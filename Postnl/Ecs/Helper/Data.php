<?php
namespace Postnl\Ecs\Helper;
use \phpseclib\Crypt\RSA;

class Data extends \Magento\Framework\App\Helper\AbstractHelper {
    
    protected $_server = null;

    /**
     * @var \Postnl\Ecs\Helper\Config
     */
    protected $ecsConfigHelper;

    /**
     * @var \Magento\Framework\Filesystem\Io\SftpFactory
     */
    protected $ioSftpFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Postnl\Ecs\Helper\Config $ecsConfigHelper,
        \Magento\Framework\Filesystem\Io\SftpFactory $ioSftpFactory
    ) {
        $this->ioSftpFactory = $ioSftpFactory;
        $this->ecsConfigHelper = $ecsConfigHelper;
        parent::__construct(
            $context
        );
    }

    
    protected function _getKeyType($key)
    {
        return strpos($key, 'PuTTY-User-Key-File-2') !== false
            ? RSA::PRIVATE_FORMAT_PUTTY
			: RSA:: PRIVATE_FORMAT_PKCS1
        ;
    }
    
    protected function _getConnectionParams()
    {
        $config = $this->ecsConfigHelper;
        
        $host = $config->getHostname();
        if (empty($host))
            throw new \Postnl\Ecs\Exception(__('Please specify hostname.'));
        
        $port = (int) $config->getPort();
        if ($port)
            $host .= ':' . $port;
        
        $username = $config->getUsername();
        if (empty($username))
            throw new \Postnl\Ecs\Exception(__('Please specify username.'));
        
        $rawKey = $config->getKey();
        if (empty($rawKey))
            throw new \Postnl\Ecs\Exception(__('Please specify key.'));
        
        $keyPassword = $config->getKeyPassword();
        $key = new RSA();
        if ( ! empty($keyPassword))
            $key->setPassword($keyPassword);
        if ( ! $key->loadKey($rawKey, $this->_getKeyType($rawKey)))
            throw new \Postnl\Ecs\Exception(__('Invalid key format.'));
        
        return array(
            'host' => $host,
            'username' => $username,
            'password' => $key,
        );
    }
    
    public function getServerInstance()
    {
        if ($this->_server === null)
        {
            $this->_server = $this->ioSftpFactory->create();
            try {
                $this->_server->open($this->_getConnectionParams());
            } catch (\Exception $e) {
                throw new \Postnl\Ecs\Exception(__($e->getMessage()));
            }
        }
        
        return $this->_server;
    }
    
}
