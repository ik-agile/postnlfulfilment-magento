<?php
namespace Postnl\Ecs\Model\Resource\Order\Row;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    public function _construct()
    {
        parent::_construct();
        $this->_init('Postnl\Ecs\Model\Order\Row', 'Postnl\Ecs\Model\Resource\Order\Row');
    }
    
    protected function _renderFiltersBefore() 
    {
        $this->getSelect()
            ->join(
                array('po' => $this->getTable('postnlecs_order')),
                'po.order_id = main_table.order_id',
                array('filename')
            )
            ->join(
                array('o' => $this->getTable('sales_order')),
                'o.entity_id = main_table.entity_id',
                array('increment_id')
            )
            ->where('po.status = ?', \Postnl\Ecs\Model\Order::STATUS_UPLOADED)
        ;

        parent::_renderFiltersBefore();
    }
    
}
