<?php
namespace Postnl\Ecs\Controller\Adminhtml\Order;

use Magento\Framework\App\Filesystem\DirectoryList;

class Export extends \Postnl\Ecs\Controller\Adminhtml\Order
{
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    ) {
        $this->_fileFactory = $fileFactory;
        parent::__construct(
            $context, $coreRegistry, $resultForwardFactory, $resultPageFactory
        );
    }

    /**
     * Export customer grid to CSV format
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $fileName = 'postnl_ecs_orders.csv';

        /** @var \Magento\Backend\Block\Widget\Grid\ExportInterface $exportBlock  */
        $exportBlock = $this->_view->getLayout()->getChildBlock('adminhtml.postnl.ecs.orders.grid', 'grid.export');
        return $this->_fileFactory->create(
            $fileName,
            $exportBlock->getCsvFile(),
            DirectoryList::VAR_DIR
        );
    }

}
