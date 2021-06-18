<?php
namespace Postnl\Ecs\Model\Processor;

class Order extends Common {
    
    protected $_xml;
    protected $_ordersNode;
    
    protected $_file;
    
    protected $_rows;
    
    const MIN_ADDRESS_LINES = 3;
	
    
    const XML_PATH_CUSTOMER_ADDRESS_LINES = 'customer/address/street_lines';

	protected $_moduleManager;
		
    /**
     * @var \Postnl\Ecs\Helper\Config
     */
    protected $ecsConfigHelper;

    /**
     * @var \Postnl\Ecs\Model\OrderFactory
     */
    protected $ecsOrderFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Postnl\Ecs\Model\Order\RowFactory
     */
    protected $ecsOrderRowFactory;

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
        \Postnl\Ecs\Model\OrderFactory $ecsOrderFactory,
		\Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Postnl\Ecs\Model\Order\RowFactory $ecsOrderRowFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
		\Magento\Framework\Module\Manager $moduleManager,
		\Magento\Directory\Model\CountryFactory $countryFactory
		
    ) {
        
		$this->ecsConfigHelper = $ecsConfigHelper;
        $this->ecsOrderFactory = $ecsOrderFactory;
        $this->scopeConfig = $scopeConfig;
		$this->_objectManager = $objectManager;
		 $this->_moduleManager = $moduleManager;
        $this->ecsOrderRowFactory = $ecsOrderRowFactory;
        $this->transactionFactory = $transactionFactory;
        $this->timezone = $timezone;
        $this->countryFactory = $countryFactory;
        parent::__construct(func_get_args());
    }
    
    public function isEnabled()
    {
        return $this->ecsConfigHelper->getIsOrderEnabled();
    }
    
    public function getPath()
    {
        return $this->ecsConfigHelper->getOrderPath();
    }
    
    public function checkPath()
    {
        $path = $this->getPath();
        if (empty($path))
            throw new \Postnl\Ecs\Exception(__('Order path is empty.', $path));
            
        $result = $this->_server->cd($path);
        if ( ! $result)
            throw new \Postnl\Ecs\Exception(__('Folder "%1" is missing.', $path));
    }
    
    protected function _createFile()
    {
        $model = $this->ecsOrderFactory->create();
        $model->setStatus(\Postnl\Ecs\Model\Order::STATUS_PENDING);
        $model->save();
        
        $this->_file = $model;
    }
    
    protected function _createXml()
    {
        $xml = new \DOMDocument('1.0');
	
        $message = $xml->createElementNS('http://www.toppak.nl/deliveryorder_new','message');
        
        $xml->appendChild($message);
        
        $message->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','type', 'deliveryOrder'));
        $message->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','messageNo', $this->_file->getId()));
        list($date, $time) = explode(' ', $this->_file->getCreatedAt());
        $message->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','date', $date));
        $message->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','time', $time));
        
        $orders = $xml->createElementNS('http://www.toppak.nl/deliveryorder_new','deliveryOrders');
        $message->appendChild($orders);
        
        $this->_xml = $xml;
        $this->_ordersNode = $orders;
    }
    
    public function startProcessing()
    {
        $this->_createFile();
        $this->_createXml();
        
        $this->_rows = array();
    }
    
    protected function _getBadCharacters()
    {
        return array(
            ';',
            '\\',
            '`',
            '\'',
            '"',
            '&',
            '*',
            '{',
            '}',
            '[',
            ']',
            '!',
            '<',
            '>'

        );
    }
    
    protected function _cleanupString($string, $maxLength = 0)
    {
        $trimmed = trim(preg_replace('#\s+#us', ' ', str_replace($this->_getBadCharacters(), '', $string)));
        if ($maxLength && mb_strlen($string, 'UTF-8') > $maxLength)
            $trimmed = mb_substr($string, 0, $maxLength, 'UTF-8');
        return $trimmed;
    }
    
    protected function _streetParts()
    {
        return array(
            \Postnl\Ecs\Model\System\Config\Source\Address::FIELD_STREET,
            \Postnl\Ecs\Model\System\Config\Source\Address::FIELD_HOUSE,
            \Postnl\Ecs\Model\System\Config\Source\Address::FIELD_ANNEX,
        );
    }
    
    protected function _exportAddress($node, \Magento\Sales\Model\Order\Address $address, $prefix)
    {
        $config = $this->ecsConfigHelper;
        $xml = $this->_xml;
        
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new',$prefix . 'Title', 
            $this->_cleanupString($address->getPrefix(), 10)
        ));
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new',$prefix . 'FirstName', 
            $this->_cleanupString($address->getFirstname(), 35)
        ));
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new',$prefix . 'LastName', 
            $this->_cleanupString(
                trim($address->getMiddlename() . ' ' . $address->getLastname(). ' ' . $address->getSuffix()),
            35)
        ));
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new',$prefix . 'CompanyName', 
            $this->_cleanupString($address->getCompany(), 35)
        ));
        
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new',$prefix . 'BuildingName', 
            ''
        ));
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new',$prefix . 'Department', 
            ''
        ));
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new',$prefix . 'Floor', 
            ''
        ));
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new',$prefix . 'Doorcode', 
            ''
        ));
        
        $street = $address->getStreet();
        $parts = array(
            0 => $config->getAddressLine1(),
            1 => $config->getAddressLine2(),
            2 => $config->getAddressLine3(),
        );
        $lengths = array(
            \Postnl\Ecs\Model\System\Config\Source\Address::FIELD_STREET => 95,
            \Postnl\Ecs\Model\System\Config\Source\Address::FIELD_HOUSE => 5,
            \Postnl\Ecs\Model\System\Config\Source\Address::FIELD_ANNEX => 35,
        );
        $strNrExt = '';
		foreach ($this->_streetParts() as $part)
        {
            $idx = array_search($part, $parts);
            $value = $idx !== false && isset($street[$idx]) ? $street[$idx] : '';
            if(empty($value))
				continue;
			
			if(empty($strNrExt))
				$strNrExt = $value;
			else
				$strNrExt = $strNrExt.' '.$value;
            /*$node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new',$prefix . $part, 
                $this->_cleanupString($value, $lengths[$part])
            ));*/
        }
        
		
		
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new',$prefix . 'PostalCode', 
            $this->_cleanupString($address->getPostcode(), 10)
        ));
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new',$prefix . 'City', 
            $this->_cleanupString($address->getCity(), 30)
        ));
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new',$prefix . 'CountryCode', 
            $this->_cleanupString($address->getCountryId())
        ));
        $countryModel = $this->countryFactory->create()->load($address->getCountryId());
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new',$prefix . 'Country', 
            $this->_cleanupString($countryModel->getName(), 30)
        ));
        
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new',$prefix . 'Phone', 
            $this->_cleanupString($address->getTelephone(), 17)
        ));
        
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new',$prefix . 'StreetHouseNrExt', 
            $this->_cleanupString($strNrExt, 100)
        ));
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new',$prefix . 'Area', 
            ''
        ));
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new',$prefix . 'Region', 
            $this->_cleanupString($address->getRegion(), 35)
        ));
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new',$prefix . 'Remark', 
            ''
        ));
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new',$prefix . 'Email', 
            $this->_cleanupString($address->getOrder()->getCustomerEmail(), 50)
        ));
    }
    
    protected function _exportItems($items)
    {
        $xml = $this->_xml;
        $node = $xml->createElementNS('http://www.toppak.nl/deliveryorder_new','deliveryOrderLines');
        foreach ($items as $item)
        {
            if ($item->isDummy(true))
                continue;
            
            $line = $xml->createElementNS('http://www.toppak.nl/deliveryorder_new','deliveryOrderLine');
            $line->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','itemNo', 
                $item->getSku()
            ));
            $productName = preg_replace('/[^A-Za-z0-9 .]/u','', strip_tags($item->getName()));
            $line->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','itemDescription', 
                 $this->_cleanupString($productName, 255)
            ));
            $line->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','quantity', 
                $item->getQtyOrdered() * 1
            ));
            $line->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','singlePriceInclTax', 
                $item->getBasePriceInclTax() * 1
            ));
            $node->appendChild($line);
        }
        return $node;
    }
    
    protected function _processOrder(\Magento\Sales\Model\Order $order)
    {
        $xml = $this->_xml;
        $node = $xml->createElementNS('http://www.toppak.nl/deliveryorder_new','deliveryOrder');

        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','orderNo', 
            $order->getIncrementId()
        ));
		
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','webOrderNo', 
            $order->getIncrementId()
        ));
		
        $createdAt = new \DateTime($order->getCreatedAt());
        $orderTimezone = $this->timezone->getConfigTimezone('store', $order->getStore());
        $createdAtStore = (new \DateTime(null, new \DateTimeZone($orderTimezone)))->setTimestamp($createdAt->getTimestamp());
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','orderDate', 
            $createdAtStore->format('Y-m-d')
        ));
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','orderTime', 
            $createdAtStore->format('H:i:s')
        ));
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','customerNo', 
            $order->getCustomerId() ? $order->getCustomerId() : ''
        ));
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','onlyHomeAddress', 
            'false'
        ));
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','vendorNo', 
            ''
        ));
        
		$deliveryDate = '';
        $shippingCode = '';
		$postNLcheck = $this->_moduleManager->isEnabled('TIG_PostNL');
		if ($postNLcheck) {
			
			$PostNlOrder = $this->_objectManager->create('TIG\PostNL\Model\OrderRepository')->getByOrderId($order->getIncrementId());
			if($PostNlOrder) {
                $postNLshippingCode = $PostNlOrder->getProductCode();
                $postNLOrderType = $PostNlOrder->getType();
                $postnlIsPG = $PostNlOrder->getIsPakjegemak();
                $shippingCode = '0'.$postNLshippingCode.'_'.$postNLOrderType;


                if($PostNlOrder->getIsStatedAddressOnly())
                    $shippingCode = $shippingCode.'_SAO';



                $deliveryDate = $PostNlOrder->getDeliveryDate() ? date('Y-m-d',strtotime($PostNlOrder->getDeliveryDate())) : '';


			}
			    
			
			
        }+
       
		
        $this->_exportAddress($node, $order->getShippingAddress(), 'shipTo');
        
        $this->_exportAddress($node, $order->getBillingAddress(), 'invoiceTo');
        
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','language', 
            strtoupper(substr($this->scopeConfig->getValue('general/locale/code', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $order->getStore()), 0, 2))
        ));
        
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','remboursAmount', 
            ''
        ));

        $carrier = $order->getShippingCarrier();
        $carrierCode = $carrier ? $carrier->getConfigData('title') : $order->getShippingMethod();
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shippingAgentCode',
            empty($shippingCode) ? $carrierCode : $shippingCode
        ));
        
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipmentType', 
            ''
        ));
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipmentProductOption', 
            ''
        ));
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipmentOption', 
            ''
        ));
		
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','receiverDateOfBirth', 
            $order->getCustomerDob() ? current(explode(' ', $order->getCustomerDob())) : ''
			//''
        ));
		
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','IDExpiration', 
            ''
        ));
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','IDNumber', 
            ''
        ));
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','IDType', 
            ''
        ));
        
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','requestedDeliveryDate', 
            $deliveryDate
        ));
        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','requestedDeliveryTime', 
            ''
        ));
        $comment = $xml->createElementNS('http://www.toppak.nl/deliveryorder_new','comment');
        $comment->appendChild($xml->createCDATASection(
            $order->getCustomerNote() ? $this->_cleanupString($order->getCustomerNote(), 255) : ''
        ));
        $node->appendChild($comment); 
        
        $node->appendChild($this->_exportItems($order->getItemsCollection()));
        
        return $node;
    }
    
    public function processOrder(\Magento\Sales\Model\Order $order)
    {
        $row = $this->ecsOrderRowFactory->create();
        $row->setOrderId($this->_file->getId());
        $row->setEntityId($order->getId());

        $this->_ordersNode->appendChild($this->_processOrder($order));
       
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
        if(function_exists('libxml_use_internal_errors'))
            libxml_use_internal_errors(true);
        $is_valid_xml = true;

        if($this->ecsConfigHelper->getOrderXsd()) {
            $is_valid_xml = $this->_xml->schemaValidate($this->ecsConfigHelper->getOrderXsd());
           
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
               
                throw new \Postnl\Ecs\Exception(__('Order XML is invalid', $validationError));
                
        }

        if(function_exists('libxml_use_internal_errors')) {
             libxml_clear_errors();
             libxml_use_internal_errors(false); 
        }
       
        
        //End check XSD
		
		//Temp save 
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
		
		$tmpPath = $directory->getPath('tmp'); 
		
		file_put_contents($tmpPath.'/'.$this->_file->getFilename(),$this->_xml->saveXml());
		
		//
        if (empty($path))
            throw new \Postnl\Ecs\Exception(__('Order path is empty', $path));
            
        $result = $this->_server->cd($path);
        if ( ! $result)
            throw new \Postnl\Ecs\Exception(__('Folder "%1" is missing', $path));
        
        if ( ! $this->_server->write($this->_file->getFilename(), $this->_xml->saveXml()))
            throw new \Postnl\Ecs\Exception(__('Can not write orders file', $path));
        
        $this->restorePath();
    }
    
    protected function _saveData()
    {
        $this->_file->setStatus(\Postnl\Ecs\Model\Order::STATUS_UPLOADED);
        
        $transaction = $this->transactionFactory->create();
        $transaction->addObject($this->_file);
        foreach ($this->_rows as $row)
            $transaction->addObject($row);
            
        $transaction->save();
    }
    
    public function completeProcessing()
    {
        if ( ! count($this->_rows))
            throw new \Postnl\Ecs\Exception(__('No orders found'));
        try {
            $this->_uploadXml();
        } catch (Postnl_Ecs_Exception $e) {
            $this->_file->setStatus(\Postnl\Ecs\Model\Order::STATUS_ERROR);
            $this->_file->save();
            throw $e;
        }
        $this->_saveData();
    }
    
    public function checkAddressLinesCount()
    {
        $lines = $this->scopeConfig->getValue(self::XML_PATH_CUSTOMER_ADDRESS_LINES, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($lines < self::MIN_ADDRESS_LINES)
             throw new \Postnl\Ecs\Exception(__('You need to have at least %1 address lines for Street, House No and Annex.', self::MIN_ADDRESS_LINES));
    }
    
    public function getXml()
    {
        return $this->_xml->saveXml();
    }

    protected function getPostnlMapCode()
    {

    }

    protected function shippingCodeMap()
    {
        return
            [
                'Daytime' => 'Overdag',
                'Evening' => 'Avond',
                'Sunday' => 'Sunday',
                'PG' => 'PG',
                'PGE' => 'PGE',
                'EPS' => 'EPS',
                'GP' => 'GP',
                'Extra@Home' => 'Extra@Home',
                'Letterbox Package' => 'Letterbox Package'

            ];
    }
    
}
