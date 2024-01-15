<?php

declare(strict_types=1);

namespace App\Service\Security;

abstract class AbstractSecurity
{
    /**
     * @param string $newId
     * @param string $file
     *
     * @return string|null
     */
    protected function getKeyById(string $newId, string $file): ?string
    {
        $rows = $this->readFile($file);
        foreach ($rows as $row) {
            [$id, $key] = explode(':', $row);
            if ($id == $newId) {
                return $key;
            }
        }
        return null;
    }

    /**
     * Remove string from file
     *
     * @param string $id
     * @param string $fileName
     *
     * @return void
     */
    protected function clearFile(string $id, string $fileName): void
    {
        $key = $this->getKeyById($id, $fileName);
        $this->filesystem->dumpFile(
            $fileName,
            str_replace($id . ":" . $key . "\n", '', file_get_contents($fileName))
        );
    }

    /**
     * Read file with keys
     *
     * @param string $file
     *
     * @return array
     */
    protected function readFile(string $file): array
    {
        if (!$this->filesystem->exists($file)) {
            return [];
        }
        return array_filter(explode("\n", file_get_contents($file)));
    }
}
