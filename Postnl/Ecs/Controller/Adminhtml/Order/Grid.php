<?php
namespace Postnl\Ecs\Controller\Adminhtml\Order;
use Magento\Framework\Controller\ResultFactory;

class Grid extends \Postnl\Ecs\Controller\Adminhtml\Order
{
    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Layout $resultLayout */
        $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        return $resultLayout;
    }
}
