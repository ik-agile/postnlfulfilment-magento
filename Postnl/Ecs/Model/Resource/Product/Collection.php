<?php
namespace Postnl\Ecs\Model\Resource\Product;


class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    public function _construct()
    {
        parent::_construct();
        $this->_init('Postnl\Ecs\Model\Product', 'Postnl\Ecs\Model\Resource\Product');
    }
    
}
