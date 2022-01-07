<?php
namespace Postnl\Ecs\Model\Processor;

class Product extends Common {
    
    protected $_xml;
    protected $_productsNode;
    
    protected $_file;
    
    protected $_rows;
    
    /**
     * @var \Postnl\Ecs\Helper\Config
     */
    protected $ecsConfigHelper;

    /**
     * @var \Postnl\Ecs\Model\ProductFactory
     */
    protected $ecsProductFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Postnl\Ecs\Model\Product\RowFactory
     */
    protected $ecsProductRowFactory;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $transactionFactory;
    
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    
    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $countryFactory;

    public function __construct(
        \Magento\Framework\Filesystem\Io\Sftp $sftp,
        \Postnl\Ecs\Helper\Config $ecsConfigHelper,
        \Postnl\Ecs\Model\ProductFactory $ecsProductFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Postnl\Ecs\Model\Product\RowFactory $ecsProductRowFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Directory\Model\CountryFactory $countryFactory
    ) {
        $this->ecsConfigHelper = $ecsConfigHelper;
        $this->ecsProductFactory = $ecsProductFactory;
        $this->scopeConfig = $scopeConfig;
        $this->ecsProductRowFactory = $ecsProductRowFactory;
        $this->transactionFactory = $transactionFactory;
        $this->timezone = $timezone;
        $this->countryFactory = $countryFactory;
        parent::__construct(func_get_args());
    }
    
    public function isEnabled()
    {
        return $this->ecsConfigHelper->getIsProductEnabled();
    }
    
    public function getPath()
    {
        return $this->ecsConfigHelper->getProductPath();
    }
    
    public function checkPath()
    {
        $path = $this->getPath();
        if (empty($path))
            throw new \Postnl\Ecs\Exception(__('Product path is empty.', $path));
            
        $result = $this->_server->cd($path);
        if ( ! $result)
            throw new \Postnl\Ecs\Exception(__('Folder "%1" is missing.', $path));
    }
    
    protected function _createFile()
    {
        $model = $this->ecsProductFactory->create();
        $model->setStatus(\Postnl\Ecs\Model\Product::STATUS_PENDING);
        $model->save();
        
        $this->_file = $model;
    }
    
    protected function _createXml()
    {
        $xml = new \DOMDocument('1.0');
	
        $message = $xml->createElementNS("http://www.toppak.nl/item", 'message');
        $xml->appendChild($message);
        
        $message->appendChild($xml->createElementNS("http://www.toppak.nl/item",'type', 'item'));
        $message->appendChild($xml->createElementNS("http://www.toppak.nl/item",'messageNo', $this->_file->getId()));
        list($date, $time) = explode(' ', $this->_file->getCreatedAt());
        $message->appendChild($xml->createElementNS("http://www.toppak.nl/item",'date', $date));
        $message->appendChild($xml->createElementNS("http://www.toppak.nl/item",'time', $time));
        
        $products = $xml->createElementNS("http://www.toppak.nl/item",'items');
        $message->appendChild($products);
        
        $this->_xml = $xml;
        $this->_productsNode = $products;
    }
    
    public function startProcessing()
    {
        $this->_createFile();
        $this->_createXml();
        
        $this->_rows = array();
    }
    
    protected function _processProduct(\Magento\Catalog\Model\Product $product)
    {
        $xml = $this->_xml;
        $node = $xml->createElementNS("http://www.toppak.nl/item",'item');
        
        $stockItem = $product->getStockItem();
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'itemNo', 
             $this->_cleanupString($product->getSku(), 24)
        ));
        
        $productName = preg_replace('/[^A-Za-z0-9 .]/u','', strip_tags($product->getName()));
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'description', 
            $this->_cleanupString($productName, 35)
        ));

		$productName2 = substr($productName, 35, strlen($productName));
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'description2', 
            $this->_cleanupString($productName2, 36)
        ));
/*        
        $productDescription = preg_replace('/[^A-Za-z0-9 .]/u','', strip_tags($product->getShortDescription()));
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'description2', 
            $this->_cleanupString($productDescription, 35)
        ));
*/        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'unitOfMeasure', 
            $product->getUnitOfMeasure()
                ? $this->_cleanupString($product->getUnitOfMeasure(), 10)
                : 'ST'
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'height', 
            $product->getHeight()
                ? $this->_cleanupString($product->getHeight(), 255)
                : '1'
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'width', 
            $product->getWidth()
                ? $this->_cleanupString($product->getWidth(), 255)
                : '1'
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'depth', 
            $product->getDepth()
                ? $this->_cleanupString($product->getDepth(), 255)
                : '1'
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'weight', 
            $product->getWeight()
                ? $this->_getFloat($product->getWeight())
                : '1'
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'vendorItemNo', 
            $product->getVendorItemNo()
                ? $this->_cleanupString($product->getVendorItemNo(), 30)
                : ''
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'eanNo', 
            $product->getEanNo()
                ? $this->_cleanupString($product->getEanNo(), 15)
                : $this->_cleanupString($product->getSku(), 15)
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'bac', 
            $product->getBac()
                ? $this->_cleanupString($product->getBac(), 255)
                : ''
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'validFrom', 
            $product->getValidFrom()
                ? $this->_cleanupString($product->getValidFrom(), 10)
                : ''
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'validTo', 
            $product->getValidTo()
                ? $this->_cleanupString($product->getValidTo(), 10)
                : ''
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'expiry', 
            $product->getExpiry()
                ? $this->_cleanupString($product->getExpiry(), 255)
                : 'false'
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'adr', 
            $product->getAdr()
                ? $this->_cleanupString($product->getAdr(), 255)
                : ''
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'active', 
            $product->getStatus() == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
                ? 'true'
                : 'false'
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'lot', 
            $product->getLot()
                ? $this->_cleanupString($product->getLot(), 255)
                : ''
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'sortOrder', 
            $product->getSortOrder()
                ? (int) $product->getSortOrder()
                : ''
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'minStock', 
            $stockItem->getMinQty() * 1
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'maxStock', 
            $product->getMaxStock()
                ? $this->_getFloat($product->getMaxStock())
                : ''
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'retailPrice', 
            $product->getMsrp()
                ? $this->_getFloat($product->getMsrp())
                : ''
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'purchasePrice', 
            $product->getPrice()
                ? $this->_getFloat($product->getPrice())
                : ''
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'productType', 
            $product->getProductType()
                ? $this->_cleanupString($product->getProductType(), 255)
                : ''
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'defaultMasterProduct', 
            $product->getDefaultMasterProduct()
                ? $this->_cleanupString($product->getDefaultMasterProduct(), 255)
                : 'false'
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'hangingStorage', 
            $product->getHangingStorage()
                ? $this->_cleanupString($product->getHangingStorage(), 255)
                : 'false'
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'backOrder', 
            $stockItem->getBackorders() != \Magento\CatalogInventory\Model\Stock::BACKORDERS_NO
                ? 'true'
                : 'false'
        ));
        
        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'enriched', 
            $product->getEnriched()
                ? $this->_cleanupString($product->getEnriched(), 255)
                : 'true'
        ));
        
        return $node;
    }
    
    public function processProduct(\Magento\Catalog\Model\Product $product)
    {
        $row = $this->ecsProductRowFactory->create();
        $row->setProductId($this->_file->getId());
        $row->setEntityId($product->getId());
        
        $this->_productsNode->appendChild($this->_processProduct($product));
        
        $this->_rows[] = $row;
    }
    
    protected function _uploadXml()
    {
        $path = $this->getPath();

        $xpath = new \DOMXPath($this->_xml);

        foreach( $xpath->query('//*[not(node())]') as $node ) {
            $node->parentNode->removeChild($node);
        }
        $this->_xml->formatOutput = true;

        //Check XSD:
        $is_valid_xml = true;
        if(function_exists('libxml_use_internal_errors'))
            libxml_use_internal_errors(true); 
        if($this->ecsConfigHelper->getProductXsd()) {
            $is_valid_xml = $this->_xml->schemaValidate($this->ecsConfigHelper->getProductXsd());
           
        }
            
     
        if( !$is_valid_xml) {
                
                $validationError = '';
                if(function_exists('libxml_use_internal_errors')) {
                    $errors = libxml_get_errors();
                    foreach ($errors as $error) {
                        $validationError = $validationError.sprintf('XML error "%s" [%d] (Code %d) in %s on line %d column %d' . "\n",
                            $error->message, $error->level, $error->code, $error->file,
                            $error->line, $error->column);
                    }
                   
                    libxml_clear_errors();
                    libxml_use_internal_errors(false); 

                }
                
                throw new \Postnl\Ecs\Exception(__('Product XML is invalid', $validationError));
                
        }

        if(function_exists('libxml_use_internal_errors')) {
             libxml_clear_errors();
             libxml_use_internal_errors(false); 
        }
       
        
        //End check XSD

        if (empty($path))
            throw new \Postnl\Ecs\Exception(__('Product path is empty', $path));
            
        $result = $this->_server->cd($path);
        if ( ! $result)
            throw new \Postnl\Ecs\Exception(__('Folder "%1" is missing', $path));
        
        if ( ! $this->_server->write($this->_file->getFilename(), $this->_xml->saveXml()))
            throw new \Postnl\Ecs\Exception(__('Can not write products file', $path));
        
        $this->restorePath();
    }
    
    protected function _saveData()
    {
        $this->_file->setStatus(\Postnl\Ecs\Model\Product::STATUS_UPLOADED);
        
        $transaction = $this->transactionFactory->create();
        $transaction->addObject($this->_file);
        foreach ($this->_rows as $row)
            $transaction->addObject($row);
            
        $transaction->save();
    }
    
    public function completeProcessing()
    {
        if ( ! count($this->_rows))
            throw new \Postnl\Ecs\Exception(__('No products found'));
        try {
            $this->_uploadXml();
        } catch (Postnl_Ecs_Exception $e) {
            $this->_file->setStatus(\Postnl\Ecs\Model\Product::STATUS_ERROR);
            $this->_file->save();
            throw $e;
        }
        $this->_saveData();
    }
    
    public function getXml()
    {
        return $this->_xml->saveXml();
    }
    
}
