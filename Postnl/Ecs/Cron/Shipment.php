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
        } catch (\Postnl\Ecs\Exception $e) {
            $this->_informAdminAboutErrors(array($e));
            return $this;

        } catch (\Exception $e) {
            $this->_informAdminAboutErrors(array($e));
            return $this;
        } catch (\Error $e){
            $this->_informAdminAboutErrors(array($e));
            return $this;
        }
        
        $errors = array();
        
        foreach ($skipped as $file) {
            try{

                $errors[] = new \Postnl\Ecs\Exception(__(
                    'File "%1" was already processed.',
                    $file
                ));
                list($file, $rows) = $processor->parseFile($file);
                $processor->completeFile($file, [], [], []);

            } catch (\Postnl\Ecs\Exception $e) {
                $errors[] = new \Postnl\Ecs\Exception(__(
                    'Error in processing File "%1" Details: ',
                    $file, $e->getMessage()
                ));

            } catch (\Exception $e) {
                $errors[] = new \Postnl\Ecs\Exception(__(
                    'Error in processing File "%1" Details: ',
                    $file, $e->getMessage()
                ));

            } catch (\Error $e){
                $errors[] = new \Postnl\Ecs\Exception(__(
                    'Error in processing File "%1" Details: ',
                    $file, $e->getMessage()
                ));
            }

        }
            
        
        foreach ($files as $file)
        {
            try {
                list($file, $rows) = $processor->parseFile($file);
                $orders = array();
                $shipments = array();
                $processedRows = [];
                foreach ($rows as $row)
                    try {
                        list($order, $shipment) = $processor->processRow($row);

						if($shipment) {
                            $orders[$order->getId()] = $order;
						    $shipments[] = $shipment;
                            $processedRows[] = $row;
                        }

                    } catch (\Postnl\Ecs\Exception $e) {

                        $errors[] = $e;
                    } catch (\Exception $e) {

                        $errors[] = $e;
                    } catch (\Error $e){
                        $errors[] = $e;
                    }

                if (!empty($shipments))
                    $processor->completeFile($file, $processedRows, $orders, $shipments);
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
            } catch (\Error $e){
                $errors[] = $e;
            }
        }
        
        try {
            $processor->restorePath();
        } catch (\Postnl\Ecs\Exception $e) {
            $errors[] = $e;
        } catch (\Exception $e) {
            $errors[] = $e;

        } catch (\Error $e){
            $errors[] = $e;
        }
        
        if(!empty($errors))
            $this->_informAdminAboutErrors($errors);
        
        return $this;
    }
    
}