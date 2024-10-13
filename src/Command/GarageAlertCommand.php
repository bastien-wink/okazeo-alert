<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Notifier\Bridge\Discord\DiscordOptions;
use Symfony\Component\Notifier\Bridge\Discord\Embeds\DiscordEmbed;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'garage:alert'
)]
class GarageAlertCommand extends Command
{
    protected function configure()
    {
    }

    public function __construct(
        private ChatterInterface $chatter,
        private HttpClientInterface $client,
        private LoggerInterface $logger,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager
    )
    {
        parent::__construct('main');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        try {
            $loginResponse = $this->client->request(
                'POST',
                'https://ha101-1.overkiz.com/enduser-mobile-web/enduserAPI/login',
                ['body' => ['userId' => $_ENV['TAHOMA_USER'], 'userPassword' => $_ENV['TAHOMA_PASSWORD']]],
            );
            $headers = $loginResponse->getHeaders();
            $cookie = $headers['set-cookie'][0];

            $devicesResponse = $this->client->request(
                'GET',
                'https://ha101-1.overkiz.com/enduser-mobile-web/enduserAPI/setup/devices/'.$_ENV['TAHOMA_DEVICE'],
                ['headers' => ['Cookie' => $cookie]]
            );

            $states = json_decode($devicesResponse->getContent())->states;
            $openClosedPartialState = "missing";
            foreach ($states as $state) {
                if ($state->name == 'core:OpenClosedPartialState') {
                    $openClosedPartialState = $state->value;
                    break;
                }
            }
        } catch (\Exception $e) {
            $openClosedPartialState = $_ENV['TAHOMA_USER'].' disconnected, '.$e->getMessage();
        }

        $output->writeln(sprintf('Status : <info>%s</info>', $openClosedPartialState));

        $parisTimezone = new \DateTimeZone('Europe/Paris');
        $currentDatetime = new \DateTime('now', $parisTimezone);
        $chatMessage = new ChatMessage('Info garage');
        $discordOptions = (new DiscordOptions())
            ->username('Ravanel Assistant')
            ->addEmbed((new DiscordEmbed())
                ->color(2021216)
                ->title("Il est $openClosedPartialState")
                ->description($currentDatetime->format('c'))
            );

        //if ($openClosedPartialState != 'closed') {
        $startTime = new \DateTime('23:00', $parisTimezone);
        $endTime = new \DateTime('05:00', $parisTimezone);
        if ($currentDatetime >= $startTime || $currentDatetime < $endTime) {
            $chatMessage = new ChatMessage('Alerte garage @here');
            $discordOptions = (new DiscordOptions())
                ->username('Ravanel Assistant')
                ->addEmbed((new DiscordEmbed())
                    ->color(2021216)
                    ->title("Garage en status '$openClosedPartialState' après 23h")
                    ->description($currentDatetime->format('c'))
                );
        }
        //}
        $chatMessage->options($discordOptions);
        $this->chatter->send($chatMessage);

        return Command::SUCCESS;
    }
}
