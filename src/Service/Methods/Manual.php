<?php

declare(strict_types=1);

namespace App\Service\Methods;

use App\Exception\DumpNotFoundException;
use App\Service\AppConfig;
use App\Service\InputOutput;

class Manual extends AbstractMethod
{
    /**
     * @param array $dbConfig
     * @param string $dbUuid
     * @param string|null $filename
     *
     * @return string
     * @throws DumpNotFoundException
     */
    public function execute(array $dbConfig, string $dbUuid, ?string $filename = null): string
    {
        $originFile = $dbConfig['dump_name'];
        if (!is_file($originFile)) {
            $originFile = $this->getOriginFile($dbUuid, $dbConfig['dump_name']);
        }

        if (!$filename) {
            $filename = time() . '.sql';

            if (str_contains($originFile, '.gz')) {
                $filename .= '.gz';
            }
        }
        $destFile = $this->getOriginFile($dbUuid, $filename);

        if (!is_file($originFile)) {
            throw new DumpNotFoundException("Dump file not found");
        }

        copy($originFile, $destFile);

        return $destFile;
    }

    /**
     * @inheritDoc
     */
    public function validate(array $config): bool
    {
        return is_file($config['dump_name']);
    }

    /**
     * @inheritDoc
     */
    public function askConfig(InputOutput $inputOutput): array
    {
        if ($this->appConfig->isDockerUsed()) {
            $dumpName = $inputOutput->ask(
                sprintf(
                    "Enter path to DB dump file started from %s/?",
                    rtrim($this->appConfig->getLocalBackupsDir(), '/')
                ),
                null,
                self::validateRequired(...)
            );

            return [
                'dump_name' => AppConfig::LOCAL_BACKUPS_FOLDER . ltrim($dumpName, '/')
            ];
        }

        return [
            'dump_name' => $inputOutput->ask(
                'Enter full path to DB dump file?',
                null,
                self::validateRequired(...)
            )
        ];
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'manual';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Configure manual dump deployment';
    }
}
