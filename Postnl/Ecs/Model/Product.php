<?php
namespace Postnl\Ecs\Model;


class Product extends \Magento\Framework\Model\AbstractModel {
    
    const STATUS_PENDING = 'pending';
    const STATUS_ERROR = 'error';
    const STATUS_UPLOADED = 'uploaded';
    
    const MAX_PRODUCTS_COUNT = 500;
    const FLAG_MAX_TIMESTAMP = 'max_timestamp';
    
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $catalogProductFactory;

    /**
     * @var \Postnl\Ecs\Model\Resource\Stock\Item\CollectionFactory
     */
    protected $catalogInventoryStockItemFactory;
    
    /**
     * @var \Postnl\Ecs\Model\Resource\Product\DirtyFactory
     */
    protected $resourceDirtyFactory;
    
    /**
     * @var \Postnl\Ecs\Model\Resource\Product\Dirty\CollectionFactory
     */
    protected $dirtyCollectionFactory;

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManager;
    
    public function _construct()
    {
        parent::_construct();
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->catalogProductFactory = $this->objectManager->create('Magento\Catalog\Model\ProductFactory');
        $this->resourceDirtyFactory = $this->objectManager->create('Postnl\Ecs\Model\Resource\Product\DirtyFactory');
        $this->catalogInventoryStockItemFactory = $this->objectManager->create('Postnl\Ecs\Model\Resource\Stock\Item\CollectionFactory');
        $this->dirtyCollectionFactory = $this->objectManager->create('Postnl\Ecs\Model\Resource\Product\Dirty\CollectionFactory');
        $this->_init('Postnl\Ecs\Model\Resource\Product');
    }
    
    public function beforeSave()
    {
        $now = (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        if ($this->isObjectNew())
        {
            $this->setCreatedAt($now);
            $this->setFilename(sprintf('PRD%s.xml', preg_replace('#[^0-9]+#', '', $now)));
        }
        $this->setUpdatedAt($now);
        
        return parent::beforeSave();
    }
    
    public function getLatest()
    {
        return $this->getCollection()
            ->addFieldToFilter('status', \Postnl\Ecs\Model\Product::STATUS_UPLOADED)
            ->setOrder('updated_at', 'DESC')
            ->getFirstItem()
        ;
    }
    
    public function getUnprocessedProducts()
    {
        $collection = $this->dirtyCollectionFactory->create();
        $productIds = array();
        $maxTimestamp = null;
        foreach ($collection as $item)
        {
            if ($item->getCreatedAt() > $maxTimestamp)
                $maxTimestamp = $item->getCreatedAt();
            $productIds[] = $item->getId();
        }
            
        $productsCollection = $this->catalogProductFactory->create()->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', array('in' => $productIds))
            ->setPageSize(self::MAX_PRODUCTS_COUNT)
            ->setCurPage(1)
        ;
        
        $map = array();
        foreach ($productsCollection as $product)
            $map[$product->getId()] = $product;
        $stocks = $this->catalogInventoryStockItemFactory->create();
        $stocks->addFieldToFilter('product_id', array('in' => array_keys($map)));
        foreach ($stocks as $stock)
        {
            if ( ! isset($map[$stock->getProductId()]))
                continue;
            
            $product = $map[$stock->getProductId()];
            $product->setStockItem($stock);
        }
        $productsCollection->setFlag(self::FLAG_MAX_TIMESTAMP, $maxTimestamp);
        return $productsCollection;
    }
    
    public function clearUnprocessedProducts($collection)
    {
        if ( ! $collection->getSize() || ! $collection->hasFlag(self::FLAG_MAX_TIMESTAMP))
            return;
        
        $ids = array();
        foreach ($collection as $product)
            $ids[] = $product->getId();
        $this->resourceDirtyFactory->create()
            ->deleteRows($ids, $collection->getFlag(self::FLAG_MAX_TIMESTAMP))
        ;
    }
    
}
