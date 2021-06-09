<?php
namespace Postnl\Ecs\Model\Observer;

class CheckServerSettings implements \Magento\Framework\Event\ObserverInterface {
    
    /**
     * @var \Postnl\Ecs\Helper\Config
     */
    protected $ecsConfigHelper;
    
     /**
     * @var \Postnl\Ecs\Helper\Data
     */
    protected $ecsHelper;
    
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    public function __construct(
        \Postnl\Ecs\Helper\Config $ecsConfigHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Postnl\Ecs\Helper\Data $ecsHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->ecsConfigHelper = $ecsConfigHelper;
        $this->messageManager = $messageManager;
        $this->ecsHelper = $ecsHelper;
        $this->objectManager = $objectManager;
    }
    
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $config = $this->ecsConfigHelper;
        if ( ! $config->getIsEnabled())
            return $this;
        
        try {
            $server = $this->ecsHelper->getServerInstance();
        } catch (\Postnl\Ecs\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
            $this->messageManager->addError(__('All export/import routines are disabled.'));
            $config->resetIsEnabled();
            return $this;
        }
        
        $orderProcessor = $this->objectManager->create('Postnl\Ecs\Model\Processor\Order', ['sftp' => $server]);
        if ($orderProcessor->isEnabled())
        {
            try {
                $orderProcessor->checkPath();
                $orderProcessor->checkAddressLinesCount();
                $orderProcessor->restorePath();
            } catch (\Postnl\Ecs\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->messageManager->addError(__('Orders export is disabled.'));
                $config->resetIsOrderEnabled();
            }
        }
        
        $shipmentProcessor = $this->objectManager->create('Postnl\Ecs\Model\Processor\Shipment', ['sftp' => $server]);
        if ($shipmentProcessor->isEnabled())
        {
            try {
                $shipmentProcessor->checkPath();
                $shipmentProcessor->restorePath();
            } catch (\Postnl\Ecs\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->messageManager->addError(__('Shipments import is disabled.'));
                $config->resetIsShipmentEnabled();
            }
        }
        
        $stockProcessor = $this->objectManager->create('Postnl\Ecs\Model\Processor\Stock', ['sftp' => $server]);
        if ($stockProcessor->isEnabled())
        {
            try {
                $stockProcessor->checkPath();
                $stockProcessor->restorePath();
            } catch (\Postnl\Ecs\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->messageManager->addError(__('Stocks import is disabled.'));
                $config->resetIsStockEnabled();
            }
        }
        
        $productProcessor = $this->objectManager->create('Postnl\Ecs\Model\Processor\Product', ['sftp' => $server]);
        if ($productProcessor->isEnabled())
        {
            try {
                $productProcessor->checkPath();
                $productProcessor->restorePath();
            } catch (\Postnl\Ecs\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->messageManager->addError(__('Products import is disabled.'));
                $config->resetIsProductEnabled();
            }
        }
    }
    
}
