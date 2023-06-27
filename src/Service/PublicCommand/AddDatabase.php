<?php

declare(strict_types=1);

namespace App\Service\PublicCommand;

use App\Enum\MethodsEnum;
use App\Service\AppConfig;
use App\Service\AppLogger;
use App\Service\Database\Analyzer;
use App\ServiceApi\Actions\AddDatabase as ServiceApiAddDatabase;
use Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddDatabase extends AbstractCommand
{
    /**
     * @var array
     */
    private array $config = [];

    /**
     * @param AppLogger $appLogger
     * @param AppConfig $appConfig
     * @param ServiceApiAddDatabase $addDatabase
     * @param Analyzer $databaseAnalyzer
     */
    public function __construct(
        private readonly AppLogger $appLogger,
        private readonly AppConfig $appConfig,
        private readonly ServiceApiAddDatabase $addDatabase,
        protected readonly Analyzer $databaseAnalyzer
    ) {
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->appLogger->initAppLogger($output);
        $this->initInputOutput($input, $output);
        $this->addDatabase();
    }

    /**
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    protected function addDatabase(): void
    {
        $inputOutput = $this->getInputOutput();

        if (!$inputOutput->confirm("Would you like to add new database?")) {
            return;
        }

        $this->config['name'] = $inputOutput->ask("Enter database name");
        $this->config['engine'] = $inputOutput->choice("Select engine", [
            'mysql', 'postgresql'
        ]);
        $this->config['platform'] = $inputOutput->choice("Select platform", [
            'custom', 'magento', 'wordpress', 'shopware'
        ]);

        $this->sendDatabaseToService();
        $this->getDumpMethods();

        if ($this->validateConnection()) {
            $this->appConfig->saveDatabaseConfig($this->config);

            if ($inputOutput->confirm("Would you like to analyze a new database structure?")) {
                $this->databaseAnalyzer->process($this->config['db_uid'], $this->config['name']);
            }
        }
        $this->addDatabase();
    }

    /**
     * @return bool
     */
    private function validateConnection(): bool
    {
        return true;
    }

    /**
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function sendDatabaseToService(): void
    {
        $data = $this->addDatabase->execute([
            'name'      => $this->config['name'],
            'engine'    => $this->config['engine'],
            'platform'  => $this->config['platform'],
            'status'    => 'pending'
        ]);

        $this->config['db_uid'] = $data['uid'];
    }

    /**
     * @return void
     * @throws Exception
     */
    private function getDumpMethods(): void
    {
        $methods = [];
        foreach (MethodsEnum::cases() as $case) {
            $methods[$case->value] = $case->description($this->config['engine']);
        }
        $this->config['method'] = $this->getInputOutput()->choice(
            "Please select how to create dumps of real database?",
            $methods
        );

        switch ($this->config['method']) {
            case MethodsEnum::DUMP->value:
                $this->askDbCredentials();
                break;
            case MethodsEnum::SSH_DUMP->value:
                $this->askSshCredentials();
                $this->askDbCredentials();
                break;
            case MethodsEnum::MANUAL->value:
                $this->config['dump_name'] = $this->getInputOutput()
                    ->ask('What is dump file name?');
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    private function askDbCredentials(): void
    {
        $validateRequired = function ($value) {
            if (empty($value)) {
                throw new \RuntimeException('Value is required.');
            }

            return $value;
        };

        $inputOutput = $this->getInputOutput();

        $this->config['db_host'] = $inputOutput->ask('Host', 'localhost', $validateRequired);
        $this->config['db_user'] = $inputOutput->ask('User:', 'root', $validateRequired);
        $this->config['db_password'] = $inputOutput->askHidden('Password');
        $this->config['db_name'] = $inputOutput->ask('Database name:', null, $validateRequired);
        $this->config['db_port'] = $inputOutput->ask('Port: ', '3306', $validateRequired);
    }

    private function askSshCredentials(): void
    {
    }
}
