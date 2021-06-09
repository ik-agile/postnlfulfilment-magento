<?php
namespace Postnl\Ecs\Model;


class Shipment extends \Magento\Framework\Model\AbstractModel {

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSED = 'processed';
    const STATUS_ERROR = 'error';
    const STATUS_SKIPPED = 'skipped';

    public function _construct()
    {
        parent::_construct();
        $this->_init('Postnl\Ecs\Model\Resource\Shipment');
    }
    
    public function beforeSave()
    {
        $now = (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        if ($this->isObjectNew())
            $this->setCreatedAt($now);
        $this->setUpdatedAt($now);
        
        return parent::beforeSave();
    }
    
    public function getUnprocessed($files)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('status', array('in' => array(
                self::STATUS_PROCESSED,
                self::STATUS_SKIPPED,
            ))) 
            ->addFieldToFilter('filename', array('in' => $files))
        ;
        $processed = array();
        foreach ($collection as $item)
            $processed[] = $item->getFilename();
            
        return array_diff($files, $processed);
    }
    
    public function getAlreadyProcessed($files)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('status', array('in' => array(
                self::STATUS_PROCESSED,
                self::STATUS_SKIPPED,
            ))) 
            ->addFieldToFilter('filename', array('in' => $files))
        ;
        $processed = array();
        foreach ($collection as $item)
            $processed[] = $item->getFilename();
            
        return $processed;
    }
    
    public function getLatest()
    {
        return $this->getCollection()
            ->addFieldToFilter('status', \Postnl\Ecs\Model\Shipment::STATUS_PROCESSED)
            ->setOrder('updated_at', 'DESC')
            ->getFirstItem()
        ;
    }
    
}
