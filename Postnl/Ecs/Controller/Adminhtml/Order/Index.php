<?php
namespace Postnl\Ecs\Controller\Adminhtml\Order;

class Index extends \Postnl\Ecs\Controller\Adminhtml\Order
{
    /**
     * Items list.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax'))
        {
            $this->_forward('grid');
            return;
        }
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Postnl_Ecs::order');
        $resultPage->getConfig()->getTitle()->prepend(__('PostNL ECS Orders'));
        $resultPage->addBreadcrumb(__('PostNL ECS'), __('PostNL ECS'));
        $resultPage->addBreadcrumb(__('Orders'), __('Orders'));
        return $resultPage;
    }
}