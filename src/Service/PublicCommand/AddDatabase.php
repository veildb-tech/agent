<?php

declare(strict_types=1);

namespace App\Service\PublicCommand;

use App\Service\AppConfig;
use App\Service\AppLogger;
use App\Service\Engine\EngineInterface;
use App\Service\Engine\EngineProcessor;
use App\Service\InputOutput;
use App\Service\Methods\MethodInterface;
use App\Service\Methods\MethodProcessor;
use App\Service\Platform\Custom;
use App\Service\Platform\PlatformInterface;
use App\Service\Platform\PlatformProcessor;
use App\Service\PublicCommand\Database\Analyzer;
use App\ServiceApi\Actions\AddDatabase as ServiceApiAddDatabase;
use App\ServiceApi\Entity\Server;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AddDatabase extends AbstractCommand
{
    /**
     * @var array
     */
    private array $config = [];

    /**
     * @param AppLogger $appLogger
     * @param AppConfig $appConfig
     * @param Server $serverApi
     * @param ServiceApiAddDatabase $addDatabase
     * @param Analyzer $databaseAnalyzer
     * @param MethodProcessor $methodProcessor
     * @param EngineProcessor $engineProcessor
     * @param PlatformProcessor $platformProcessor
     */
    public function __construct(
        private readonly AppLogger $appLogger,
        private readonly AppConfig $appConfig,
        private readonly Server $serverApi,
        private readonly ServiceApiAddDatabase $addDatabase,
        protected readonly Analyzer $databaseAnalyzer,
        private readonly MethodProcessor $methodProcessor,
        private readonly EngineProcessor $engineProcessor,
        private readonly PlatformProcessor $platformProcessor
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
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
     */
    protected function addDatabase(): void
    {
        $inputOutput = $this->getInputOutput();

        if (!$inputOutput->confirm("Would you like to add new database?")) {
            return;
        }

        $server = $this->serverApi->get($this->appConfig->getServerUuid());

        $this->config['name'] = $inputOutput->ask("Enter database name");

        $this->getEngines();
        $this->getPlatforms();
        $this->getDumpMethods();

        try {
            $valid = $this->validateConnection();
        } catch (\Exception $exception) {
            $valid = false;
            $this->getInputOutput()->error($exception->getMessage());
        }

        if ($valid) {
            $this->sendDatabaseToService($server);
            $this->appConfig->saveDatabaseConfig($this->config);

            $this->analyzeDb($inputOutput);
        } else {
            $this->getInputOutput()->warning("Can't connect to database");
            if ($this->getInputOutput()->confirm("Do you want continue?", false)) {
                $this->sendDatabaseToService($server);
            } else {
                $this->getInputOutput()->warning("Database is not saved. Please try again. Contact our support if you still face this problem.");
            }
        }
        $this->addDatabase();
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function validateConnection(): bool
    {
        $method = $this->methodProcessor->getMethodByCode($this->config['method']);
        return $method->validate($this->config);
    }

    /**
     * @param array $server
     *
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws InvalidArgumentException
     */
    private function sendDatabaseToService(array $server): void
    {
        $data = $this->addDatabase->execute([
            'name'      => $this->config['name'],
            'engine'    => $this->config['engine'],
            'platform'  => $this->config['platform'],
            'status'    => 'pending',
            'server'    => '/api/servers/' . $server['uuid'],
            "workspace" => $server['workspace']
        ]);

        $this->config['db_uuid'] = $data['uid'];
    }

    /**
     * @return void
     * @throws Exception
     */
    private function getPlatforms(): void
    {
        $platforms = $this->platformProcessor->getPlatforms();
        $availablePlatforms = [];

        /** @var PlatformInterface $platform */
        foreach ($platforms as $platform) {
            $availablePlatforms[$platform->getCode()] = $platform->getName();
        }

        $this->config['platform'] = $this->getInputOutput()->choice(
            "Select platform",
            $availablePlatforms,
            Custom::CODE
        );
    }

    /**
     * @return void
     * @throws Exception
     */
    private function getEngines(): void
    {
        $engines = $this->engineProcessor->getEngines();
        $availableEngines = [];

        /** @var EngineInterface $engine */
        foreach ($engines as $engine) {
            $availableEngines[$engine->getCode()] = $engine->getName();
        }

        $this->config['engine'] = $this->getInputOutput()->choice("Select engine", $availableEngines);
    }

    /**
     * @return void
     * @throws Exception
     */
    private function getDumpMethods(): void
    {
        /** @var MethodInterface[] $methods */
        $methods = $this->methodProcessor->getMethods($this->config['engine']);
        $availableMethods = [];

        foreach ($methods as $method) {
            $availableMethods[$method->getCode()] = $method->getDescription();
        }

        $this->config['method'] = $this->getInputOutput()->choice(
            "Please select how to create dumps of real database?",
            $availableMethods
        );

        $methodConfig = $methods[$this->config['method']]->askConfig($this->getInputOutput());
        if ($this->appConfig->isDockerUsed()) {
            $methodConfig['dump_name'] = AppConfig::LOCAL_BACKUPS_FOLDER . ltrim($methodConfig['dump_name'], '/');
        }
        $this->config = array_merge($methodConfig, $this->config);
    }

    /**
     * @param InputOutput $inputOutput
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws InvalidArgumentException
     */
    private function analyzeDb(InputOutput $inputOutput): void
    {
        if (!$inputOutput->confirm("Would you like to analyze a new database structure?")) {
            return;
        }

        $this->databaseAnalyzer
            ->setInputOutput($inputOutput)
            ->createTempDbAndProcess($this->config['db_uuid']);

        $inputOutput->info('The DB structure analyzing successfully finished.');
    }
}
