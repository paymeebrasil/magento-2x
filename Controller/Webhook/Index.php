<?php

namespace Paymee\Core\Controller\Webhook;

use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Paymee\Core\Controller\AbstractNotification;
use Paymee\Core\Model\Api;

class Index extends AbstractNotification
{
    protected $_helper;
    protected $orderFactory;
    protected $orderRepository;
    protected $invoiceService;
    protected $invoiceRepository;
    protected $transactionFactory;
    protected $invoiceSender;

    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        OrderRepositoryInterface $orderRepository,
        InvoiceService $invoiceService,
        InvoiceRepositoryInterface $invoiceRepository,
        InvoiceSender $invoiceSender,
        TransactionFactory $transactionFactory
    ) {
        $this->orderFactory       = $orderFactory;
        $this->orderRepository    = $orderRepository;
        $this->invoiceService     = $invoiceService;
        $this->invoiceRepository  = $invoiceRepository;
        $this->transactionFactory = $transactionFactory;
        $this->invoiceSender      = $invoiceSender;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_helper = $objectManager->create('Paymee\Core\Helper\Data');

        parent::__construct($context);
    }

    /**
     * Webhook endpoint called by Paymee API when payment status changes.
     * Route: paymee/webhook/
     */
    public function execute()
    {
        $rawBody = file_get_contents('php://input');
        $data    = json_decode($rawBody, true);

        $this->_helper->logs('--- Paymee Webhook ----');
        $this->_helper->logs($data);

        $resultJson = $this->resultFactory->create(
            \Magento\Framework\Controller\ResultFactory::TYPE_JSON
        );

        if (empty($data['newStatus']) || empty($data['referenceCode'])) {
            $this->_helper->logs('Paymee Webhook: missing required fields');
            return $resultJson->setHttpResponseCode(400)->setData(['error' => 'Missing required fields']);
        }

        $orderIncrementId = $data['referenceCode'];
        $saleToken        = $data['saleToken'] ?? null;

        if (!$saleToken) {
            return $resultJson->setHttpResponseCode(400)->setData(['error' => 'Missing saleToken']);
        }

        $api = new Api();
        $api->setUri("/v1.1/transactions/{$saleToken}");
        $api->connect(false);

        $response = $api->getResponse();
        $this->_helper->logs($response);

        if (!isset($response['message']) || $response['message'] !== 'success') {
            return $resultJson->setHttpResponseCode(400)->setData(['error' => 'API verification failed']);
        }

        $status = $response['situation'] ?? null;

        try {
            $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);

            if (!$order->getId()) {
                $this->_helper->logs("Paymee Webhook: order {$orderIncrementId} not found.");
                return $resultJson->setHttpResponseCode(404)->setData(['error' => 'Order not found']);
            }

            $this->changeOrderStatus($order, $status);

            return $resultJson->setHttpResponseCode(200)->setData(['success' => true]);

        } catch (\Exception $e) {
            $this->_helper->logs($e->getMessage());
            return $resultJson->setHttpResponseCode(500)->setData(['error' => $e->getMessage()]);
        }
    }

    public function changeOrderStatus($order, $status)
    {
        $this->_helper->logs(
            "Paymee Webhook: order {$order->getIncrementId()} changing status to {$status}"
        );

        try {
            switch ($status) {
                case 'PAID':
                    $order->setState(Order::STATE_PROCESSING)
                          ->setStatus(Order::STATE_PROCESSING);
                    $this->orderRepository->save($order);

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
                    $this->_helper->logs("Paymee Webhook: unhandled status {$status}");
                    break;
            }

            return true;

        } catch (\Exception $e) {
            $this->_helper->logs($e->getMessage());
            throw $e;
        }
    }

    public function cancelOrder($order)
    {
        try {
            $this->_helper->logs("Paymee Webhook: cancelling order {$order->getIncrementId()}");

            if ($order->canCancel()) {
                $order->cancel();
                $history = $order->addCommentToStatusHistory(
                    __("Paymee: canceled order {$order->getIncrementId()}")
                );
                $history->setIsCustomerNotified(true);
                $this->orderRepository->save($order);

                $this->_helper->logs("Paymee Webhook: order {$order->getIncrementId()} canceled");
            } else {
                $this->_helper->logs("Paymee Webhook: order {$order->getIncrementId()} cannot cancel");
            }

            return true;

        } catch (\Exception $e) {
            $this->_helper->logs($e->getMessage());
            throw $e;
        }
    }

    public function invoiceOrder($order)
    {
        try {
            $this->_helper->logs("Paymee Webhook: generating invoice from order {$order->getIncrementId()}");

            if ($order->canInvoice()) {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->register();
                $invoice->pay();

                $this->invoiceRepository->save($invoice);

                $transaction = $this->transactionFactory->create();
                $transaction->addObject($invoice)
                            ->addObject($invoice->getOrder())
                            ->save();

                $history = $order->addCommentToStatusHistory(
                    __("Paymee: Auto invoiced order {$order->getIncrementId()}")
                );
                $history->setIsCustomerNotified(true);
                $this->orderRepository->save($order);

                $this->_helper->logs("Paymee Webhook: order {$order->getIncrementId()} invoiced successfully");
            } else {
                $this->_helper->logs("Paymee Webhook: cannot invoice order {$order->getIncrementId()}");
            }

            return true;

        } catch (\Exception $e) {
            $this->_helper->logs($e->getMessage());
            throw $e;
        }
    }
}
