<?php

namespace App\Services;

use App\Entity\Annonce;
use App\Entity\Game;
use App\Entity\Subscription;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\CachingHttpClient;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\RedirectionException;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OkkazeoCrawler
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private CachingHttpClient $cachedHttpClient;
    private Store $store;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger, EntityManagerInterface $entityManager, KernelInterface $appKernel)
    {
        $this->httpClient = $httpClient;

        $this->store = new Store($appKernel->getCacheDir().'/webCache');
        $this->cachedHttpClient = new CachingHttpClient(
            new FakeCacheHeaderClient(
                $httpClient
            ),
            $this->store
        );
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    public function getCacheContent(string $url, array $options = [])
    {
        if (false === file_exists($this->store->getPath('md'.hash('sha256', $url)))) {
            sleep(2);
        }

        try {
            $response = $this->cachedHttpClient->request('GET', $url, $options);
            $content = $response->getContent();
        } catch (ClientException $e) {
            $this->logger->debug('Extra sleep');
            sleep(10);
            $response = $this->cachedHttpClient->request('GET', $url, $options);
            $content = $response->getContent();
        }

        return $content;
    }

    public function getNodeAttr(string $url, string $nodeSelector, string $attr)
    {
        $content = $this->getCacheContent($url);

        $crawler = new Crawler($content);
        $node = $crawler->filterXPath($nodeSelector);

        if ($node->count() > 0 && $node->first()->attr($attr)) {
            return $node->first()->attr($attr);
        }

        return null;
    }

    public function populateFromBgg(Game $game): void
    {
        if ($game->getBggId() === null) {
            $this->logger->info('BGG id not found');

            return;
        }

        $content = $this->getCacheContent("https://boardgamegeek.com/xmlapi2/thing?id={$game->getBggId()}&stats=1");

        $crawler = new Crawler($content);

        $node = $crawler->filter('rank#1');
        if ($node->count() > 0 && $node->first()->attr('value')) {
            $game->setBggRank($node->first()->attr('value'));
        }

        $node = $crawler->filter('name[type=primary]');
        if ($node->count() > 0 && $node->first()->attr('value')) {
            $game->setBggName($node->first()->attr('value'));
        }

        $node = $crawler->filterXPath('//items/item/yearpublished');
        if ($node->count() > 0 && $node->first()->attr('value')) {
            $game->setBggYearPublished($node->first()->attr('value'));
        }

        $node = $crawler->filterXPath('//items/item/playingtime');
        if ($node->count() > 0 && $node->first()->attr('value')) {
            $game->setBggPlayingTime($node->first()->attr('value'));
        }

        $node = $crawler->filter('[type="boardgamedesigner"]');
        if ($node->count() > 0 && $node->first()->attr('value')) {
            $game->setBggDesigner($node->first()->attr('value'));
        }

        $node = $crawler->filter('averageweight');

        if ($node->count() > 0 && $node->first()->attr('value')) {
            $game->setBggWeight((string) round((float) $node->first()->attr('value'), 2));
        }
    }

    public function findGameIdUsingBGG($name): ?string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/vf/', '', $name);
        $nameWithoutSpecial = preg_replace('/[^A-Za-z0-9 ]/', ' ', $name);
        $nameWithoutSpecialStripParenthesis = preg_replace('/[^A-Za-z0-9 ]/', ' ', substr($name, 0, strpos($name, '(')));
        $nameWithoutSpecialStripParenthesis = substr($nameWithoutSpecialStripParenthesis, 0, strpos($nameWithoutSpecialStripParenthesis, 'edition'));

        $searchUrls = [
            'https://boardgamegeek.com/xmlapi2/search?exact=1&query='.rawurlencode($name).'&type=boardgame',
            'https://boardgamegeek.com/xmlapi2/search?exact=1&query='.rawurlencode($nameWithoutSpecial).'&type=boardgame',
            'https://boardgamegeek.com/xmlapi2/search?exact=1&query='.rawurlencode($nameWithoutSpecialStripParenthesis).'&type=boardgame',
            'https://boardgamegeek.com/xmlapi2/search?query='.rawurlencode($nameWithoutSpecial).'&type=boardgame',
            'https://boardgamegeek.com/xmlapi2/search?query='.rawurlencode($nameWithoutSpecialStripParenthesis.'&type=boardgame'),
        ];

        foreach ($searchUrls as $altName) {
            $gameId = $this->getNodeAttr($altName, '//items/item', 'id');

            if (!empty($gameId)) {
                $this->logger->info("Found BGG id in BGG search : $gameId");

                return $gameId;
            }
        }

        return null;
    }

    public function getGameFromAnnonce(Annonce $okkazeoAnnonce): ?Game
    {
        $game = $this->entityManager->getRepository(Game::class)->findOneBy(['okkazeoName' => $okkazeoAnnonce->getName()]);

        if ($game) {
            return $game;
        }

        try {
            $content = $this->getCacheContent($okkazeoAnnonce->getUrl(), ['max_redirects' => 0]);
        } catch (RedirectionException $e) {
            // HTTP 303 : "L'annonce recherchée n'existe pas ou a été retirée."
            return null;
        }

        $crawler = new Crawler($content);

        $detail = $crawler->filter('a[href^="/jeux/view"]');
        $detailUrl = $detail->first()->attr('href');

        $okkazeoGameUrl = 'https://www.okkazeo.com'.$detailUrl;
        $okkazeoGameId = substr($okkazeoGameUrl, strrpos($okkazeoGameUrl, '/') + 1);

        $game = new Game();
        $this->entityManager->persist($game);
        $game->setOkkazeoId((int) $okkazeoGameId);
        $game->setOkkazeoName($okkazeoAnnonce->getName());
        $game->setOkkazeoImageUrl($okkazeoAnnonce->getImageUrl());

        try {
            $content = $this->getCacheContent($okkazeoGameUrl);
            $bggUrl = (new Crawler($content))->filter('a[href^="https://boardgamegeek.com/boardgame/"]')->first()->attr('href');
            preg_match('/https:\/\/boardgamegeek.com\/boardgame\/(\d+)\//', $bggUrl, $matches);
            $this->logger->info("Found BGG url in Okkazeo View : {$bggUrl}, id : {$matches[1]}");

            $game->setBggId($matches[1]);
        } catch (\Exception $e) {
            $game->setBggId($this->findGameIdUsingBGG($okkazeoAnnonce->getName()));
        }

        $this->populateFromBgg($game);
        $this->entityManager->flush();

        return $game;
    }

    /**
     * @param Annonce[] $okkazeoAnnonces
     */
    public function filterAnnonces(array $okkazeoAnnonces, Subscription $subscription, &$topRankedAnnonces, &$scannedNames = [], &$notFoundGames = [], &$firstAnnonce = null): bool
    {
        foreach ($okkazeoAnnonces as $annonce) {
            if (empty($firstAnnonce)) {
                $firstAnnonce = $annonce;
            }

            if ($subscription->getLastAnnonceUrl() && (int) preg_replace('#^(.*?)/([^/]*)$#', '$2', $annonce->getUrl()) <= (int) preg_replace('#^(.*?)/([^/]*)$#', '$2', $subscription->getLastAnnonceUrl())) {
                $this->logger->info("Previously watched Annonce reached {$annonce->getUrl()} <= {$subscription->getLastAnnonceUrl()}");

                return false;
            }

            $this->logger->info("Lookup Name : {$annonce->getName()} - {$annonce->getUrl()}");

            if (in_array($annonce->getName(), $subscription->getExcludedGames())) {
                $this->logger->info('       Skip (excluded game)');
                continue;
            }

            if (in_array($annonce->getName(), $scannedNames)) {
                $this->logger->info('       Skip (already in list)');
                continue;
            }
            $scannedNames[] = $annonce->getName();

            if (empty($annonce->getGame()->getBggId())) {
                $notFoundGames[$annonce->getName()] = ['name' => $annonce->getName()];
                $this->logger->info('       Skip (no BGG ID)');
                continue;
            }

            $this->logger->info("       Rank : {$annonce->getGame()->getBggRank()} - https://boardgamegeek.com/boardgame/{$annonce->getGame()->getBggId()} ");

            if ($subscription->getFilterMinYear() &&
                $annonce->getGame()->getBggYearPublished() &&
                is_numeric($annonce->getGame()->getBggYearPublished()) &&
                $annonce->getGame()->getBggYearPublished() < $subscription->getFilterMinYear()) {
                $this->logger->info('       Skip (FilterMinYear)');
                continue;
            }

            if ($subscription->getFilterMinRank() && $annonce->getGame()->getBggRank() == 'Not Ranked') {
                $this->logger->info('       Skip (Not ranked)');
                continue;
            }

            if ($subscription->getFilterMinRank() &&
                !empty($annonce->getGame()->getBggRank()) &&
                is_numeric($annonce->getGame()->getBggRank()) &&
                $annonce->getGame()->getBggRank() > $subscription->getFilterMinRank()) {
                $this->logger->info('       Skip (getFilterMinRank)');
                continue;
            }

            $topRankedAnnonces[$annonce->getName()] = $annonce;
        }

        return true;
    }

    public function sortAnnonces(&$topRankedAnnonces)
    {
        usort($topRankedAnnonces, function (Annonce $a, Annonce $b) {
            if ($a->getGame()->getBggRank() == $b->getGame()->getBggRank()) {
                return 0;
            }

            return ($a->getGame()->getBggRank() < $b->getGame()->getBggRank()) ? -1 : 1;
        });
    }

    /**
     * @return Annonce[]
     */
    public function getOkkazeoAnnonces(string $url): array
    {
        // Let's be nice with servers
        sleep(5);

        $response = $this->httpClient->request('GET', $url);
        $content = $response->getContent();

        $crawler = new Crawler($content);
        $arrivage = $crawler->filter('.arrivage.jeu');

        $okkazeoAnnonces = [];
        foreach ($arrivage as $jeu) {
            $okkazeoAnnonce = new Annonce(
                url: 'https://www.okkazeo.com'.(new Crawler($jeu))->filter('.titre a')->first()->attr('href'),
                imageUrl: (new Crawler($jeu))->filter('.mts')->first()->attr('src'),
                name: (new Crawler($jeu))->filter('.titre a')->first()->text(),
                price: (new Crawler($jeu))->filter('.prix')->first()->text()
            );

            $game = $this->getGameFromAnnonce($okkazeoAnnonce);
            if (!$game) {
                continue;
            }
            $okkazeoAnnonce->setGame($game);

            $okkazeoAnnonces[] = $okkazeoAnnonce;
        }

        return $okkazeoAnnonces;
    }
}
