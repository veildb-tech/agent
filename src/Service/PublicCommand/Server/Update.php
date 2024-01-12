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
            $userEmail  = $this->input->getOption('email') ?? $inputOutput->ask("Enter your Email");
            $password   = $this->input->getOption('password') ?? $inputOutput->askHidden("Enter your Password");
            $workspace   = $this->input->getOption('workspace') ?? $inputOutput->ask("Enter your Workspace code");

            $user       = $this->getUserByEmail->setCredentials($userEmail, $password, $workspace)->execute($userEmail);

            $server     = $this->updateServer($inputOutput, $user, $userEmail, $password, $workspace);
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
    private function updateServer(
        InputOutput $inputOutput,
        array $user,
        string $userEmail,
        string $password,
        string $workspace = ''
    ): array {
        if ($this->input->getOption('current')) {
            $uuid = $this->appConfig->getServerUuid();
        } else {
            $uuid = $inputOutput->ask("Enter server UUID");
        }
        $server = $this->serverApi->setCredentials($userEmail, $password)->get(htmlspecialchars($uuid));

        if (!$server['url']) {
            $serverUrl = $inputOutput->ask("Enter server public Url", '');
        }

        $server = $this->serverApi->setCredentials($userEmail, $password, $workspace)->update(
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
