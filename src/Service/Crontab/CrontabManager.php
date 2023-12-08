<?php

declare(strict_types=1);

namespace App\Service\Crontab;

use App\Service\AppConfig;
use Exception;
use Symfony\Component\Process\Process;

/**
 * Manager works with cron tasks
 */
class CrontabManager
{
    /**#@+
     * Constants for wrapping App section in crontab
     */
    public const TASKS_BLOCK_START = '#~ BACKUPS APP START';
    public const TASKS_BLOCK_END = '#~ BACKUPS APP END';
    /**#@-*/

    /**#@+
     * List of Tasks
     */
    public const CRON_TASKS = [
        [
            'time'    => '* * * * *',
            'command' => '{rootDir}/bin/console app:db:process >> {logDir}/cron.log 2>&1'
        ],
        [
            'time'    => '* * * * *',
            'command' => '{rootDir}/bin/console app:db:backups:clear >> {logDir}/cron.log 2>&1'
        ]
    ];
    /**#@-*/

    /**
     * @param AppConfig $appConfig
     */
    public function __construct(
        protected readonly AppConfig $appConfig
    ) {
    }

    /**
     * Build tasks block start text.
     *
     * @return string
     */
    private function getTasksBlockStart(): string
    {
        $tasksBlockStart = self::TASKS_BLOCK_START;
        if (defined('BP')) {
            $tasksBlockStart .= ' ' . hash("sha256", BP);
        }
        return $tasksBlockStart;
    }

    /**
     * Build tasks block end text.
     *
     * @return string
     */
    private function getTasksBlockEnd(): string
    {
        $tasksBlockEnd = self::TASKS_BLOCK_END;
        if (defined('BP')) {
            $tasksBlockEnd .= ' ' . hash("sha256", BP);
        }
        return $tasksBlockEnd;
    }

    /**
     * Get cron tasks list
     *
     * @return array
     * @throws Exception
     */
    public function getTasks(): array
    {
        $this->checkSupportedOs();
        $content = $this->getCrontabContent();
        $pattern = '!(' . $this->getTasksBlockStart() . ')(.*?)(' . $this->getTasksBlockEnd() . ')!s';

        if (preg_match($pattern, $content, $matches)) {
            $tasks = trim($matches[2], PHP_EOL);
            return explode(PHP_EOL, $tasks);
        }
        return [];
    }

    /**
     * Save Tasks to crontab
     *
     * @return void
     * @throws Exception
     */
    public function saveTasks(): void
    {
        $this->checkSupportedOs();
        $baseDir = $this->appConfig->getProjectDir();
        $logDir  = $baseDir . '/var/log';

        $tasks = self::CRON_TASKS;

        foreach ($tasks as $key => $task) {
            $tasks[$key]['command'] = str_replace(['{rootDir}', '{logDir}'], [$baseDir, $logDir], $task['command']);
        }

        $content = $this->getCrontabContent();
        $content = $this->cleanAppSection($content);
        $content = $this->generateSection($content, $tasks);

        $this->save($content);
    }

    /**
     * Generate Magento Tasks Section
     *
     * @param string $content
     * @param array $tasks
     *
     * @return string
     */
    private function generateSection(string $content, array $tasks = []): string
    {
        if (count($tasks)) {
            // Add EOL symbol to previous line if not exist.
            if (!str_ends_with($content, PHP_EOL)) {
                $content .= PHP_EOL;
            }

            $content .= $this->getTasksBlockStart() . PHP_EOL;
            foreach ($tasks as $task) {
                $content .= $task['time'] . ' ' . PHP_BINARY . ' ' . $task['command'] . PHP_EOL;
            }
            $content .= $this->getTasksBlockEnd() . PHP_EOL;
        }

        return $content;
    }

    /**
     * Clean Tasks Section in crontab content
     *
     * @param string $content
     *
     * @return string
     */
    private function cleanAppSection(string $content): string
    {
        return preg_replace(
            '!' . preg_quote($this->getTasksBlockStart()) . '.*?'
            . preg_quote($this->getTasksBlockEnd() . PHP_EOL) . '!s',
            '',
            $content
        );
    }

    /**
     * Get crontab content without Tasks Section
     *
     * In case of some exceptions the empty content is returned
     *
     * @return string
     */
    private function getCrontabContent(): string
    {
        try {
            $process = Process::fromShellCommandline(
                'crontab -l 2>/dev/null'
            )->setTimeout(
                null
            );

            $process->run();

            if (!$process->isSuccessful()) {
                return '';
            }

            $content = $process->getOutput();
        } catch (Exception $e) {
            return '';
        }

        return $content;
    }

    /**
     * Save crontab
     *
     * @param string $content
     *
     * @return void
     * @throws Exception
     */
    private function save(string $content): void
    {
        $content = str_replace(['%', '"', '$'], ['%%', '\"', '\$'], $content);

        $process = Process::fromShellCommandline(
            'echo "' . $content . '" | crontab -'
        )->setTimeout(
            null
        );

        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception($process->getErrorOutput());
        }
    }

    /**
     * Check that OS is supported
     *
     * If OS is not supported then no possibility to work with crontab
     *
     * @return void
     * @throws Exception
     */
    private function checkSupportedOs(): void
    {
        if (stripos(PHP_OS, 'WIN') === 0) {
            throw new Exception('Your operating system is not supported to work with this command');
        }
    }
}
