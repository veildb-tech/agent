<?php

declare(strict_types=1);

namespace App\Service\PublicCommand;

use App\Enum\LogStatusEnum;
use App\Exception\NoSuchMethodException;
use App\Service\AppConfig;
use App\Service\AppLogger;
use App\Service\Engines\Mysql;
use App\Service\Methods\Method;
use App\ServiceApi\Actions\GetScheduledUID;
use App\ServiceApi\Actions\FinishDump;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseProcessor extends AbstractCommand
{
    /**
     * @param AppLogger $appLogger
     * @param AppConfig $appConfig
     * @param GetScheduledUID $getScheduledUID
     * @param FinishDump $finishDump
     * @param Method $method
     * @param Mysql $mysql
     * @param Filesystem $filesystem
     */
    public function __construct(
        private readonly AppLogger $appLogger,
        private readonly AppConfig $appConfig,
        private readonly GetScheduledUID $getScheduledUID,
        private readonly FinishDump $finishDump,
        private readonly Method $method,
        private readonly Mysql $mysql,
        private readonly Filesystem $filesystem
    ) {
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws NoSuchMethodException
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->appLogger->initAppLogger($output);
        $scheduledData = $this->getScheduledUID->execute();
        if (!empty($scheduledData)) {
            if (empty($scheduledData['uuid']) || empty($scheduledData['db']['uid'])) {
                throw new \Exception("Something went wrong. Scheduled uuid and database uuid is required");
            }

            $this->init($scheduledData['db']['uid']);

            $databaseConfig = $this->appConfig->getDatabaseConfig($scheduledData['db']['uid']);
            $filename = time() . '.sql';

            $method = $this->method->getMethodClass($databaseConfig['method']);
            $this->appLogger->logToService(
                $scheduledData['uuid'],
                LogStatusEnum::PROCESSING->value,
                "Preparing backup"
            );
            $method->execute($databaseConfig, $scheduledData['db']['uid'], $filename);

            $this->mysql->execute($scheduledData['uuid'], $scheduledData['db']['uid'], $filename);

            $this->finishDump->execute($scheduledData['uuid'], 'ready', $filename);
        }
    }

    /**
     * @param string $dbuid
     * @return void
     */
    private function init(string $dbuid): void
    {
        $untouchedDir = $this->appConfig->getDumpUntouchedDirectory() . '/' . $dbuid;
        $processedDir = $this->appConfig->getDumpProcessedDirectory() . '/' . $dbuid;

        $this->filesystem->mkdir($untouchedDir);
        $this->filesystem->mkdir($processedDir);
    }
}
