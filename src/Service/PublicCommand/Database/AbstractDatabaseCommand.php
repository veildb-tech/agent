<?php

declare(strict_types=1);

namespace App\Service\PublicCommand\Database;

use App\Service\AppConfig;
use App\Service\AppLogger;
use App\Service\Engine\EngineProcessor;
use App\Service\InputOutput;
use App\Service\Methods\MethodInterface;
use App\Service\Methods\MethodProcessor;
use App\Service\Platform\Custom;
use App\Service\Platform\PlatformProcessor;
use App\Service\PublicCommand\AbstractCommand;
use App\ServiceApi\Entity\Database as ServiceApiDatabase;
use App\ServiceApi\Entity\Server;
use Exception;
use Psr\Cache\InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Question\Question;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

abstract class AbstractDatabaseCommand extends AbstractCommand
{
    /**
     * @var array
     */
    protected array $config = [];

    /**
     * @var array|string[]
     */
    protected array $baseDbInfo = [
        'db_uuid',
        'name',
        'engine',
        'platform',
        'method'
    ];

    /**
     * @param AppLogger $appLogger
     * @param AppConfig $appConfig
     * @param Server $serverApi
     * @param ServiceApiDatabase $databaseApi
     * @param Analyzer $databaseAnalyzer
     * @param MethodProcessor $methodProcessor
     * @param EngineProcessor $engineProcessor
     * @param PlatformProcessor $platformProcessor
     */
    public function __construct(
        protected readonly AppLogger $appLogger,
        protected readonly AppConfig $appConfig,
        protected readonly Server $serverApi,
        protected readonly ServiceApiDatabase $databaseApi,
        protected readonly Analyzer $databaseAnalyzer,
        protected readonly MethodProcessor $methodProcessor,
        protected readonly EngineProcessor $engineProcessor,
        protected readonly PlatformProcessor $platformProcessor
    ) {
    }

    /**
     * Prompts the user for the database name and sets it in the config.
     *
     * @return void
     * @throws RuntimeException If the database name contains invalid characters.
     * @throws Exception If an error occurs while retrieving the engines, platforms, or dump methods.
     */
    protected function poll(): void
    {
        $this->getName();
        $this->getEngines();
        $this->getPlatforms();
        $this->getDumpMethods();
    }

    /**
     * Validate connection
     *
     * @return bool
     * @throws Exception
     */
    protected function checkConnection(): bool
    {
        try {
            return $this->validateConnection();
        } catch (\Exception $exception) {
            $this->getInputOutput()->error($exception->getMessage());

            return false;
        }
    }

    /**
     * Save entered data
     *
     * @param array $server
     *
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    protected function saveDBtoService(array $server): void
    {
        $this->sendDatabaseToService($server);
        $this->appConfig->saveDatabaseConfig($this->config);

        $this->analyzeDb($this->getInputOutput());
    }

    /**
     * Handles the case of invalid database connection.
     *
     * @param array $server The server configuration data.
     *
     * @return void
     * @throws ClientExceptionInterface When a client error occurs.
     * @throws DecodingExceptionInterface When an error occurs during decoding.
     * @throws InvalidArgumentException When an invalid argument is provided.
     * @throws RedirectionExceptionInterface When a redirection error occurs.
     * @throws ServerExceptionInterface When a server error occurs.
     * @throws TransportExceptionInterface When a transport error occurs.
     * @throws Exception
     */
    protected function handleInvalidConnection(array $server): void
    {
        $inputOutput = $this->getInputOutput();

        $inputOutput->warning("Can't connect to database");
        $result = $inputOutput->choice(
            "Do you want to",
            [
                'review' => 'review the entered data?',
                'save' => 'save the entered data and exit?',
                'exit' => 'exit?'
            ],
            'review'
        );

        switch ($result) {
            case 'review':
                $this->poll();
                if (!$this->checkConnection()) {
                    $this->handleInvalidConnection($server);
                }
                break;
            case 'save':
                $this->saveDBtoService($server);
                break;
            case 'exit':
                $inputOutput->warning(
                    "Database is not saved. Please try again. Contact our support if you still face this problem."
                );
                break;
        }
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function validateConnection(): bool
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
    protected function sendDatabaseToService(array $server): void
    {
        if (isset($this->config['db_uuid'])) {
            $this->databaseApi->update(
                $this->config['db_uuid'],
                [
                    'name' => $this->config['name'],
                    'engine' => $this->config['engine'],
                    'platform' => $this->config['platform']
                ]
            );
        } else {
            $data = $this->databaseApi->add([
                'name' => $this->config['name'],
                'engine' => $this->config['engine'],
                'platform' => $this->config['platform'],
                'status' => 'pending',
                'server' => '/api/servers/' . $server['uuid']
            ]);

            $this->config['db_uuid'] = $data['uid'];
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function getName(): void
    {
        $question = new Question("Enter database name", $this->config['name'] ?? null);
        $question->setValidator(function (string $answer): string {
            if ($answer == "" || !preg_match('/^[a-zA-Z0-9\s]+$/', $answer)) {
                throw new \RuntimeException(
                    'Invalid characters in the string. Only letters, numbers, and spaces are allowed.'
                );
            }
            return $answer;
        });
        $question->setMaxAttempts(2);
        $this->config['name'] = $this->getInputOutput()->askQuestion($question);
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function getPlatforms(): void
    {
        $platforms = $this->platformProcessor->getPlatforms($this->config['engine']);

        if (count($platforms) > 1) {
            $this->config['platform'] = $this->getInputOutput()->choice(
                "Select platform",
                array_map(fn($platform) => $platform->getName(), $platforms),
                $this->config['platform'] ?? Custom::CODE
            );
        } else {
            $this->config['platform'] = array_shift($platforms);
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function getEngines(): void
    {
        $engines = $this->engineProcessor->getEngines();

        $this->config['engine'] = $this->getInputOutput()->choice(
            "Select engine",
            array_map(fn($engine) => $engine->getName(), $engines),
            $this->config['engine'] ?? null
        );
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function getDumpMethods(): void
    {
        /** @var MethodInterface[] $methods */
        $methods = $this->methodProcessor->getMethods($this->config['engine']);

        $this->config['method'] = $this->getInputOutput()->choice(
            "Please select how to create dumps of real database?",
            array_map(fn($method) => $method->getDescription(), $methods),
            $this->config['method'] ?? null
        );

        $mainConfigData   = array_intersect_key($this->config, array_flip($this->baseDbInfo));
        $methodConfigData = array_diff($this->config, $mainConfigData);

        $methodConfig = $methods[$this->config['method']]->askConfig($this->getInputOutput(), $methodConfigData);
        $this->config = array_merge($mainConfigData, $methodConfig);
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
    protected function analyzeDb(InputOutput $inputOutput): void
    {
        if (!$inputOutput->confirm("Would you like to analyze a new database structure?")) {
            return;
        }

        $this->databaseAnalyzer
            ->setInputOutput($inputOutput)
            ->createTempDbAndProcess($this->config['db_uuid']);
    }
}
