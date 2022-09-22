<?php

namespace Paymee\Core\Controller\Webhook;

use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Paymee\Core\Controller\AbstractNotification;
use Paymee\Core\Model\Api;

class Index extends AbstractNotification
{

    protected $_helper;
    protected $orderFactory;
    protected $invoiceService;
    protected $transaction;
    protected $invoiceSender;

    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        Transaction $transaction
    )
    {
        $this->orderFactory    = $orderFactory;
        $this->invoiceService   = $invoiceService;
        $this->transaction      = $transaction;
        $this->invoiceSender    = $invoiceSender;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_helper = $objectManager->create('Paymee\Core\Helper\Data');
        parent::__construct($context);
    }

    /**
     * Action to receive webhook from IoPay API
     * Controller /iopay/webhook/
     */
    public function execute()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $this->_helper->logs('--- Paymee Webhook ----');
        $this->_helper->logs($data);

        if (
            $data['newStatus'] &&
            $data['referenceCode'])
        {
            $orderIncrementId   = $data['referenceCode'];
            //$status             = $data['newStatus'];
            $saleToken          = $data['saleToken'];

            $api = new Api();
            $api->setUri("/v1.1/transactions/{$saleToken}");
            $api->connect(false);

            $response = $api->getResponse();
            $this->_helper->logs($response);

            if ($response['message'] == 'success') {
                $status = $response['situation'];
                try {
                    $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
                    if ($order->getId()) {
                        $this->changeOrderStatus($order, $status);
                        http_response_code(200);
                    } else {
                        $this->_helper->logs("Paymee Webhook: order {$orderIncrementId} not found.");
                        echo "Paymee Webhook: order {$orderIncrementId} not found.";
                    }
                } catch(Exception $e) {
                    $this->_helper->logs($e->getMessage());
                    http_response_code(400);
                }
            } else {
                var_dump($response);
            }
        }
    }

    public function changeOrderStatus($order, $status)
    {
        $this->_helper->logs(
            "Paymee Webhook: order {$order->getIncrementId()} changing status to {$status}"
        );
        try {
            $orderStateProcessing = Order::STATE_PROCESSING;

            /* Check webhook status return */
            switch ($status) {
                case 'PAID':
                    $order->setState($orderStateProcessing)->setStatus($orderStateProcessing);
                    $order->save();
                    if ($this->_helper->getPaymeeAutoInvoice()) {
                        $this->invoiceOrder($order);
                    }
                    break;
                case 'CANCELLED':
                case 'failed':
                case 'charged_back':
                    $this->cancelOrder($order);
                    break;
                default:
                    break;
            }

            return true;

        } catch(Exception $e) {
            $this->_helper->logs($e->getMessage());
        }
    }

    public function cancelOrder($order)
    {
        try {
            $this->_helper->logs("Paymee Webhook: cancelling order {$order->getIncrementId()} ");
            if ($order->canCancel()) {
                $order->cancel()->save();

                $order->addCommentToStatusHistory(
                    __("Paymee: canceled order {$order->getIncrementId()}")
                )->setIsCustomerNotified(true)->save();

                $this->_helper->logs("Paymee Webhook: order {$order->getIncrementId()} canceled");
            } else {
                $this->_helper->logs("Paymee Webhook: order {$order->getIncrementId()} cannot cancel");
            }

            return true;

        } catch (Exception $e) {
            $this->_helper->logs($e->getMessage());
        }
    }

    public function invoiceOrder($order)
    {
        try {
            $this->_helper->logs("Paymee Webhook: generating invoice from order {$order->getIncrementId()} ");

            if ($order->canInvoice()) {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->register();
                $invoice->pay(); //fatura como PAID
                $invoice->save();

                $transactionSave =
                    $this->transaction
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder());
                $transactionSave->save();

                //$this->invoiceSender->send($invoice);

                $order->addCommentToStatusHistory(
                    __("Paymee: Auto invoiced order {$order->getIncrementId()}")
                )->setIsCustomerNotified(true)->save();

                $this->_helper->logs("Paymee Webhook: order {$order->getIncrementId()} invoiced successfully");

            } else {
                $this->_helper->logs("Paymee Webhook: cannot invoice order {$order->getIncrementId()}");
            }

            return true;

        } catch (Exception $e) {
            $this->_helper->logs($e->getMessage());
        }
    }
}
