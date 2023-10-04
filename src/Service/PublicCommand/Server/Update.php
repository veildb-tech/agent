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

final class Update extends AbstractServerCommand
{
    /**
     * @return bool
     * @throws Exception
     */
    protected function process(): bool
    {
        $inputOutput = $this->getInputOutput();

        try {
            $userEmail  = $inputOutput->ask("Enter your Email");
            $password   = $inputOutput->askHidden("Enter your Password");
            $user       = $this->getUserByEmail->setCredentials($userEmail, $password)->execute($userEmail);
            $server     = $this->updateServer($inputOutput, $user, $userEmail, $password);

            $this->updateEnvFile($server['uuid'], $server['secret_key']);

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
            $inputOutput->error("During updating server an error happened: " . $e->getMessage());

            return false;
        }
    }

    /**
     * Update / activate existed server
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
     * @throws Exception
     */
    private function updateServer(InputOutput $inputOutput, array $user, string $userEmail, string $password): array
    {
        if ($this->input->getOption('current')) {
            $uuid = $this->appConfig->getServerUuid();
        } else {
            $uuid = $inputOutput->ask("Enter server UUID");
        }
        $server = $this->serverApi->setCredentials($userEmail, $password)->get(htmlspecialchars($uuid));

        $serverWorkspace = str_replace('/api/workspaces/', '', $server['workspace']);

        if (!in_array($serverWorkspace, array_column($user['workspaces'], 'code'))) {
            throw new Exception('You do not have access to this server!!!');
        }

        if (!$server['url']) {
            $serverUrl = $inputOutput->ask("Enter server public Url", '');
        }

        $server = $this->serverApi->setCredentials($userEmail, $password)->update(
            $uuid,
            [
                'url'         => $serverUrl ?? $server['url'],
                'status'      => ServerStatusEnum::ENABLED->value,
                'ipAddress'   => $this->getIpAddress()
            ]
        );

        $inputOutput->success("Server successfully updated");
        return $server;
    }
}
