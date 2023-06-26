<?php

declare(strict_types=1);

namespace App\Service\Methods;

use \Exception;

class Dump extends AbstractMethod
{

    /**
     * @param array $dbConfig
     * @param string $filename
     * @return void
     * @throws Exception
     */
    public function execute(array $dbConfig, string $dbUuid, string $filename): void
    {
        $destFile = $this->getOriginFile($dbUuid, $filename);
        $dbPassword = !empty($dbConfig['db_password']) ? sprintf('-p%s', $dbConfig['db_password']) : '';
        $this->shellProcess->run(sprintf(
            "mysqldump -u %s %s %s > %s",
            $dbConfig['db_user'],
            $dbPassword,
            $dbConfig['db_name'],
            $destFile
        ));
    }
}
