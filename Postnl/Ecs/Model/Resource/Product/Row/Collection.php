<?php
namespace Postnl\Ecs\Model\Resource\Product\Row;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    public function _construct()
    {
        parent::_construct();
        $this->_init('Postnl\Ecs\Model\Product\Row', 'Postnl\Ecs\Model\Resource\Product\Row');
    }
    
    protected function _renderFiltersBefore() 
    {
        $this->getSelect()
            ->join(
                array('po' => $this->getTable('postnlecs_product')),
                'po.product_id = main_table.product_id',
                array('filename')
            )
            ->join(
                array('o' => $this->getTable('sales_product')),
                'o.entity_id = main_table.entity_id',
                array('increment_id')
            )
            ->where('po.status = ?', \Postnl\Ecs\Model\Product::STATUS_UPLOADED)
        ;

        parent::_renderFiltersBefore();
    }
    
}
