<?php
namespace Postnl\Ecs\Model\Resource;


class Order extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    
    const MAX_ORDERS = 100;

    /**
     * @var \Postnl\Ecs\Helper\Config
     */
    protected $ecsConfigHelper;

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Postnl\Ecs\Helper\Config $ecsConfigHelper,
        $connectionName = null
    ) {
        $this->ecsConfigHelper = $ecsConfigHelper;
        parent::__construct($context, $connectionName);
    }
    
    public function _construct()
    {    
        $this->_init('postnlecs_order', 'order_id');
    }
    
    protected function _getProcessedOrdersSelect()
    {
        $connection      = $this->getConnection();
        $select = $connection->select()
            ->from(
                array('por' => $this->getTable('postnlecs_order_row')),
                array('entity_id')
            )
            ->join(
                array('po' => $this->getTable('postnlecs_order')),
                'po.order_id = por.order_id',
                array()
            )
            ->where('po.status = ?', \Postnl\Ecs\Model\Order::STATUS_UPLOADED)
        ;
        return $select;
    }
    
    public function applyUnprocessedFilter($collection)
    {
        $config = $this->ecsConfigHelper;
        $collection->addAttributeToFilter('status', array('in' => $config->getOrderStatus()));
		//error_log(print_r($config->getOrderMethod(),TRUE));
        $collection->addAttributeToFilter('shipping_method', array('in' => $config->getOrderMethod()));
        $collection->getSelect()
            ->order('main_table.created_at ASC')
            ->where('main_table.entity_id NOT IN ?', $this->_getProcessedOrdersSelect())
            ->limit(self::MAX_ORDERS)
        ;
        return $collection;
    }
    
}
