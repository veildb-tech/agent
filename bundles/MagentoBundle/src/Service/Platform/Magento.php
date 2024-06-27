<?php

declare(strict_types=1);

namespace DbManager\MagentoBundle\Service\Platform;

use App\Service\Platform\AbstractPlatform;
use DbManager\MysqlBundle\Service\Engine\Mysql;
use DbManager\MariaDbBundle\Service\Engine\MariaDb;

class Magento extends AbstractPlatform
{
    const CODE = 'magento';

    /**
     * @return string
     */
    public function getCode(): string
    {
        return self::CODE;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Magento (Adobe Commerce)';
    }

    /**
     * @param string $engine
     * @return bool
     */
    public function supports(string $engine): bool
    {
        return in_array($engine, [Mysql::ENGINE_CODE, MariaDb::ENGINE_CODE]);
    }
}
