<?php
namespace Postnl\Ecs\Model\Resource\Order;


class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    public function _construct()
    {
        parent::_construct();
        $this->_init('Postnl\Ecs\Model\Order', 'Postnl\Ecs\Model\Resource\Order');
    }
    
}
