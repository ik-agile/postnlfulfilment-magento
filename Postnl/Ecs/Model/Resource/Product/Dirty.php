<?php
namespace Postnl\Ecs\Model\Resource\Product;

class Dirty extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    
    public function _construct()
    {    
        $this->_init('postnlecs_product_dirty', 'entity_id');
    }
    
    public function deleteRows($ids, $upTo)
    {
        if ( ! count($ids))
            return $this;
        
        $ids = $this->getConnection()->quoteInto('`entity_id` IN (?)', $ids);
        $upTo = $this->getConnection()->quoteInto('`created_at` <= ?', $upTo);
        $this->getConnection()->delete($this->getMainTable(), "{$ids} AND {$upTo}");
        
        return $this;
    }
    
}
