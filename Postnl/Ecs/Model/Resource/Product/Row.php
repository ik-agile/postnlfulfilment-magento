<?php
namespace Postnl\Ecs\Model\Resource\Product;

class Row extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    
    public function _construct()
    {    
        $this->_init('postnlecs_product_row', 'product_row_id');
    }
    
}
