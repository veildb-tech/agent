<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Service\AppConfig;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class CommandAccessListener implements EventSubscriberInterface
{
    public function __construct(
        private AppConfig $appConfig
    ) {
    }

    /**
     * @param ConsoleCommandEvent $event
     *
     * @return void
     */
    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();

        if ($command->getName() === 'app:server:update' && !$this->appConfig->getServerUuid()) {
            $event->disableCommand();

            $output = $event->getOutput();
            $output->writeln(
                '<error>An information about the server did\'t found. You must to use app:server:add to add the server.</error>'
            );
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'onConsoleCommand',
        ];
    }
}
