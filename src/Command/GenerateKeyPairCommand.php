<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Security\GenerateKeyPair;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function PHPUnit\Framework\throwException;

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
        $this->addOption(
            'identifier',
            null,
            InputOption::VALUE_REQUIRED,
            'User identifier'
        )->addOption(
            'key-only',
            null,
            InputOption::VALUE_NONE,
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
            $id = $input->getOption('identifier');
            $keyOnly = $input->getOption('key-only');

            if (null === $id) {
                throw new Exception(sprintf('You must provide the "%s" option.', '--identifier'));
            }

            [$secretKey, $publicKey] = $this->generateKeyPair->execute($id, $keyOnly, $io);
        } catch (Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        if ($keyOnly) {
            $io->writeln($publicKey);

            return Command::SUCCESS;
        }

        $io->newLine();
        $io->writeln('Public Key:');
        $io->writeln($publicKey);

        $io->success('Done!');

        return Command::SUCCESS;
    }
}
