<?php
namespace Postnl\Ecs\Cron;

class Common {
    
    /**
     * @var \Postnl\Ecs\Helper\Config
     */
    protected $ecsConfigHelper;
    
     /**
     * @var \Postnl\Ecs\Helper\Data
     */
    protected $ecsHelper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * ImportLogHistoryCleaner constructor.
     *
     * @param \Postnl\Ecs\Helper\Config                          $ecsConfigHelper
     * @param \Postnl\Ecs\Helper\Data                            $ecsHelper
     * @param \Psr\Log\LoggerInterface                           $logger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\ObjectManagerInterface          $objectManager
     */
    public function __construct(
        \Postnl\Ecs\Helper\Config $ecsConfigHelper,
        \Postnl\Ecs\Helper\Data $ecsHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->ecsConfigHelper = $ecsConfigHelper;
        $this->ecsHelper = $ecsHelper;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->objectManager = $objectManager;
    }
    
    protected function _informAdminAboutErrors($errors)
    {
        if ( ! count($errors))
            return;
        
        $messages = array();
		        foreach ($errors as $error)
        {
             
			$this->logger->critical($error);
            $messages[] = $error->getMessage();
			
        }
        
        $email = $this->ecsConfigHelper->getAdminEmail();
        if (empty($email))
            return;
        
        $mail = new \Zend_Mail();
        $mail->setFrom(
            $this->scopeConfig->getValue('trans_email/ident_general/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            $this->scopeConfig->getValue('trans_email/ident_general/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
        );
        $messages = implode('</li><li>', $messages);
        $body = __('<p>Following errors were encountered:</p><ul><li>%1</li></ul>', $messages);
        $mail->addTo($email, $email);
        $mail->setSubject(__('PostNL Ecs Errors Report'));
        $mail->setBodyHtml($body);
        $mail->send();
    }
	
	public function getOrdersPerFile()
	{
		return $this->ecsConfigHelper->getMaxOrdersFile();
	}
    
}
