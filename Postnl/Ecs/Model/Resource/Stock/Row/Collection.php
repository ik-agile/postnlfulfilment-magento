<?php
namespace Postnl\Ecs\Model\Resource\Stock\Row;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    public function _construct()
    {
        parent::_construct();
        $this->_init('Postnl\Ecs\Model\Stock', 'Postnl\Ecs\Model\Resource\Stock');
    }
    
}
