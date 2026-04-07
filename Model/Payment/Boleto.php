<?php

namespace Paymee\Core\Model\Payment;

class Boleto extends \Magento\Payment\Model\Method\AbstractMethod
{
    const METHOD_CODE = 'paymee_boleto';

    protected $_code             = self::METHOD_CODE;
    protected $_isGateway        = true;
    protected $_canCapture       = true;
    protected $_isInitializeNeeded = true;
    protected $_infoBlockType    = 'Paymee\Core\Block\Payment\Info\Boleto';

    protected $_helper;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $attributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $attributeFactory,
            $paymentData,
            $scopeConfig,
            $logger
        );

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_helper = $objectManager->create('Paymee\Core\Helper\Data');
    }

    public function initialize($paymentAction, $stateObject)
    {
        throw new \Magento\Framework\Exception\LocalizedException(__('Boleto não implementado'));
    }

    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        return $this;
    }

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        return $this;
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return parent::isAvailable($quote);
    }
}
