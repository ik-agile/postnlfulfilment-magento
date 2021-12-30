<?php
namespace Postnl\Ecs\Model;


class Order extends \Magento\Framework\Model\AbstractModel {
    
    const STATUS_PENDING = 'pending';
    const STATUS_ERROR = 'error';
    const STATUS_UPLOADED = 'uploaded';

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManager;

    public function _construct()
    {
        parent::_construct();
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();;
        $this->_init('Postnl\Ecs\Model\Resource\Order');
    }
    
    public function beforeSave()
    {
        $nowFileName = (new \DateTime())->format('YmdHisu');
		$now = (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        if ($this->isObjectNew())
        {
            $this->setCreatedAt($now);
            if(empty($this->getFilename()) || strlen($this->getFilename()) < 8)
                $this->setFilename(sprintf('ORD%s.xml', preg_replace('#[^0-9]+#', '', $nowFileName)));
        }
        $this->setUpdatedAt($now);
        
        return parent::beforeSave();
    }
    
    public function getUnprocessedOrders()
    {
        $collection = $this->objectManager->create('Magento\Sales\Model\Order')->getCollection();
        return $this->getResource()->applyUnprocessedFilter($collection);
    }
    
    public function getLatest()
    {
        return $this->getCollection()
            ->addFieldToFilter('status', \Postnl\Ecs\Model\Order::STATUS_UPLOADED)
            ->setOrder('updated_at', 'DESC')
            ->getFirstItem()
        ;
    }
    
}
