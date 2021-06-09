<?php
namespace Postnl\Ecs\Block\Adminhtml;

class Order extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'postnlecs';
        $this->_headerText = __('Exported Orders');
        $this->setUseAjax(true);
        parent::_construct();
        $this->removeButton('add');
    }

}
