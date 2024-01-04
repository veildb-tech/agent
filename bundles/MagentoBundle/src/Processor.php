<?php

declare(strict_types=1);

namespace DbManager\MagentoBundle;

use DbManager\CoreBundle\Interfaces\DbDataManagerInterface;
use DbManager\CoreBundle\Interfaces\EngineInterface;
use Illuminate\Database\Connection;
use DbManager\MysqlBundle\Processor as MysqlProcessor;
use DbManager\MagentoBundle\DataProcessor\Platform\MagentoEavDataProcessorService;
use DbManager\MagentoBundle\Exception\NotMagentoException;
use Exception;

/**
 * Mysql Processor instance
 */
final class Processor extends MysqlProcessor implements EngineInterface
{
    /**
     * Engine const
     */
    public const DRIVER_ENGINE = 'mysql';

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function process(DbDataManagerInterface $dbDataManager): void
    {
        parent::process($dbDataManager);
        foreach ($dbDataManager->getIterableAdditions() as $addition) {
            $this->dataProcessor = new MagentoEavDataProcessorService(
                sprintf('%s_entity', $addition['entity_type_code']),
                $addition,
                $this->connection
            );

            try {
                $this->processAttributes($addition);
            } catch (\Exception $exception) {
                $this->addError($exception->getMessage());
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getDbStructure(DbDataManagerInterface $dbDataManager): array
    {
        $structure = parent::getDbStructure($dbDataManager);
        $connection = $this->getDbConnection($dbDataManager->getName());
        return [
            ... $structure,
            'additional_data' => $this->getAdditionalData($dbDataManager, $connection)
        ];
    }

    /**
     * Get platform additional attributes
     *
     * @param DbDataManagerInterface $dbDataManager
     * @param Connection $connection
     *
     * @return array
     * @throws NotMagentoException
     */
    protected function getAdditionalData(DbDataManagerInterface $dbDataManager, Connection $connection): array
    {
        $data = [];
        if ($dbDataManager->getPlatform() === 'magento') {
            try {
                $attributes = $connection->select(
                    "SELECT `attribute_code`, `backend_type`, `eav_entity_type`.`entity_type_code`"
                    . " FROM `eav_attribute` LEFT JOIN `eav_entity_type` "
                    . " ON `eav_entity_type`.`entity_type_id` = `eav_attribute`.`entity_type_id` "
                    . " WHERE `backend_type` != 'static' AND `source_model` IS NULL;"
                );
            } catch (Exception $exception) {
                throw new NotMagentoException("Could not find Magento eav tables. Please ensure your platform is Magento. Otherwise process with a custom platform");
            }

            $data['eav_attributes'] = $attributes;
        }
        return $data;
    }

    /**
     * @param array $rule
     * @return void
     * @throws \Exception
     */
    protected function processAttributes(array $rule): void
    {
        switch ($rule['method']) {
            case 'truncate':
                $this->dataProcessor->truncate($rule['attribute_code']);
                break;
            case 'update':
                $this->dataProcessor->update($rule['attribute_code'], $rule['value']);
                break;
            case 'fake':
                $valuesCount = 50;
                $values = [];
                for ($i = 0; $i < $valuesCount; $i++) {
                    $values[] = $this->generateFake($rule['value'], []);
                }
                $this->dataProcessor->fake($rule['attribute_code'], $values);
                break;
            default:
                throw new \Exception(sprintf("No such method %s", $rule['method']));
        }
    }
}
