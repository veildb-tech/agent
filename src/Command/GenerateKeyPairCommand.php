<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Security\GenerateKeyPair;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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

    protected function configure(): void
    {
        $this->setDescription('Generate public/private keys for use in your application.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userIdentifier = $io->ask("Enter user identifier!");

        try {
            [$secretKey, $publicKey] = $this->generateKeyPair->execute($userIdentifier);
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 0;
        }

        $io->newLine();
        $io->writeln('Generated Public Key:');
        $io->writeln($publicKey);

        $io->success('Done!');

        return 0;
    }
}
