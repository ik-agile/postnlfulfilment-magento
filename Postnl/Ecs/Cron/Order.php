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
		//$ordersPerFile = 1;
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
				
			}  catch (\Postnl\Ecs\Exception $e) {

                $this->_informAdminAboutErrors([$e]);

                return $this;
            }
			catch (\Exception $e) {
				
				$this->_informAdminAboutErrors([$e]);
				
				return $this;
			} catch (\Error $e)
            {
                $this->_informAdminAboutErrors([$e]);

                return $this;
            }
			
			
			$processedOrders = [];
			foreach ($ordersArray as $order) {
				try {
					$processor->processOrder($order);
					$processedOrders[] = $order;

				} catch (\Postnl\Ecs\Exception $e) {

                    $errors[] = $e;
                } catch (\Exception $e) {
					$errors[] = $e;
				} catch (\Error $e){
                    $errors[] = $e;
                }
			}

			if(empty($processedOrders))
			    continue;
			try {
				$processor->completeProcessing();
			} catch (\Postnl\Ecs\Exception $e) {

                $errors[] = $e;
            }  catch (\Exception $e) {
				$errors[] = $e;
			} catch (\Error $e){
                $errors[] = $e;
            }
			
			
			
		}
		
        if(count($errors) > 0)
			$this->_informAdminAboutErrors($errors);
        

        return $this;
    }
}
