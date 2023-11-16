<?php

declare(strict_types=1);

namespace App\Service;

use Exception;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LogLevel;
use Symfony\Component\HttpKernel\Log\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use App\ServiceApi\Actions\SendDumpLogs;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AppLogger
{
    /**
     * @var Logger|null
     */
    private ?Logger $logger = null;

    /**
     * @var OutputInterface|null
     */
    private ?OutputInterface $output = null;

    public function __construct(private readonly SendDumpLogs $sendDumpLogs)
    {
    }

    /**
     * @param string $message
     * @param string|LogLevel $level
     * @return void
     * @throws Exception
     */
    public function log(string $message, string|LogLevel $level = LogLevel::INFO): void
    {
        if ($this->logger === null) {
            throw new Exception("App logger should be initialized first. Make sure method 'initAppLogger' has be called");
        }

        $this->logger->log($level, $message);
        if ($this->output->isVerbose()) {
            $this->output->writeln($message);
        }
    }

    /**
     * @param string $dumpuuid
     * @param string $status
     * @param string $message
     * @param bool $localLog
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
    public function logToService(string $dumpuuid, string $status, string $message, bool $localLog = true): void
    {
        $this->sendDumpLogs->execute($dumpuuid, $status, $message);
        if ($localLog) {
            $this->log($message);
        }
    }

    /**
     * @param OutputInterface $output
     * @return $this
     * @throws Exception
     */
    public function initAppLogger(OutputInterface $output): static
    {
        if ($this->logger !== null) {
            throw new Exception("Logger has already been initialized");
        }
        $this->output = $output;
        $this->logger = new Logger('info');
//        $this->logger->pushHandler(new StreamHandler(APP_ROOT . 'logs/cli.log'));
        return $this;
    }
}
