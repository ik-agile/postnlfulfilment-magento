<?php
namespace Postnl\Ecs\Model\Processor;


class Shipment extends Common {

    const MAX_FILES_TO_PROCESS = 1000;

    protected $_orders = array();

    /**
     * @var \Postnl\Ecs\Helper\Config
     */
    protected $ecsConfigHelper;

    /**
     * @var \Postnl\Ecs\Model\Shipment
     */
    protected $ecsShipment;

    /**
     * @var \Postnl\Ecs\Model\ShipmentFactory
     */
    protected $ecsShipmentFactory;

    /**
     * @var \Postnl\Ecs\Model\Shipment\RowFactory
     */
    protected $ecsShipmentRowFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $salesOrderFactory;

    /**
     * @var \Magento\Sales\Model\Order\Shipment\TrackFactory
     */
    protected $salesOrderShipmentTrackFactory;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var \Magento\Sales\Model\Order\ShipmentFactory
     */
    protected $shipmentFactory;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\ShipmentSender
     */
    protected $shipmentSender;

    /**
     * @var \Magento\Sales\Model\Order\CreditmemoFactory
     */
    protected $creditmemoFactory;

    /**
     * @var \Magento\Sales\Model\Service\CreditmemoService
     */
    protected $creditmemoService;

    /**
     * @var \Magento\\Sales\Model\Order\Invoice
     */
    protected $invoice;

    public function __construct(
        \Magento\Framework\Filesystem\Io\Sftp $sftp,
        \Postnl\Ecs\Helper\Config $ecsConfigHelper,
        \Postnl\Ecs\Model\Shipment $ecsShipment,
        \Postnl\Ecs\Model\ShipmentFactory $ecsShipmentFactory,
        \Postnl\Ecs\Model\Shipment\RowFactory $ecsShipmentRowFactory,
        \Magento\Sales\Model\OrderFactory $salesOrderFactory,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $salesOrderShipmentTrackFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory,
        \Magento\Sales\Model\Order\Email\Sender\ShipmentSender $shipmentSender,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory,
        \Magento\Sales\Model\Order\Invoice $invoice,
        \Magento\Sales\Model\Service\CreditmemoService $creditmemoService
    ) {
        $this->ecsConfigHelper = $ecsConfigHelper;
        $this->ecsShipment = $ecsShipment;
        $this->ecsShipmentFactory = $ecsShipmentFactory;
        $this->ecsShipmentRowFactory = $ecsShipmentRowFactory;
        $this->salesOrderFactory = $salesOrderFactory;
        $this->salesOrderShipmentTrackFactory = $salesOrderShipmentTrackFactory;
        $this->transactionFactory = $transactionFactory;
        $this->shipmentFactory = $shipmentFactory;
        $this->shipmentSender = $shipmentSender;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoService = $creditmemoService;
        $this->invoice = $invoice;
        parent::__construct(func_get_args());
    }

    public function isEnabled()
    {
        return $this->ecsConfigHelper->getIsShipmentEnabled();
    }

    public function getPath()
    {
        return $this->ecsConfigHelper->getShipmentPath();
    }

    public function checkPath()
    {
        $path = $this->getPath();
        if (empty($path))
            throw new \Postnl\Ecs\Exception(__('Shipment path is empty', $path));

        $result = $this->_server->cd($path);
        if ( ! $result)
            throw new \Postnl\Ecs\Exception(__('Folder "%1" is missing', $path));
    }

    protected function _getAllFiles()
    {
        $this->checkPath();
        $result = $this->_filterFiles($this->_server->ls(), '#.+\.xml$#D');
        return $result;
    }

    public function getFiles()
    {
        $files = $this->_getAllFiles();
        if ( ! count($files))
            return array(
                array(),
                array()
            );

        $unprocessed = $this->ecsShipment->getUnprocessed($files);
        return array(
            array_slice($unprocessed, 0, self::MAX_FILES_TO_PROCESS),
            $this->ecsShipment->getAlreadyProcessed($files)
        );
    }

    protected function _getFile($filename)
    {
        $file = $this->ecsShipmentFactory->create()->load($filename, 'filename');
        if ( ! $file->getId())
        {
            $file->setFilename($filename);
        }
        $file->setStatus(\Postnl\Ecs\Model\Shipment::STATUS_PENDING);
        $file->save();
        return $file;
    }

    protected function _getData(\Postnl\Ecs\Model\Shipment $file)
    {
        $filename = $file->getFilename();
        $contents = $this->_server->read($filename);

        if ($contents === false)
            throw new \Postnl\Ecs\Exception(__('Can not read file "%1"', $filename));

        $xml = @simplexml_load_string($contents);
        if ($xml === false)
            throw new \Postnl\Ecs\Exception(__('Invalid XML found in "%1"', $filename));

        if (isset($xml->messageNo))
            $file->setMessageNumber((string) $xml->messageNo);

        if (isset($xml->retailerName))
        {
            $retailerName = (string) $xml->retailerName;
            $configRetailerName = $this->ecsConfigHelper->getRetailerName();
            if ( ! empty($configRetailerName) && $retailerName != $configRetailerName)
                throw new \Postnl\Ecs\Exception(__('File "%1" is skipped because of Retailer Name mismatch', $filename));
        }

        $result = array();
        if ( ! isset($xml->orderStatus))
            return $result;

        foreach ($xml->orderStatus as $status)
        {
            $row = $this->ecsShipmentRowFactory->create();
            $items = array();
            if (isset($status->orderStatusLines))
                foreach ($status->orderStatusLines->orderStatusLine as $line)
                    $items[(string) $line->itemNo] = (string) $line->quantity;

            if ( ! count($items))
                throw new \Postnl\Ecs\Exception(__('It seems, that shipping message "%1" was already processed.', $filename));

            $row->setData(array(
                'shipment_id' => $file->getId(),
                'status' => \Postnl\Ecs\Model\Shipment\Row::STATUS_PENDING,
                'order_id' => (string) $status->orderNo,
                'shipment' => $file,
                'tracking_number' => (string) $status->trackAndTraceCode,
                'items' => $items,
            ));
            $result[] = $row;
        }
        return $result;
    }

    public function parseFile($filename)
    {
        $file = $this->_getFile($filename);
        try {
            $data = $this->_getData($file);
        } catch (Postnl_Ecs_Exception $e) {
            $file->setStatus(\Postnl\Ecs\Model\Shipment::STATUS_ERROR);
            $file->save();
            $data = array();
            throw $e;
        }
        return array($file, $data);
    }

    protected function _getOrder($orderId)
    {
        if ( ! isset($this->_orders[$orderId]))
        {
            $this->_orders[$orderId] = $this->salesOrderFactory->create()->loadByIncrementId($orderId);
        }
        return $this->_orders[$orderId];
    }

    public function processRow(\Postnl\Ecs\Model\Shipment\Row $row)
    {

        try {
            $order = $this->_getOrder($row->getOrderId());
            if ( ! $order || ! $order->getId())
                throw new \Postnl\Ecs\Exception(__(
                    'Unknown order number "%1" in file "%2"',
                    $row->getOrderId(),
                    $row->getShipment()->getFilename()
                ));


            $qtys = array();
            $data = $row->getItems();
            $itemCount = 0;
            $cancelCount = 0;

            foreach ($order->getItemsCollection() as $item)
            {
                if ($item->isDummy(true))
                    continue;
                $itemCount += 1;
                $sku = $item->getSku();




                if (isset($data[$sku]) && $data[$sku] > 0)
                {
                    if ( ! isset($qtys[$item->getId()]))
                        $qtys[$item->getId()] = 0;

                    $qtyToShip = min($item->getQtyToShip(), $data[$sku]);
                    if ( ! $qtyToShip)
                        continue;

                    $qtys[$item->getId()] += $qtyToShip;
                    $data[$sku] -= $qtyToShip;
                } else {
                    if(isset($data[$sku])) {
                        if ( $data[$sku] == 0 && $item->getQtyToShip() > 0) {

                            $cancelCount += 1;
                        }
                    }
                    else
                        $cancelCount += 1;
                }



            }


            if ( ! count($qtys)) {
                /*throw new \Postnl\Ecs\Exception(__(
                    'No suitable items found for shipping order "%1" in file "%2"',
                    $row->getOrderId(),
                    $row->getShipment()->getFilename()
                ));*/
                //Qty to Ship is 0 So cancel order.

                if ($cancelCount == $itemCount ) {

                    $invoices = $order->getInvoiceCollection();
                    foreach ($invoices as $invoice) {
                        $invoiceincrementid = $invoice->getIncrementId();
                    }

                    if(isset($invoiceincrementid)) {
                        $invoiceobj = $this->invoice->loadByIncrementId($invoiceincrementid);
                        $creditmemo = $this->creditmemoFactory->createByOrder($order);

                        // Offline refund
                        $creditmemo->setInvoice($invoiceobj);

                        $this->creditmemoService->refund($creditmemo);
                    }

                    $order->registerCancellation('Shipment is 0', true)->save();



                    $row->setStatus(\Postnl\Ecs\Model\Shipment\Row::STATUS_PROCESSED);
                    return array($order, NULL);

                } else {

                    $row->setStatus(\Postnl\Ecs\Model\Shipment\Row::STATUS_PROCESSED);
                    return array($order, NULL);
                    /*	throw new \Postnl\Ecs\Exception(__(
						'No suitable items found for shipping order "%1" in file "%2"', 
						$row->getOrderId(),
						$row->getShipment()->getFilename()
					));*/
                }
            }
            $shipment = $this->shipmentFactory->create(
                $order,
                $qtys, [[
                    'title' => 'PostNL',
                    'number' => $row->getTrackingNumber(),
                    'carrier_code' => 'postnl',
                    'order_id' => $order->getId(),
                ]]
            );





            if (!$shipment->getTotalQty()) {
                $row->setStatus(\Postnl\Ecs\Model\Shipment\Row::STATUS_PROCESSED);
                return array($order, NULL);
                /*throw new \Postnl\Ecs\Exception(__(
                    'No suitable items found for shipping order "%1" in file "%2"', 
                    $row->getOrderId(),
                    $row->getShipment()->getFilename()
                ));*/

            }


            $shipment->register();
            if ( $itemCount == count($qtys)) {
                //complete order


                $order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE, true)->setStatus(\Magento\Sales\Model\Order::STATE_COMPLETE);
                $order->save();


            }

            $row->setStatus(\Postnl\Ecs\Model\Shipment\Row::STATUS_PROCESSED);
            return array($order, $shipment);
        } catch (Postnl_Ecs_Exception $e) {
            $row->setStatus(\Postnl\Ecs\Model\Shipment\Row::STATUS_ERROR);
            throw $e;
        } catch (Exception $e) {
            $row->setStatus(\Postnl\Ecs\Model\Shipment\Row::STATUS_ERROR);
            throw new \Postnl\Ecs\Exception(__(
                'File "%1": %2',
                $row->getShipment()->getFilename(),
                $e->getMessage()
            ));
        }
    }

    public function completeFile(\Postnl\Ecs\Model\Shipment $file, $rows, $orders, $shipments)
    {
        $file->setStatus(\Postnl\Ecs\Model\Shipment::STATUS_PROCESSED);
        $transaction = $this->transactionFactory->create();
        $transaction->addObject($file);
        foreach ($rows as $row)
            $transaction->addObject($row);
        foreach ($orders as $order)
            $transaction->addObject($order);

        if(!empty($shipments)) {
            foreach ($shipments as $shipment)
                $transaction->addObject($shipment);
        }

        $transaction->save();

        if(!empty($shipments)) {
            if ($this->ecsConfigHelper->getShipmentInform()) {

                if(count($shipments)) {
                    foreach ($shipments as $shipment)
                    {
                        if ($shipment->getId())
                            $this->shipmentSender->send($shipment, true);
                    }
                }


            }




        }


        $filename = $file->getFilename();
        $result = $this->_server->rm($this->_server->pwd() . '/' . $filename);
        if ( ! $result)
            throw new \Postnl\Ecs\Exception(__('Can not remove file "%1"', $filename));
    }

}
