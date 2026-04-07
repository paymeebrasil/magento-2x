<?php

namespace Paymee\Core\Block\Checkout;

class Success extends \Magento\Framework\View\Element\Template
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
    ) {
        $this->_orderFactory   = $orderFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_scopeConfig    = $scopeConfig;
        $this->_pricingHelper  = $pricingHelper;
        parent::__construct($context, $data);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_helper = $objectManager->create('Paymee\Core\Helper\Data');

        $this->setTemplate('checkout/success.phtml');
    }

    /**
     * @return \Magento\Sales\Model\Order\Payment
     */
    public function getPayment()
    {
        return $this->getOrder()->getPayment();
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        $orderIncrementId = $this->_checkoutSession->getLastRealOrderId();
        return $this->_orderFactory->create()->loadByIncrementId($orderIncrementId);
    }

    /**
     * Returns formatted grand total with currency symbol.
     */
    public function getOrderTotalCurrency(): string
    {
        return (string)$this->_pricingHelper->currency(
            $this->getOrder()->getGrandTotal(),
            true,
            false
        );
    }

    /**
     * @return string
     */
    public function getTotal(): string
    {
        $order = $this->getOrder();
        $total = $order->getBaseGrandTotal();

        if (!$total) {
            $total = $order->getBasePrice() + $order->getBaseShippingAmount();
        }

        return number_format((float)$total, 2, '.', '');
    }

    /**
     * @return int|null
     */
    public function getEntityId()
    {
        return $this->getOrder()->getEntityId();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPaymentMethod(): string
    {
        return $this->getPayment()->getMethodInstance()->getCode();
    }

    /**
     * @return string
     */
    public function getOrderUrl(): string
    {
        $params = ['order_id' => $this->_checkoutSession->getLastRealOrder()->getId()];
        return $this->_urlBuilder->getUrl('sales/order/view', $params);
    }

    /**
     * @return string
     */
    public function getReOrderUrl(): string
    {
        $params = ['order_id' => $this->_checkoutSession->getLastRealOrder()->getId()];
        return $this->_urlBuilder->getUrl('sales/order/reorder', $params);
    }

    /**
     * @return string
     */
    public function getPaymeeUrlPixStatus(): string
    {
        return $this->getUrl('paymee/statuspix/');
    }
}
