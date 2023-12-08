<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Crontab\CrontabManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:cron:install',
    description: 'Generates and installs crontab for current user',
)]
final class AppCronInstallCommand extends Command
{
    /**
     * @param CrontabManager $crontabManager
     * @param string|null $name
     */
    public function __construct(
        protected readonly CrontabManager $crontabManager,
        string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->addOption(
            'hide-errors',
            null,
            InputOption::VALUE_OPTIONAL
        );
    }

    /**
     * Executes "app:cron:install" command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        try {
            $tasks = $this->crontabManager->getTasks();
            if (!count($tasks)) {
                $this->crontabManager->saveTasks();

                $output->writeln('<info>Crontab has been generated and saved</info>');
                return Command::SUCCESS;
            }

            if ($input->hasOption('hide-errors')) {
                return Command::SUCCESS;
            }

            $output->writeln('<error>Crontab has already been generated and saved</error>');
            return Command::FAILURE;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
