<?php

declare(strict_types=1);

namespace App\Service\PublicCommand\Server;

use App\Enum\ServerStatusEnum;
use App\Service\InputOutput;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class Add extends AbstractServerCommand
{
    /**
     * @return bool
     * @throws Exception
     */
    protected function process(): bool
    {
        $inputOutput = $this->getInputOutput();

        try {
            $userData = $this->getUserData($inputOutput);

            $server = $this->createServer($inputOutput, ...$userData);

            $this->updateEnvFile($server['uuid'], $server['secret_key']);

            $this->setupCronJobs();

            return true;
        } catch (
            Exception
            | ClientExceptionInterface
            | DecodingExceptionInterface
            | ServerExceptionInterface
            | TransportExceptionInterface
            | InvalidArgumentException
            | RedirectionExceptionInterface $e
        ) {
            $inputOutput->error("During adding the server an error happened: " . $e->getMessage());

            return false;
        }
    }

    /**
     * Create new server
     *
     * @param InputOutput $inputOutput
     * @param string $email
     * @param string $password
     * @param string $workspace
     *
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function createServer(
        InputOutput $inputOutput,
        string $email,
        string $password,
        string $workspace = ''
    ): array {
        $serverName = $inputOutput->ask("Enter server name");
        $serverUrl  = $this->getServerUrl($inputOutput);

        $server = $this->serverApi->setCredentials($email, $password, $workspace)->create(
            [
                'name'        => $serverName,
                'url'         => $serverUrl,
                'status'      => ServerStatusEnum::ENABLED->value,
                'ipAddress'   => $this->getIpAddress()
            ]
        );

        $inputOutput->success("Server successfully added");
        return $server;
    }

    /**
     * Setup Cron Jobs
     *
     * @return void
     * @throws Exception
     */
    private function setupCronJobs(): void
    {
        if (!$this->appConfig->isDockerUsed()) {
            $this->shellProcess->run($this->appConfig->getProjectDir() . '/dbvisor-agent app:cron:install');
        }
    }
}
