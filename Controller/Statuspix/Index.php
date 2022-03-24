<?php

namespace Paymee\Core\Controller\Statuspix;

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
        Context        $context,
        OrderFactory   $orderFactory,
        InvoiceService $invoiceService,
        InvoiceSender  $invoiceSender,
        Transaction    $transaction
    )
    {
        $this->orderFactory = $orderFactory;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_helper = $objectManager->create('Paymee\Core\Helper\Data');
        parent::__construct($context);
    }

    public function execute()
    {
        $uuid = $this->getRequest()->getParam('uuid');

        $this->_helper->logs('--- Paymee Check Pix Status ----');
        $this->_helper->logs($uuid);

        $api = new Api();
        $api->setUri("/v1.1/transactions/{$uuid}");
        $api->connect(false);

        $response = $api->getResponse();
        $this->_helper->logs($response);

        if ($response['message'] == 'success' && $response['situation'] == 'PAID') {
            echo 'PAID';
        } else {
            var_dump($response);
        }
    }
}
