<?php
namespace Postnl\Ecs\Model\Processor;


class Stock extends Common {
    
    const MAX_FILES_TO_PROCESS = 1000;

    /**
     * @var \Postnl\Ecs\Helper\Config
     */
    protected $ecsConfigHelper;

    /**
     * @var \Postnl\Ecs\Model\Stock
     */
    protected $ecsStock;

    /**
     * @var \Postnl\Ecs\Model\StockFactory
     */
    protected $ecsStockFactory;

    /**
     * @var \Postnl\Ecs\Model\Stock\RowFactory
     */
    protected $ecsStockRowFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $catalogProductFactory;

    /**
     * @var \Postnl\Ecs\Model\Resource\Stock\Item\CollectionFactory
     */
    protected $catalogInventoryStockItemFactory;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $transactionFactory;
    
    /**
     * @var \Magento\Indexer\Model\IndexerFactory
     */
    protected $indexerFactory;

    public function __construct(
        \Magento\Framework\Filesystem\Io\Sftp $sftp,
        \Postnl\Ecs\Helper\Config $ecsConfigHelper,
        \Postnl\Ecs\Model\Stock $ecsStock,
        \Postnl\Ecs\Model\StockFactory $ecsStockFactory,
        \Postnl\Ecs\Model\Stock\RowFactory $ecsStockRowFactory,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Postnl\Ecs\Model\Resource\Stock\Item\CollectionFactory $catalogInventoryStockItemFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Indexer\Model\IndexerFactory $indexerFactory
    ) {
        $this->ecsConfigHelper = $ecsConfigHelper;
        $this->ecsStock = $ecsStock;
        $this->ecsStockFactory = $ecsStockFactory;
        $this->ecsStockRowFactory = $ecsStockRowFactory;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->catalogInventoryStockItemFactory = $catalogInventoryStockItemFactory;
        $this->transactionFactory = $transactionFactory;
        $this->indexerFactory = $indexerFactory;
        parent::__construct(func_get_args());
    }
    
    public function isEnabled()
    {
        return $this->ecsConfigHelper->getIsStockEnabled();
    }
    
    public function getPath()
    {
        return $this->ecsConfigHelper->getStockPath();
    }
    
    public function checkPath()
    {
        $path = $this->getPath();
        if (empty($path))
            throw new \Postnl\Ecs\Exception(__('Stock path is empty', $path));
            
        $result = $this->_server->cd($path);
        if ( ! $result)
            throw new \Postnl\Ecs\Exception(__('Folder "%1" is missing', $path));
    }
    
    protected function _getAllFiles()
    {
        $this->checkPath();
        $result = $this->_filterFiles($this->_server->ls(), '#.+\.xml$#D');
        return $result;
    }
    
    public function getFiles()
    {
        $files = $this->_getAllFiles();
        if ( ! count($files))
            return array(
                array(),
                array()
            );
        
        $unprocessed = $this->ecsStock->getUnprocessed($files);
        return array(
            array_slice($unprocessed, 0, self::MAX_FILES_TO_PROCESS),
            $this->ecsStock->getAlreadyProcessed($files)
        );
    }
    
    protected function _getFile($filename)
    {
        $file = $this->ecsStockFactory->create()->load($filename, 'filename');
        if ( ! $file->getId())
        {
            $file->setFilename($filename);
        }
        $file->setStatus(\Postnl\Ecs\Model\Stock::STATUS_PENDING);
        $file->save();
        return $file;
    }
    
    protected function _getData(\Postnl\Ecs\Model\Stock $file)
    {
      
		
		$filename = $file->getFilename();
		
        $contents = $this->_server->read($filename);
        if ($contents === false)
            throw new \Postnl\Ecs\Exception(__('Can not read file "%1"', $filename));
        
        $xml = @simplexml_load_string($contents);
        if ($xml === false)
            throw new \Postnl\Ecs\Exception(__('Invalid XML found in "%1"', $filename));
        
        if (isset($xml->messageNo))
            $file->setMessageNumber((string) $xml->messageNo);
        
        $result = array();
        if ( ! isset($xml->Stockupdate))
            return $result;
        
        foreach ($xml->Stockupdate as $update)
        {
            $sku = (string) $update->stockdtl_itemnum;
            $row = $this->ecsStockRowFactory->create();
            $row->setData(array(
                'stock_id' => $file->getId(),
                'status' => \Postnl\Ecs\Model\Stock\Row::STATUS_PENDING,
                'product_id' => $sku,
                'qty' => (float) $update->stockdtl_fysstock,
                'file' => $file,
                'stocks' => [],
                'product' => null,
            ));
            $result[$sku] = $row;
        }
        
        $products = $this->catalogProductFactory->create()->getCollection();
        $products->addAttributeToFilter('sku', array('in' => array_keys($result)));
        $map = array();
        foreach ($products as $product)
        {
            if(isset($result[$product->getSku()]))
            {
                $map[$product->getId()] = $product->getSku();
                //$result[$product->getSku()]->setProduct($product->getId());
                $result[$product->getSku()]->setEntityId($product->getId());
            }

        }
       
		
        $stocks = $this->catalogInventoryStockItemFactory->create();
        $stocks->addFieldToFilter('product_id', array('in' => array_keys($map)));

        $transaction = $this->transactionFactory->create();
        $saveTransaction = false;
        foreach ($stocks as $stock)
        {
            if ( ! isset($map[$stock->getProductId()]))
                continue;
            $existing = $result[$map[$stock->getProductId()]]->getStocks();
            $existing[] = $stock->getProductId();
            $result[$map[$stock->getProductId()]]->setStocks($existing);
            $stockQty = $result[$map[$stock->getProductId()]]->getQty();
            $stock->setProcessIndexEvents(false);
            $stock->setQty($stockQty);
            $transaction->addObject($stock);
            $saveTransaction = true;

        }

        if($saveTransaction)
            $transaction->save();
		
        return $result;
    }
    
    public function parseFile($filename)
    {
        $file = $this->_getFile($filename);
        try {
            $data = $this->_getData($file);
        } catch (Postnl_Ecs_Exception $e) {
            $file->setStatus(\Postnl\Ecs\Model\Stock::STATUS_ERROR);
            $file->save();
            $data = array();
            throw $e;
        }
        return array($file, $data);
    }
    
    public function processRow(\Postnl\Ecs\Model\Stock\Row $row)
    {
        $stocks = [];
        try {
            $product = $row->getEntityId();
			
            if ( ! $product && !empty($product)) {
              /*  throw new \Postnl\Ecs\Exception(__(
                    'Unknown SKU "%1" in file "%2"', 
                    $row->getProductId(),
                    $row->getFile()->getFilename()
                ));*/
				 $row->setStatus(\Postnl\Ecs\Model\Stock\Row::STATUS_PROCESSED);
            return $stocks;
				
				}
            
            if ( ! count($row->getStocks()))
                throw new \Postnl\Ecs\Exception(__(
                    'Missing inventory for SKU "%1" in file "%2"', 
                    $row->getProductId(),
                    $row->getFile()->getFilename()
                ));
				
            /*foreach ($row->getStocks() as $stock)
            {
                $stock->setProcessIndexEvents(false);
                $stock->setQty($row->getQty());
            }*/
                
            $row->setStatus(\Postnl\Ecs\Model\Stock\Row::STATUS_PROCESSED);
            $stocks[] = $product;
        } catch (Postnl_Ecs_Exception $e) {
            $row->setStatus(\Postnl\Ecs\Model\Stock\row::STATUS_ERROR);
            throw $e;
        } catch (Exception $e) {
            $row->setStatus(\Postnl\Ecs\Model\Stock\Row::STATUS_ERROR);
            throw new \Postnl\Ecs\Exception(__(
                'File "%1": %2', 
                $row->getFile()->getFilename(),
                $e->getMessage()
            ));
        }
        return $stocks;
    }
    
    public function completeFile(\Postnl\Ecs\Model\Stock $file, $rows, $stocks)
    {
        $file->setStatus(\Postnl\Ecs\Model\Stock::STATUS_PROCESSED);
        $transaction = $this->transactionFactory->create();
        $transaction->addObject($file);
        foreach ($rows as $row)
            $transaction->addObject($row);
        //foreach ($stocks as $stock)
          //  $transaction->addObject($stock);
        $transaction->save();
        $indexer = $this->indexerFactory->create();
        $indexer->load('cataloginventory_stock');
        $indexer->reindexAll();
        
        $filename = $file->getFilename();
        $result = $this->_server->rm($this->_server->pwd() . '/' . $filename);
        if ( ! $result)
            throw new \Postnl\Ecs\Exception(__('Can not remove file "%1"', $filename));
    }

    public function removeFile($filename)
    {
        $file = $this->_getFile($filename);
        $file->setStatus(\Postnl\Ecs\Model\Stock::STATUS_ERROR);

        $result = $this->_server->rm($this->_server->pwd() . '/' . $filename);
        if ( ! $result)
            throw new \Postnl\Ecs\Exception(__('Can not remove file "%1"', $filename));

    }
    
}
