<?php
namespace Postnl\Ecs\Cron;

class Order extends Common
{
    
    public function execute()
    {
		
        if (! $this->ecsConfigHelper->getIsOrderEnabled()) {
            return $this;
        }
        
        $order = $this->objectManager->create('Postnl\Ecs\Model\Order');
        $orders = $order->getUnprocessedOrders();
		
        if (! $orders->getSize()) {
            return $this;
        }
		
		
        $ordersPerFile = $this->getOrdersPerFile();
		//$ordersPerFile = 2;
		$ordersChunk = [];
		$chunkPartArray = [];
		
		foreach($orders as $key => $orderItem)
		{
			
			if(count($chunkPartArray) < $ordersPerFile) {
				$chunkPartArray[] = $orderItem;
				
				
			}				
			else {
				
				$ordersChunk[] = $chunkPartArray;
				unset($chunkPartArray);
				$chunkPartArray = [];
				
				$chunkPartArray[] = $orderItem;
				
				
			}
			
			
		}
		
		if(count($chunkPartArray) > 0)
			$ordersChunk[] = $chunkPartArray;
		
		
		$errors = [];
		foreach($ordersChunk as $ordersArray) {
			
			
			try {
				$server = $this->ecsHelper->getServerInstance();
				$processor = $this->objectManager->create('Postnl\Ecs\Model\Processor\Order', ['sftp' => $server]);
				$processor->startProcessing();
				
			} catch (\Exception $e) {
				
				$this->_informAdminAboutErrors([$e]);
				
				return $this;
			}
			
			
			
			foreach ($ordersArray as $order) {
				try {
					$processor->processOrder($order);
				} catch (\Exception $e) {
					$errors[] = $e;
				}
			}
			
			try {
				$processor->completeProcessing();
			} catch (\Exception $e) {
				$errors[] = $e;
			}
			
			
			
		}
		
        if(count($errors) > 0)
			$this->_informAdminAboutErrors($errors);
        

        return $this;
    }
}
