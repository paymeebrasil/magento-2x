<?php

namespace Paymee\Core\Model\Payment;

use Magento\Checkout\Model\Session as CheckoutSession;
use Paymee\Core\Model\Api;

class Pix extends \Magento\Payment\Model\Method\AbstractMethod
{
    const METHOD_CODE        = 'paymee_pix';
    protected $_code         = self::METHOD_CODE;

    protected $_isGateway = true;
    protected $_canCapture = true;
    protected $_isInitializeNeeded = true;
    protected $_cart;
    protected $_helper;
    protected $_infoBlockType = 'Paymee\Core\Block\Payment\Info\Pix';
    protected $_urlInterface;
    protected $_customerRepositoryInterface;
    protected $_checkoutSession;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $attributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        CheckoutSession $checkoutSession
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

        $this->_cart = $cart;
        $this->_urlInterface = $urlInterface;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_helper = $objectManager->create('Paymee\Core\Helper\Data');
        $this->_checkoutSession = $checkoutSession;
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);
        if (!($data instanceof \Magento\Framework\DataObject)) {
            $data = new \Magento\Framework\DataObject($data);
        }

        $infoForm = $data->getData();

        if(isset($infoForm['additional_data'])){
            $infoForm = $infoForm['additional_data'];
        }

        $info = $this->getInfoInstance();
        $info->setAdditionalInformation($infoForm);
    }

    public function initialize($paymentAction, $stateObject)
    {
        $quote      = $this->_checkoutSession->getQuote();
        $order      = $this->getInfoInstance()->getOrder();
        $customerId = $order->getCustomerId();

        if (isset($customerId)) {
            $_customer      = $this->_customerRepositoryInterface->getById($customerId);
            $customerId     = ($_customer->getId() !== null) ? $_customer->getId() : $customerId;
        }

        if ($this->_helper->checkVersionMagento23Less()) {
            $order->setCanSendNewEmailFlag(false);
        }

        $payment = $order->getPayment();

        $fieldCpf = $this->_helper->getPaymeeFieldCpf();
        switch ($fieldCpf) {
            case 'billing':
                $cpf = $order->getBillingAddress()->getVatId();
                break;
            case 'shipping':
                $cpf = $order->getShippingAddress()->getVatId();
                break;
            case 'customer':
                $cpf = $this->_helper->logs($order->getData('customer_taxvat'));
                break;
            case 'paymee':
                $cpf = $this->getInfoInstance()->getAdditionalInformation('pix_cpf');
                break;
        }

        $this->_helper->logs("--- Payee Pix ----");
        $this->_helper->logs("field cpf: {$fieldCpf}");
        $this->_helper->logs("cpf: {$cpf}");

        if (!$cpf || $cpf == null) {
            throw new \Magento\Framework\Exception\LocalizedException(__('O campo CPF não está preenchido'));
        }

        $firstName      = $order->getData('customer_firstname');
        $lastname       = $order->getData('customer_lastname');
        $email          = $order->getData('customer_email');
        $phone          = $order->getBillingAddress()->getTelephone();
        $name           = $firstName . " " . $lastname;
        $grandTotal     = round($order->getGrandTotal(), 2);
        $referenceCode  = $order->getIncrementId();
        $maxAge         = $this->_helper->getPaymeePixMaxage();
        $observation    = $this->_helper->getPaymeeObservation();

        $_data = array(
            "currency"      => "BRL",
            "amount"        => (float)$grandTotal,
            "referenceCode" => $referenceCode,
            "maxAge"        => $maxAge,
            "paymentMethod" => "PIX",
            "callbackURL"   => $this->_urlInterface->getUrl('paymee/webhook/'),
            "shopper" => array(
                "id"        => $customerId,
                "name"      => $name,
                "email"     => $email,
                "document"  => array(
                    "type"      => "CPF",
                    "number"    => $cpf,
                ),
                "phone" => array(
                    "type"      => "MOBILE",
                    "number"    => $phone,
                ),
            ),
            "observation" => $observation
        );

        $this->_helper->logs("--- Payee Pix Data ----");
        $this->_helper->logs($_data);

        $api = new Api();
        $api->setUri("/v1.1/checkout/transparent/");
        $api->setData($_data);
        $api->connect();

        $response = $api->getResponse();
        $this->_helper->logs("--- Payee Return ----");
        $this->_helper->logs($response);

        if (isset($response['message']) && ($response['message'] == "success")) {
            try {
                $referenceCode  = $response['response']['referenceCode'];
                $uuid           = $response['response']['uuid'];
                $qrCodeImg      = $response['response']['instructions']['qrCode']['url'];
                $qrCodeCopy     = $response['response']['instructions']['qrCode']['plain'];

                $payment
                    ->setAdditionalInformation("paymeeReferenceCode", $referenceCode)
                    ->setAdditionalInformation("paymeeUuid", $uuid)
                    ->setAdditionalInformation("paymeeQrCodeImg", $qrCodeImg)
                    ->setAdditionalInformation("paymeeQrCodeCopy", $qrCodeCopy);

            } catch (Exception $e) {
                $this->_helper->logs('Paymee Pix Error Payment Save: ' . $e->getMessage());
                throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__(json_encode($response['errors'])));
        }

        return $this;
    }

    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {

    }

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {

    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null){
        return true;
    }

}
