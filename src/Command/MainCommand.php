<?php

namespace App\Command;

use App\Entity\Annonce;
use App\Entity\Subscription;
use App\Services\OkkazeoCrawler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;

#[AsCommand(
    name: 'app:main'
)]
class MainCommand extends Command
{
    private LoggerInterface $logger;
    private MailerInterface $mailer;
    private EntityManagerInterface $entityManager;
    private OkkazeoCrawler $crawler;

    protected function configure()
    {
        $this->addOption('idempotentSubscriptions', 'i', InputArgument::OPTIONAL, 'Readonly subscription.lastUrlViewed ', false);
    }

    public function __construct(OkkazeoCrawler $crawler, LoggerInterface $logger, MailerInterface $mailer, EntityManagerInterface $entityManager)
    {
        parent::__construct('main');

        $this->crawler = $crawler;
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $subscriptions = $this->entityManager->getRepository(Subscription::class)->findBy([], ['frequency' => 'DESC', 'id' => ' DESC']);

        $notFoundGames = [];
        $scriptStartTime = new \DateTime();

        /** @var Subscription $subscription */
        foreach ($subscriptions as $subscription) {
            $this->logger->info("Subscription : {$subscription->getEmail()}");

            $previousUpdate = $subscription->getUpdatedAt();

            // Todo : move this to query builder once we reach 1000 subscriptions
            if ($previousUpdate) {
                $nextUpdate = $previousUpdate->add(new \DateInterval('PT'.$subscription->getFrequency().'H'))->sub(new \DateInterval('PT1M')); // Remove 1 min to prevent time drifting (sending schedule might shift otherwise)
                if ($nextUpdate > $scriptStartTime) {
                    $this->logger->info("Next update at {$nextUpdate->format('c')}, current script start time is ".$scriptStartTime->format('c'));
                    continue;
                }
            }

            $topRankedAnnonces = [];
            $scannedNames = [];
            $firstAnnonce = null;

            foreach (range(1, 15) as $page) {
                $okkazeoAnnonces = $this->crawler->getOkkazeoAnnonces("https://www.okkazeo.com/jeux/arrivages?FiltreCodePostal={$subscription->getFilterZipcode()}&FiltreDistance={$subscription->getFilterRange()}&FiltreRechJeux=on&FiltreLangue=1&FiltreRechPrixMin=&FiltreRechPrixMax=&page=$page");

                $endOfPageReached = $this->crawler->filterAnnonces($okkazeoAnnonces, $subscription, $topRankedAnnonces, $scannedNames, $notFoundGames, $firstAnnonce);

                if (!$endOfPageReached) {
                    break;
                }
            }

            $this->crawler->sortAnnonces($topRankedAnnonces);

            if (!$input->getOption('idempotentSubscriptions')) {
                $subscription->setLastAnnonceUrl($firstAnnonce->getUrl());
            }

            $this->logger->info('-------------------');

            if ($topRankedAnnonces) {
                $this->logger->info('Top games : ');

                $table = new Table($output);
                $table
                    ->setHeaders(['Okkazeo Url', 'Okkazeo Name', 'Prix', 'BGG Url', 'BGG rank', 'BGG name', 'yearpublished', 'playingtime', 'boardgamedesigner', 'averageweight'])
                    ->setRows(array_map(function (Annonce $a) {
                        return $a->__toArray();
                    }, $topRankedAnnonces));
                $table->render();

                $email = (new TemplatedEmail())
                    ->from('mailer@wink-dev.com')
                    ->to($subscription->getEmail())
                    ->subject('Okkazeo Alert')
                    ->htmlTemplate('emails/notification.html.twig')

                    // pass variables (name => value) to the template
                    ->context([
                        'subscription' => $subscription,
                        'topRankedAnnonces' => $topRankedAnnonces,
                    ]);

                $this->mailer->send($email);
            }

            $subscription->setUpdatedAt($scriptStartTime);
            $this->entityManager->flush();
        }

        if ($notFoundGames) {
            $this->logger->info('Not found : ');

            $table = new Table($output);
            $table
                ->setRows($notFoundGames);
            $table->render();
        }

        return Command::SUCCESS;
    }
}
