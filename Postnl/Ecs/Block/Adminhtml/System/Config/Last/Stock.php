<?php
namespace Postnl\Ecs\Block\Adminhtml\System\Config\Last;


class Stock extends \Magento\Config\Block\System\Config\Form\Field {

    /**
     * @var \Postnl\Ecs\Model\StockFactory
     */
    protected $ecsStockFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Postnl\Ecs\Model\StockFactory $ecsStockFactory,
        array $data = []
    ) {
        $this->ecsStockFactory = $ecsStockFactory;
        parent::__construct($context, $data);
    }
    
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $lastStock = $this->ecsStockFactory->create()->getLatest();
        return $lastStock->getId() ? $lastStock->getFilename() : '<em>' . __('none') . '</em>';
    }
    
}
