<?php
namespace Postnl\Ecs\Setup;
 
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Config\ConfigOptionsListConstants;

use Magento\Framework\DB\Ddl\Table as TableSchema;
use Magento\Framework\DB\Ddl\Trigger;
 
class UpgradeSchema implements UpgradeSchemaInterface
{
    
    protected $_schemaName;
    
    protected function getSchemaName() 
    {
        if ( ! $this->_schemaName) 
        {
            /** @var \Magento\Framework\ObjectManagerInterface $om */
            $om = \Magento\Framework\App\ObjectManager::getInstance();		
            /** @var \Magento\Framework\App\DeploymentConfig $config */
            $config = $om->get('Magento\Framework\App\DeploymentConfig');
            /** https://github.com/magento/magento2/issues/2090 */
            $this->_schemaName = $config->get(
                ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTION_DEFAULT
                . '/' . ConfigOptionsListConstants::KEY_NAME
            );
        }
        return $this->_schemaName;
    }
    
    protected function _describeTriggers(AdapterInterface $connection)
    {
        $columns = [
            'TRIGGER_NAME',
            'EVENT_MANIPULATION',
            'EVENT_OBJECT_CATALOG',
            'EVENT_OBJECT_SCHEMA',
            'EVENT_OBJECT_TABLE',
            'ACTION_ORDER',
            'ACTION_CONDITION',
            'ACTION_STATEMENT',
            'ACTION_ORIENTATION',
            'ACTION_TIMING',
            'ACTION_REFERENCE_OLD_TABLE',
            'ACTION_REFERENCE_NEW_TABLE',
            'ACTION_REFERENCE_OLD_ROW',
            'ACTION_REFERENCE_NEW_ROW',
            'CREATED',
        ];
        $sql = 'SELECT ' . implode(', ', $columns)
            . ' FROM ' . $connection->quoteIdentifier(['INFORMATION_SCHEMA','TRIGGERS'])
            . ' WHERE ';

        $schema = $this->getSchemaName();
        if ($schema) {
            $sql .= $connection->quoteIdentifier('EVENT_OBJECT_SCHEMA')
                . ' = ' . $connection->quote($schema);
        } else {
            $sql .= $connection->quoteIdentifier('EVENT_OBJECT_SCHEMA')
                . ' != ' . $connection->quote('INFORMATION_SCHEMA');
        }

        $results = $connection->query($sql);

        $data = [];
        foreach ($results as $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            if (null !== $row['created']) {
                $row['created'] = new \DateTime($row['created']);
            }
            $data[$row['trigger_name']] = $row;
        }

        return $data;
    }
    
    protected function _groupTriggers($triggers)
    {
        /* group existing triggers by table / event / time */
        $result = [];
        foreach ($triggers as $trigger)
        {
            $table = $trigger['event_object_table'];
            if ( ! isset($result[$table]))
                $result[$table] = [];
            
            $event = $trigger['event_manipulation'];
            if ( ! isset($result[$table][$event]))
                $result[$table][$event] = [];
            
            $time = $trigger['action_timing'];
            $result[$table][$event][$time] = $trigger;
        }
        return $result;
    }
    
    protected function _createDirtyTable(SchemaSetupInterface $setup)
    {
        if ($setup->tableExists('postnlecs_product_dirty'))
            return;
        
        /* postnlecs_product_dirty */
        $table = $setup
            ->getConnection()
            ->newTable($setup->getTable('postnlecs_product_dirty'))
            ->addColumn(
                'entity_id',
                TableSchema::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false,],
                'Entity ID'
            )
            ->addColumn(
                'created_at',
                TableSchema::TYPE_DATETIME,
                null,
                ['nullable' => false],
                'Created At'
            )
            ->setComment('PostNL ECS Dirty Products')
            ->addIndex('entity_id', 'entity_id', ['type' => AdapterInterface::INDEX_TYPE_UNIQUE])
            ->addForeignKey(
                $setup->getFkName(
                    'postnlecs_product_dirty',
                    'entity_id',
                    'catalog_product_entity',
                    'entity_id'
                ),
                'entity_id',
                $setup->getTable('catalog_product_entity'),
                'entity_id',
                TableSchema::ACTION_CASCADE
            )
        ;
        $setup->getConnection()->createTable($table);
    }
    
    protected function _generateTriggerName(SchemaSetupInterface $setup, Trigger $trigger)
    {
        return $setup->getConnection()->getTriggerName(
            $trigger->getTable(),
            $trigger->getTime(),
            $trigger->getEvent()
        );
    }
    
    protected function _createTriggers(SchemaSetupInterface $setup)
    {
        $existingTriggers = $this->_describeTriggers($setup->getConnection());
        $groupedTriggers = $this->_groupTriggers($existingTriggers);
        $time = Trigger::TIME_AFTER;
        foreach ($this->_getAffectedTables($setup) as $table => $body)
        {
            foreach ($this->_getAffectedEvents() as $event)
            {
                $trigger = new Trigger();
                $trigger->setTime($time);
                $trigger->setEvent($event);
                $trigger->setTable($table);
                if (isset($groupedTriggers[$table][$event][$time]))
                {
                    $existingTrigger = $groupedTriggers[$table][$event][$time];
                    $trigger->setName($existingTrigger['trigger_name']);
                    $trigger->addStatement($body);
                    /* get clear trigger body */
                    $existingBody = trim(trim($existingTrigger['action_statement']), ';');
                    if (strtolower(substr($existingBody, 0, 5)) === 'begin')
                        $existingBody = substr($existingBody, 5);
                    if (strtolower(substr($existingBody, -3)) === 'end')
                        $existingBody = substr($existingBody, 0, strlen($existingBody) - 3);
                    $existingBody = trim($existingBody);
                    $trigger->addStatement($existingBody);
                    /* drop existing trigger */
                    $setup->getConnection()->dropTrigger($trigger->getName());
                }
                else
                {
                    $trigger->setName($this->_generateTriggerName($setup, $trigger));
                    $trigger->addStatement($body);
                }
                $setup->getConnection()->createTrigger($trigger);
            }
        }
    }
    
    protected function _getAffectedTables(SchemaSetupInterface $setup)
    {
        $dirtyTable = $setup->getTable('postnlecs_product_dirty');
        
        $entityBody = <<<SQL
    /* POSTNL ECS TRIGGER START */
    INSERT INTO `{$dirtyTable}` (
        `entity_id`,
        `created_at`
    ) VALUES (
        NEW.`entity_id`,
        NOW()
    )
    ON DUPLICATE KEY UPDATE `created_at` = VALUES(`created_at`)
    /* POSTNL ECS TRIGGER END */

SQL;

        $productBody = <<<SQL
    /* POSTNL ECS TRIGGER START */
    INSERT INTO `{$dirtyTable}` (
        `entity_id`,
        `created_at`
    ) VALUES (
        NEW.`product_id`,
        NOW()
    )
    ON DUPLICATE KEY UPDATE `created_at` = VALUES(`created_at`)
    /* POSTNL ECS TRIGGER END */

SQL;

        $result = [
            $setup->getTable('catalog_product_entity')             => $entityBody,
            $setup->getTable('catalog_product_entity_varchar')     => $entityBody,    
            $setup->getTable('catalog_product_entity_datetime')    => $entityBody,
            $setup->getTable('catalog_product_entity_decimal')     => $entityBody,
            $setup->getTable('catalog_product_entity_text')        => $entityBody,
            $setup->getTable('catalog_product_entity_int')         => $entityBody,
            $setup->getTable('cataloginventory_stock_item')        => $productBody,
        ];
        
        return $result;
    }
    
    protected function _getAffectedEvents()
    {
        return [
            Trigger::EVENT_INSERT,
            Trigger::EVENT_UPDATE,
        ];
    }
    
    protected function _createProductTables(SchemaSetupInterface $setup)
    {
        if ($setup->tableExists('postnlecs_product'))
            return;
        
        /* postnlecs_order */
        $table = $setup
            ->getConnection()
            ->newTable($setup->getTable('postnlecs_product'))
            ->addColumn(
                'product_id',
                TableSchema::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Product ID'
            )
            ->addColumn(
                'filename',
                TableSchema::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Filename'
            )
            ->addColumn(
                'status',
                TableSchema::TYPE_TEXT,
                16,
                ['nullable' => false],
                'Status'
            )
            ->addColumn(
                'created_at',
                TableSchema::TYPE_DATETIME,
                null,
                ['nullable' => false],
                'Created At'
            )
            ->addColumn(
                'updated_at',
                TableSchema::TYPE_DATETIME,
                null,
                ['nullable' => false],
                'Updated At'
            )
            ->setComment('PostNL ECS Product Export Files')
        ;
        $setup->getConnection()->createTable($table);
        
        if ($setup->tableExists('postnlecs_product_row'))
            return;
        
        /* postnlecs_product_row */
        $table = $setup
            ->getConnection()
            ->newTable($setup->getTable('postnlecs_product_row'))
            ->addColumn(
                'product_row_id',
                TableSchema::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Product Row ID'
            )
            ->addColumn(
                'product_id',
                TableSchema::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Product ID'
            )
            ->addColumn(
                'entity_id',
                TableSchema::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Entity ID'
            )
            ->addColumn(
                'created_at',
                TableSchema::TYPE_DATETIME,
                null,
                ['nullable' => false],
                'Created At'
            )
            ->addColumn(
                'updated_at',
                TableSchema::TYPE_DATETIME,
                null,
                ['nullable' => false],
                'Updated At'
            )
            ->addIndex('product_id', 'product_id')
            ->addIndex('entity_id', 'entity_id')
            ->addForeignKey(
                $setup->getFkName(
                    'postnlecs_product_row',
                    'product_id',
                    'postnlecs_product',
                    'product_id'
                ),
                'product_id',
                $setup->getTable('postnlecs_product'),
                'product_id',
                TableSchema::ACTION_CASCADE
            )
            ->addForeignKey(
                $setup->getFkName(
                    'postnlecs_product_row',
                    'entity_id',
                    'catalog_product_entity',
                    'entity_id'
                ),
                'entity_id',
                $setup->getTable('catalog_product_entity'),
                'entity_id',
                TableSchema::ACTION_CASCADE
            )
            ->setComment('PostNL ECS Product Export Rows')
        ;
        $setup->getConnection()->createTable($table);
    }
    
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
 
        if (version_compare($context->getVersion(), '0.3.0') < 0) 
        {
            $this->_createDirtyTable($setup);
            $this->_createTriggers($setup);
            $this->_createProductTables($setup);
        }
 
        $setup->endSetup();
    }
}
