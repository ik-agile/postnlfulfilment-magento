<?php
namespace Postnl\Ecs\Cron;

class Product extends Common {
    
    public function execute()
    {
       
		if ( ! $this->ecsConfigHelper->getIsProductEnabled())
            return $this;
        
		
        $productSingleton = $this->objectManager->create('Postnl\Ecs\Model\Product');
        $products = $productSingleton->getUnprocessedProducts();
        if ( ! $products->getSize())
            return $this;
        
        try {
            $server = $this->ecsHelper->getServerInstance();
            $processor = $this->objectManager->create('Postnl\Ecs\Model\Processor\Product', ['sftp' => $server]);
            $processor->startProcessing();
        } catch (\Exception $e) {
            $this->_informAdminAboutErrors(array($e));
            return $this;
        }
        
        $errors = array();
        foreach ($products as $product)
        {
            try {
                $processor->processProduct($product);
            } catch (\Exception $e) {
                $errors[] = $e;
            }
        }
        
        try {
            $processor->completeProcessing();
            $productSingleton->clearUnprocessedProducts($products);
        } catch (\Exception $e) {
            $errors[] = $e;
        }
        
        $this->_informAdminAboutErrors($errors);

        return $this;
    }
    
}