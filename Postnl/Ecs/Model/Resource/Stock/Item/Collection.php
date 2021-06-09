<?php
namespace Postnl\Ecs\Model\Resource\Stock\Item;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    public function _construct()
    {
        parent::_construct();
        $this->_init('Magento\CatalogInventory\Model\Stock\Item', 'Magento\CatalogInventory\Model\ResourceModel\Stock\Item');
    }
    
}
