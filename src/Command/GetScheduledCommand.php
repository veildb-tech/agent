<?php

namespace App\Command;

use App\ServiceApi\AppService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:db:getScheduled',
    description: 'Get scheduled db dump from service. This returns project UID',
)]
class GetScheduledCommand extends Command
{

    private AppService $appService;

    public function __construct(
        AppService $appService,
        string $name = null
    ) {
        parent::__construct($name);
        $this->appService = $appService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);


        $this->appService->getSheduledDump();


        $request = $this->appService->getRequest();
        if ($request) {

        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
