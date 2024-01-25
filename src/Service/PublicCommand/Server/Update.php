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
            $userData = $this->getUserData($inputOutput);

            $server = $this->updateServer($inputOutput, ...$userData);

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
    private function updateServer(
        InputOutput $inputOutput,
        string $email,
        string $password,
        string $workspace = ''
    ): array {
        $uuid = $this->getUuid($inputOutput);
        $server = $this->serverApi->setCredentials($email, $password)->get($uuid);

        $serverUrl = $this->getServerUrl($inputOutput, $server['url']);

        $server = $this->serverApi->setCredentials($email, $password, $workspace)->update(
            $uuid,
            [
                'url'         => $serverUrl,
                'status'      => ServerStatusEnum::ENABLED->value,
                'ipAddress'   => $this->getIpAddress()
            ]
        );

        $inputOutput->success("Server successfully updated");
        return $server;
    }

    /**
     * Returns the UUID for the server. If the 'current' option is set, it returns the server UUID from the app configuration.
     * Otherwise, it prompts the user to enter the server UUID.
     *
     * @param InputOutput $inputOutput The input/output object.
     *
     * @return string The UUID for the server.
     * @throws \RuntimeException If the entered value is empty.
     *
     * @example
     * // Example usage:
     * $inputOutput = new InputOutput();
     * $uuid = $this->getUuid($inputOutput);
     */
    private function getUuid(InputOutput $inputOutput): string
    {
        if ($this->input->getOption('current')) {
            return $this->appConfig->getServerUuid();
        }
        return $inputOutput->ask("Enter server UUID", '', function ($value) {
            if (empty($value)) {
                throw new \RuntimeException('Value is required.');
            }
            return htmlspecialchars($value);
        });
    }
}
