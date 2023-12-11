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
            $userEmail  = $this->input->getOption('email') ?? $inputOutput->ask("Enter your Email");
            $password   = $this->input->getOption('password') ?? $inputOutput->askHidden("Enter your Password");
            $user       = $this->getUserByEmail->setCredentials($userEmail, $password)->execute($userEmail);
            $server     = $this->createServer($inputOutput, $user, $userEmail, $password);

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
     * @param array $user
     * @param string $userEmail
     * @param string $password
     *
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function createServer(InputOutput $inputOutput, array $user, string $userEmail, string $password): array
    {
        $serverName = $inputOutput->ask("Enter server name");
        if (!$this->appConfig->isDockerUsed()) {
            $serverUrl = $inputOutput->ask("Enter server public Url", '');
        } else {
            $serverUrl = $this->appConfig->getDockerServerUrl();
        }

        $server = $this->serverApi->setCredentials($userEmail, $password)->create(
            [
                'name'        => $serverName,
                'url'         => $serverUrl,
                'status'      => ServerStatusEnum::ENABLED->value,
                'ipAddress'   => $this->getIpAddress(),
                'workspaceId' => $this->getWorkspaceId($user, $inputOutput)
            ]
        );

        $inputOutput->success("Server successfully added");
        return $server;
    }

    /**
     * Get workspace ID
     *
     * @param array $user
     * @param InputOutput $inputOutput
     *
     * @return string
     */
    private function getWorkspaceId(array $user, InputOutput $inputOutput): string
    {
        if (count($user['workspaces']) == 1) {
            return "/api/workspaces/" . $user['workspaces'][0]['code'];
        }

        $workspaceCode = $inputOutput->choice(
            "Select Workspace",
            array_column($user['workspaces'], 'code')
        );

        $key = array_search($workspaceCode, array_column($user['workspaces'], 'code'));

        return "/api/workspaces/" . $user['workspaces'][$key]['code'];
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
