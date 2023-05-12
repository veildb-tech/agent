<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Service\Actions\GetDump;

#[AsCommand(
    name: 'app:dump',
    description: 'Check if need to create dump. It returns project_id if need and null if no',
)]
class CreateBackupCommand extends Command
{

    private GetDump $dump;

    public function __construct(
        GetDump $dump,
        string $name = null
    ) {
        parent::__construct($name);
        $this->dump = $dump;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dump->getScheduledDumps();
        $output->write('project1');

        return Command::SUCCESS;
    }
}
