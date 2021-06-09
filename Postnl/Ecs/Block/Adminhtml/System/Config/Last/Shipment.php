<?php
namespace Postnl\Ecs\Block\Adminhtml\System\Config\Last;


class Shipment extends \Magento\Config\Block\System\Config\Form\Field {

    /**
     * @var \Postnl\Ecs\Model\ShipmentFactory
     */
    protected $ecsShipmentFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Postnl\Ecs\Model\ShipmentFactory $ecsShipmentFactory,
        array $data = []
    ) {
        $this->ecsShipmentFactory = $ecsShipmentFactory;
        parent::__construct($context, $data);
    }
    
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $lastShipment = $this->ecsShipmentFactory->create()->getLatest();
        return $lastShipment->getId() ? $lastShipment->getFilename() : '<em>' . __('none') . '</em>';
    }
    
}
