<?php
namespace Postnl\Ecs\Cron;

class Stock extends Common {
    
    public function execute()
    {
      
		if ( ! $this->ecsConfigHelper->getIsStockEnabled())
            return $this;
        
        try {
            $server = $this->ecsHelper->getServerInstance();
            $processor = $this->objectManager->create('Postnl\Ecs\Model\Processor\Stock', ['sftp' => $server]);
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
            $processor->completeFile($file, $rows, []);
        }
           
        
        foreach ($files as $file)
        {
            try {
                list($file, $rows) = $processor->parseFile($file);
                $stocks = array();
                $success = true;
                foreach ($rows as $row)
                {
                    try {
                        $stocks = array_merge($stocks, $processor->processRow($row));
                    } catch (\Postnl\Ecs\Exception $e) {
                        $errors[] = $e;
                        $success = false;
                    }
                }


                $processor->completeFile($file, $rows, $stocks);
            } catch (\Postnl\Ecs\Exception $e) {
                $errors[] = $e;
            } catch (\Exception $e) {
                throw $e;
                if (is_object($file))
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