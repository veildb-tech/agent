<?php

declare(strict_types=1);

namespace App\Service\PublicCommand\Server;

use App\Service\AppConfig;
use App\Service\PublicCommand\AbstractCommand;
use App\Service\PublicCommand\AddDatabase;
use App\Service\ShellProcess;
use App\ServiceApi\Actions\GetUserByEmail;
use App\ServiceApi\Entity\Server;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

abstract class AbstractServerCommand extends AbstractCommand
{
    /**
     * Get IP
     */
    public const GET_IP_URL = 'https://ipecho.net/plain';

    /**
     * @param AppConfig $appConfig
     * @param Server $serverApi
     * @param AddDatabase $addDatabase
     * @param ShellProcess $shellProcess
     * @param GetUserByEmail $getUserByEmail
     * @param HttpClientInterface $httpClient
     */
    public function __construct(
        protected readonly AppConfig $appConfig,
        protected readonly Server $serverApi,
        protected readonly AddDatabase $addDatabase,
        protected readonly ShellProcess $shellProcess,
        protected readonly GetUserByEmail $getUserByEmail,
        protected readonly HttpClientInterface $httpClient
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
        $this->initInputOutput($input, $output);

        if ($this->process() && !$input->getOption('current')) {
            $this->addDatabase->execute($input, $output);
        }
    }

    /**
     * Get IP address
     *
     * @return string
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function getIpAddress(): string
    {
        try {
            return $this->httpClient->request('GET', self::GET_IP_URL)->getContent();
        } catch (\Exception $e) {
            return '127.0.0.1';
        }
    }

    /**
     * Update ENV file
     *
     * @param string $newUuid
     * @param string $newSKey
     *
     * @return void
     * @throws Exception
     */
    protected function updateEnvFile(string $newUuid, string $newSKey): void
    {
        $envFile = $this->appConfig->getProjectDir() . '/.env';

        $uuid = $this->appConfig->getServerUuid();
        $command = 'sed -e "s/^APP_SERVER_UUID=' . $uuid . '/APP_SERVER_UUID=' . $newUuid . '/g"';
        $this->updateFile($command, $envFile);

        $secretKey = $this->appConfig->getServerSecretKey();
        $command = 'sed -e "s/^APP_SERVER_SECRET_KEY=' . $secretKey . '/APP_SERVER_SECRET_KEY=' . $newSKey . '/g"';
        $this->updateFile($command, $envFile);

        $this->appConfig->updateEnvConfigs();
    }

    /**
     * Update file
     *
     * @param string $command
     * @param string $envFile
     *
     * @return void
     * @throws Exception
     */
    private function updateFile(string $command, string $envFile): void
    {
        $this->shellProcess->run($command . ' ' . $envFile . ' > ' . $envFile . '.tmp');
        $this->shellProcess->run(' cat ' . $envFile . '.tmp > ' . $envFile);
        $this->shellProcess->run(' rm ' . $envFile . '.tmp');
    }
}
