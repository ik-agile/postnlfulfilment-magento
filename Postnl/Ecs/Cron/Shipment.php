<?php
namespace Postnl\Ecs\Cron;

class Shipment extends Common {
    
    public function execute()
    {
        
		if ( ! $this->ecsConfigHelper->getIsShipmentEnabled())
            return $this;
        
        try {
            $server = $this->ecsHelper->getServerInstance();
            $processor = $this->objectManager->create('Postnl\Ecs\Model\Processor\Shipment', ['sftp' => $server]);
            list($files, $skipped) = $processor->getFiles();
        } catch (\Exception $e) {
            $this->_informAdminAboutErrors(array($e));
            return $this;
        }
        
        $errors = array();
        
        foreach ($skipped as $file) {
            $errors[] = new \Postnl\Ecs\Exception(__(
                'File "%1" was already processed.', 
                $file
            ));
            list($file, $rows) = $processor->parseFile($file);
            $processor->completeFile($file, [], [], []);
        }
            
        
        foreach ($files as $file)
        {
            try {
                list($file, $rows) = $processor->parseFile($file);
                $orders = array();
                $shipments = array();
                $success = true;
                foreach ($rows as $row)
                    try {
                        list($order, $shipment) = $processor->processRow($row);
                        $orders[$order->getId()] = $order;
						if($shipment) 
							$shipments[] = $shipment;
                    } catch (\Postnl\Ecs\Exception $e) {
                        $success = false;
                        $errors[] = $e;
                    }
                if ($success)
                    $processor->completeFile($file, $rows, $orders, $shipments);
            } catch (\Postnl\Ecs\Exception $e) {
                $errors[] = $e;
            } catch (\Exception $e) {
                if ($file)
                    $errors[] = new \Postnl\Ecs\Exception(__(
                        'File "%1": %2', 
                        $file->getFilename(),
                        $e->getMessage()
                    ));
                else
                    $errors[] = $e;
            }
        }
        
        try {
            $processor->restorePath();
        } catch (\Exception $e) {
            $errors[] = $e;
            return $this;
        }
        
        $this->_informAdminAboutErrors($errors);
        
        return $this;
    }
    
}