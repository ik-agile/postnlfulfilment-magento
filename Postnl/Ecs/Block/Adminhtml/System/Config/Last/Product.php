<?php
namespace Postnl\Ecs\Block\Adminhtml\System\Config\Last;


class Product extends \Magento\Config\Block\System\Config\Form\Field {

    /**
     * @var \Postnl\Ecs\Model\ProductFactory
     */
    protected $ecsProductFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Postnl\Ecs\Model\ProductFactory $ecsProductFactory,
        array $data = []
    ) {
        $this->ecsProductFactory = $ecsProductFactory;
        parent::__construct($context, $data);
    }
    
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $lastProduct = $this->ecsProductFactory->create()->getLatest();
        return $lastProduct->getId() ? $lastProduct->getFilename() : '<em>' . __('none') . '</em>';
    }
    
}
