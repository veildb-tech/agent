<?php

declare(strict_types=1);

namespace App\Service\PublicCommand;

use App\Service\AppConfig;
use App\Service\AppLogger;
use App\Service\InputOutput;
use App\ServiceApi\Actions\AddServer;
use App\ServiceApi\Actions\GetUserByEmail;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallApp extends AbstractCommand
{
    /**
     * Get IP
     */
    public const GET_IP_URL = 'https://ipecho.net/plain';

    /**
     * @var array
     */
    private array $config = [];

    /**
     * @param AppLogger $appLogger
     * @param AppConfig $appConfig
     * @param AddServer $addServer
     * @param GetUserByEmail $getUserByEmail
     * @param HttpClientInterface $httpClient
     */
    public function __construct(
        private readonly AppLogger $appLogger,
        private readonly AppConfig $appConfig,
        private readonly AddServer $addServer,
        private readonly GetUserByEmail $getUserByEmail,
        private readonly HttpClientInterface $httpClient
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

        $this->validateRequiredLibs();
        $this->addServerInfo();
    }

    protected function validateRequiredLibs()
    {
        $inputOutput = $this->getInputOutput();

        $inputOutput->info('Validating required software...');
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
    protected function addServerInfo(): void
    {
        $inputOutput = $this->getInputOutput();

        $userEmail  = $inputOutput->ask("Enter your Email");
        $password   = $inputOutput->askHidden("Enter your Password");
        $serverName = $inputOutput->ask("Enter server name");

        $user   = $this->getUserByEmail->setCredentials($userEmail, $password)->execute($userEmail);
        $server = $this->addServer->setCredentials($userEmail, $password)->execute(
            [
                'name'      => $serverName,
                'status'    => 'active',
                'ipAddress' => $this->getIpAddress(),
                'workspaceId' => $this->getWorkspaceId($user, $inputOutput)
            ]
        );

        $inputOutput->success("Server successfully added");
        $inputOutput->note(
            [
                "please update .env file with next data:",
                sprintf("APP_SERVER_UUID: %s", $server['uuid']),
                sprintf("APP_SERVER_SECRET_KEY: %s", $server['secret_key'])
            ]
        );
    }

    private function getWorkspaceId(array $user, InputOutput $inputOutput): string
    {
        if (count($user['workspace']) == 1) {
            return "/api/workspaces/" . $user['workspace'][0]['id'];
        }

        $workspaceCode = $inputOutput->choice(
            "Select Workspace",
            array_column($user['workspace'], 'code')
        );

        $workspace = array_search($workspaceCode, array_column($user['workspace'], 'code'));

        return "/api/workspaces/" . $workspace['id'];
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
    private function getIpAddress(): string
    {
        try {
            return $this->httpClient->request('GET', self::GET_IP_URL)->getContent();
        } catch (\Exception $e) {
            return '127.0.0.1';
        }
    }
}
