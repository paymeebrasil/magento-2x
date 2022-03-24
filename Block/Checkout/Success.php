<?php
namespace Paymee\Core\Block\Checkout;

class Success
    extends \Magento\Framework\View\Element\Template
{
    protected $_orderFactory;
    protected $_checkoutSession;
    protected $_scopeConfig;
    protected $_helper;
    protected $_pricingHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        array $data = []
    )
    {
        $this->_orderFactory = $orderFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_scopeConfig = $scopeConfig;
        $this->_pricingHelper = $pricingHelper;
        parent::__construct(
            $context,
            $data
        );

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_helper = $objectManager->create('Paymee\Core\Helper\Data');

        $this->setTemplate('checkout/success.phtml');
    }

    /**
     * @return \Magento\Sales\Model\Order\Payment
     */
    public function getPayment()
    {
        $order = $this->getOrder();
        $payment = $order->getPayment();

        return $payment;
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        $orderIncrementId = $this->_checkoutSession->getLastRealOrderId();
        $order = $this->_orderFactory->create()->loadByIncrementId($orderIncrementId);

        return $order;
    }

    public function getOrderTotalCurrency() {
        return $this->_pricingHelper->currency($this->getOrder()->getGrandTotal(),true,false);
    }

    /**
     * @return float|string
     */
    public function getTotal()
    {
        $order = $this->getOrder();
        $total = $order->getBaseGrandTotal();

        if (!$total) {
            $total = $order->getBasePrice() + $order->getBaseShippingAmount();
        }

        $total = number_format($total, 2, '.', '');

        return $total;
    }

    /**
     * @return mixed
     */
    public function getEntityId()
    {
        return $this->getOrder()->getEntityId();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPaymentMethod()
    {
        $payment_method = $this->getPayment()->getMethodInstance()->getCode();

        return $payment_method;
    }

    /**
     * @return array
     */
    public function getInfoPayment()
    {
        $order_id = $this->_checkoutSession->getLastRealOrderId();
        $info_payments = $this->_coreFactory->create()->getInfoPaymentByOrder($order_id);

        return $info_payments;
    }

    /**
     * Return a message to show in success page
     *
     * @param object  $payment
     *
     * @return string
     */
    public function getMessageByStatus($payment)
    {
        $status = $payment['status'] != "" ? $payment['status'] : '';
        $status_detail = $payment['status_detail'] != "" ? $payment['status_detail'] : '';
        $payment_method = $payment['payment_method_id'] != "" ? $payment['payment_method_id'] : '';
        $amount = $payment['transaction_amount'] != "" ? $payment['transaction_amount'] : '';
        $installments = $payment['installments'] != "" ? $payment['installments'] : '';

        return $this->_coreFactory->create()->getMessageByStatus($status, $status_detail, $payment_method, $installments, $amount);
    }

    /**
     * Return a url to go to order detail page
     *
     * @return string
     */
    public function getOrderUrl()
    {
        $params = ['order_id' => $this->_checkoutSession->getLastRealOrder()->getId()];
        $url = $this->_urlBuilder->getUrl('sales/order/view', $params);

        return $url;
    }

    public function getReOrderUrl(){
        $params = ['order_id' => $this->_checkoutSession->getLastRealOrder()->getId()];
        $url = $this->_urlBuilder->getUrl('sales/order/reorder', $params);
        return $url;
    }

    public function getPaymeeUrlPixStatus()
    {
        return $this->getUrl("paymee/statuspix/");
    }
}
