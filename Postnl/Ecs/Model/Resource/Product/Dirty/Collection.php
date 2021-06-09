<?php
namespace Postnl\Ecs\Model\Resource\Product\Dirty;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    public function _construct()
    {
        parent::_construct();
        $this->_init('Postnl\Ecs\Model\Product\Dirty', 'Postnl\Ecs\Model\Resource\Product\Dirty');
    }
    
}
