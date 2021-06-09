<?php
namespace Postnl\Ecs\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

use Magento\Framework\DB\Ddl\Table as TableSchema;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        
        /* postnlecs_order */
        $table = $installer
            ->getConnection()
            ->newTable($installer->getTable('postnlecs_order'))
            ->addColumn(
                'order_id',
                TableSchema::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Order ID'
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
            ->setComment('PostNL ECS Order Export Files')
        ;
        $installer->getConnection()->createTable($table);
        
        /* postnlecs_order_row */
        $table = $installer
            ->getConnection()
            ->newTable($installer->getTable('postnlecs_order_row'))
            ->addColumn(
                'order_row_id',
                TableSchema::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Order Row ID'
            )
            ->addColumn(
                'order_id',
                TableSchema::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Order ID'
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
            ->addIndex('order_id', 'order_id')
            ->addIndex('entity_id', 'entity_id')
            ->addForeignKey(
                $installer->getFkName(
                    'postnlecs_order_row',
                    'order_id',
                    'postnlecs_order',
                    'order_id'
                ),
                'order_id',
                $installer->getTable('postnlecs_order'),
                'order_id',
                TableSchema::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName(
                    'postnlecs_order_row',
                    'entity_id',
                    'sales_order',
                    'entity_id'
                ),
                'entity_id',
                $installer->getTable('sales_order'),
                'entity_id',
                TableSchema::ACTION_CASCADE
            )
            ->setComment('PostNL ECS Order Export Rows')
        ;
        $installer->getConnection()->createTable($table);
        
        /* postnlecs_shipment */
        $table = $installer
            ->getConnection()
            ->newTable($installer->getTable('postnlecs_shipment'))
            ->addColumn(
                'shipment_id',
                TableSchema::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Shipment ID'
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
                'message_number',
                TableSchema::TYPE_TEXT,
                32,
                ['nullable' => true, 'default' => null],
                'Message Number'
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
            ->addIndex('message_number', 'message_number')
            ->addIndex('lookup', ['status', 'filename'])
            ->setComment('PostNL ECS Shipment Import Files')
        ;
        $installer->getConnection()->createTable($table);
        
        /* postnlecs_shipment_row */
        $table = $installer
            ->getConnection()
            ->newTable($installer->getTable('postnlecs_shipment_row'))
            ->addColumn(
                'shipment_row_id',
                TableSchema::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Shipment Row ID'
            )
            ->addColumn(
                'shipment_id',
                TableSchema::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Shipment ID'
            )
            ->addColumn(
                'entity_id',
                TableSchema::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true, 'default' => null],
                'Entity ID'
            )
            ->addColumn(
                'order_id',
                TableSchema::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Order ID'
            )
            ->addColumn(
                'tracking_number',
                TableSchema::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Tracking Number'
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
            ->addIndex('shipment_id', 'shipment_id')
            ->addIndex('entity_id', 'entity_id')
            ->addForeignKey(
                $installer->getFkName(
                    'postnlecs_shipment_row',
                    'shipment_id',
                    'postnlecs_shipment',
                    'shipment_id'
                ),
                'shipment_id',
                $installer->getTable('postnlecs_shipment'),
                'shipment_id',
                TableSchema::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName(
                    'postnlecs_shipment_row',
                    'entity_id',
                    'sales_order',
                    'entity_id'
                ),
                'entity_id',
                $installer->getTable('sales_order'),
                'entity_id',
                TableSchema::ACTION_SET_NULL
            )
            ->setComment('PostNL ECS Shipment Import Rows')
        ;
        $installer->getConnection()->createTable($table);

        /* postnlecs_stock */
        $table = $installer
            ->getConnection()
            ->newTable($installer->getTable('postnlecs_stock'))
            ->addColumn(
                'stock_id',
                TableSchema::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Stock ID'
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
                'message_number',
                TableSchema::TYPE_TEXT,
                32,
                ['nullable' => true, 'default' => null],
                'Message Number'
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
            ->addIndex('message_number', 'message_number')
            ->addIndex('lookup', ['status', 'filename'])
            ->setComment('PostNL ECS Stock Import Files')
        ;
        $installer->getConnection()->createTable($table);
        
        /* postnlecs_stock_row */
        $table = $installer
            ->getConnection()
            ->newTable($installer->getTable('postnlecs_stock_row'))
            ->addColumn(
                'stock_row_id',
                TableSchema::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Stock Row ID'
            )
            ->addColumn(
                'stock_id',
                TableSchema::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Stock ID'
            )
            ->addColumn(
                'entity_id',
                TableSchema::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true, 'default' => null],
                'Entity ID'
            )
            ->addColumn(
                'product_id',
                TableSchema::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Product ID'
            )
            ->addColumn(
                'qty',
                TableSchema::TYPE_DECIMAL,
                '12,4',
                ['nullable' => false],
                'Qty'
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
            ->addIndex('stock_id', 'stock_id')
            ->addIndex('entity_id', 'entity_id')
            ->addForeignKey(
                $installer->getFkName(
                    'postnlecs_stock_row',
                    'stock_id',
                    'postnlecs_stock',
                    'stock_id'
                ),
                'stock_id',
                $installer->getTable('postnlecs_stock'),
                'stock_id',
                TableSchema::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName(
                    'postnlecs_stock',
                    'entity_id',
                    'catalog_product_entity',
                    'entity_id'
                ),
                'entity_id',
                $installer->getTable('catalog_product_entity'),
                'entity_id',
                TableSchema::ACTION_SET_NULL
            )
            ->setComment('PostNL ECS Stock Import Rows')
        ;
        $installer->getConnection()->createTable($table);
        $installer->endSetup();
    }
}
