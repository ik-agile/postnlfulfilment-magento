<?php
namespace Postnl\Ecs\Helper;


class Config extends \Magento\Framework\App\Helper\AbstractHelper {

    const XML_PATH_GENERAL_ENABLED          = 'ecs/general/enabled';
    const XML_PATH_GENERAL_RETAILER_NAME    = 'ecs/general/retailer_name';
    const XML_PATH_GENERAL_EMAIL            = 'ecs/general/email';
    const XML_PATH_GENERAL_ADDRESS_1        = 'ecs/general/address_1';
    const XML_PATH_GENERAL_ADDRESS_2        = 'ecs/general/address_2';
    const XML_PATH_GENERAL_ADDRESS_3        = 'ecs/general/address_3';
    
    const XML_PATH_SERVER_HOSTNAME      = 'ecs/server/hostname';
    const XML_PATH_SERVER_PORT          = 'ecs/server/port';
    const XML_PATH_SERVER_USERNAME      = 'ecs/server/username';
    const XML_PATH_SERVER_KEY           = 'ecs/server/key';
    const XML_PATH_SERVER_KEY_PASSWORD  = 'ecs/server/key_password';
    
    const XML_PATH_ORDER_ENABLED    = 'ecs/order/enabled';
    const XML_PATH_ORDER_PATH       = 'ecs/order/path';
	const XML_PATH_ORDER_ORDERSFILE       = 'ecs/order/ordersfile';
    const XML_PATH_ORDER_STATUS     = 'ecs/order/status';
    const XML_PATH_ORDER_METHOD     = 'ecs/order/method';
    const XML_PATH_ORDER_EXPR       = 'ecs/order/expr';
    
    const XML_PATH_SHIPMENT_ENABLED     = 'ecs/shipment/enabled';
    const XML_PATH_SHIPMENT_PATH        = 'ecs/shipment/path';
    const XML_PATH_SHIPMENT_INFORM      = 'ecs/shipment/inform_customer';
    const XML_PATH_SHIPMENT_EXPR        = 'ecs/shipment/expr';
    
    const XML_PATH_STOCK_ENABLED    = 'ecs/stock/enabled';
    const XML_PATH_STOCK_PATH       = 'ecs/stock/path';
    const XML_PATH_STOCK_EXPR       = 'ecs/stock/expr';
    
    const XML_PATH_PRODUCT_ENABLED    = 'ecs/product/enabled';
    const XML_PATH_PRODUCT_PATH       = 'ecs/product/path';
    const XML_PATH_PRODUCT_EXPR       = 'ecs/product/expr';

    const XML_PRODUCT_ITEM_XSD =  'item.xsd';

    const XML_ORDER_XSD = 'deliveryOrder_new.xsd';

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        $this->resourceConfig = $resourceConfig;
        parent::__construct(
            $context
        );
    }

    
    protected function _formatPath($path)
    {
        $path = trim(str_replace('\\', '/', $path));
        if ( ! empty($path))
            $path = rtrim($path, '/') . '/';
        return $path;
    }
    
    public function getIsEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_GENERAL_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    public function resetIsEnabled()
    {
        $this->resourceConfig->saveConfig(
            self::XML_PATH_GENERAL_ENABLED,
            0,
            'default',
            0
        );
    }
    
    public function getRetailerName()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_GENERAL_RETAILER_NAME, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getAdminEmail()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_GENERAL_EMAIL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getAddressLine1()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_GENERAL_ADDRESS_1, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getAddressLine2()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_GENERAL_ADDRESS_2, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getAddressLine3()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_GENERAL_ADDRESS_3, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getHostname()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SERVER_HOSTNAME, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getPort()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SERVER_PORT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getUsername()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SERVER_USERNAME, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getKey()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SERVER_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getKeyPassword()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SERVER_KEY_PASSWORD, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getIsOrderEnabled()
    {
        return $this->getIsEnabled() && $this->scopeConfig->isSetFlag(self::XML_PATH_ORDER_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    public function resetIsOrderEnabled()
    {
        $this->resourceConfig->saveConfig(
            self::XML_PATH_ORDER_ENABLED,
            0,
            'default',
            0
        );
    }

    public function getOrderPath()
    {
        return $this->_formatPath($this->scopeConfig->getValue(self::XML_PATH_ORDER_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
    }

    public function getOrderStatus()
    {
        return explode(',', $this->scopeConfig->getValue(self::XML_PATH_ORDER_STATUS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
    }

    public function getOrderMethod()
    {
        return explode(',', $this->scopeConfig->getValue(self::XML_PATH_ORDER_METHOD, \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
    }

    public function getOrderCronExpr()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ORDER_EXPR, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getIsShipmentEnabled()
    {
        return $this->getIsEnabled() && $this->scopeConfig->isSetFlag(self::XML_PATH_SHIPMENT_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    public function resetIsShipmentEnabled()
    {
        $this->resourceConfig->saveConfig(
            self::XML_PATH_SHIPMENT_ENABLED,
            0,
            'default',
            0
        );
    }

    public function getShipmentPath()
    {
        return $this->_formatPath($this->scopeConfig->getValue(self::XML_PATH_SHIPMENT_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
    }

    public function getShipmentInform()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SHIPMENT_INFORM, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getShipmentCronExpr()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SHIPMENT_EXPR, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getIsStockEnabled()
    {
        return $this->getIsEnabled() && $this->scopeConfig->getValue(self::XML_PATH_STOCK_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    public function resetIsStockEnabled()
    {
        $this->resourceConfig->saveConfig(
            self::XML_PATH_STOCK_ENABLED,
            0,
            'default',
            0
        );
    }

    public function getStockPath()
    {
        return $this->_formatPath($this->scopeConfig->getValue(self::XML_PATH_STOCK_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
    }

    public function getStockCronExpr()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_STOCK_EXPR, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    public function getIsProductEnabled()
    {
        return $this->getIsEnabled() && $this->scopeConfig->getValue(self::XML_PATH_PRODUCT_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    public function resetIsProductEnabled()
    {
        $this->resourceConfig->saveConfig(
            self::XML_PATH_PRODUCT_ENABLED,
            0,
            'default',
            0
        );
    }

    public function getProductPath()
    {
        return $this->_formatPath($this->scopeConfig->getValue(self::XML_PATH_PRODUCT_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
    }

    public function getProductCronExpr()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_PRODUCT_EXPR, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getProductXsd()
    {
        if(file_exists(__DIR__.DIRECTORY_SEPARATOR.self::XML_PRODUCT_ITEM_XSD)) {
            return (__DIR__.DIRECTORY_SEPARATOR.self::XML_PRODUCT_ITEM_XSD);
        }
        else 
            return false;
    }
    public function getOrderXsd()
    {
        if(file_exists(__DIR__.DIRECTORY_SEPARATOR.self::XML_ORDER_XSD)) {
            return (__DIR__.DIRECTORY_SEPARATOR.self::XML_ORDER_XSD);
        }
        else 
            return false;
    }
	
	public function getMaxOrdersFile()
	{
		$nooforders = $this->scopeConfig->getValue(self::XML_PATH_ORDER_ORDERSFILE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
		return empty($nooforders) ? 1 : $nooforders;
	}
    
}
