<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Security\GenerateKeyPair;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:server:generate-keypair',
    description: 'Generate public/private keys for use in your application.'
)]
final class GenerateKeyPairCommand extends Command
{
    /**
     * @param GenerateKeyPair $generateKeyPair
     */
    public function __construct(
        protected readonly GenerateKeyPair $generateKeyPair
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription('Generate public/private keys for use in your application.');
        $this->addOption(
            'identifier',
            null,
            InputOption::VALUE_REQUIRED,
            'User identifier'
        )->addOption(
            'key-only',
            null,
            InputOption::VALUE_OPTIONAL,
            'Return only generated public key'
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            if (!$input->hasOption('identifier')) {
                $io->error('identifier is required');

                return Command::FAILURE;
            }

            [$secretKey, $publicKey] = $this->generateKeyPair->execute($input->getOption('identifier'), $io);
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        if ($input->getOption('key-only')) {
            $io->writeln($publicKey);

            return Command::SUCCESS;
        }

        $io->newLine();
        $io->writeln('Generated Public Key:');
        $io->writeln($publicKey);

        $io->success('Done!');

        return Command::SUCCESS;
    }
}
