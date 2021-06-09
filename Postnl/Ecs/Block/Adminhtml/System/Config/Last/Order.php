<?php
namespace Postnl\Ecs\Block\Adminhtml\System\Config\Last;


class Order extends \Magento\Config\Block\System\Config\Form\Field {

    /**
     * @var \Postnl\Ecs\Model\OrderFactory
     */
    protected $ecsOrderFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Postnl\Ecs\Model\OrderFactory $ecsOrderFactory,
        array $data = []
    ) {
        $this->ecsOrderFactory = $ecsOrderFactory;
        parent::__construct($context, $data);
    }
    
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $lastOrder = $this->ecsOrderFactory->create()->getLatest();
        return $lastOrder->getId() ? $lastOrder->getFilename() : '<em>' . __('none') . '</em>';
    }
    
}
