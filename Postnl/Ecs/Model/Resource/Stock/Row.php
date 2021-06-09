<?php
namespace Postnl\Ecs\Model\Resource\Stock;

class Row extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    
    public function _construct()
    {    
        $this->_init('postnlecs_stock_row', 'stock_row_id');
    }
    
}
