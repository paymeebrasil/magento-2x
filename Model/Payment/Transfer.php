<?php

namespace Paymee\Core\Model\Payment;

use Paymee\Core\Model\Api;

class Transfer extends \Magento\Payment\Model\Method\AbstractMethod
{
    const METHOD_CODE        = 'paymee_transfer';
    protected $_code         = self::METHOD_CODE;

    protected $_isGateway = true;
    protected $_canCapture = true;
    protected $_isInitializeNeeded = true;
    protected $_cart;
    protected $_helper;
    protected $_infoBlockType = 'Paymee\Core\Block\Payment\Info\Transfer';
    protected $_urlInterface;
    protected $_customerRepositoryInterface;

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
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
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
        $payment    = $this->getInfoInstance();
        $order      = $payment->getOrder();
        $cpf        = $order->getBillingAddress()->getVatId(); //campo vat_id do billingAddress
        $customerId = $order->getCustomerId();

        $this->_helper->logs("--- Payee Transfer ----");
        $this->_helper->logs("cpf: {$cpf}");

        if (isset($customerId)) {
            $_customer      = $this->_customerRepositoryInterface->getById($customerId);
            $customerId     = ($_customer->getId() !== null) ? $_customer->getId() : $customerId;
        }

        $firstName      = $order->getData('customer_firstname');
        $lastname       = $order->getData('customer_lastname');
        $email          = $order->getData('customer_email');
        $phone          = $order->getBillingAddress()->getTelephone();
        $name           = $firstName . " " . $lastname;
        $grandTotal     = round($order->getGrandTotal(), 2);
        $referenceCode  = $order->getIncrementId();
        $maxAge         = $this->_helper->getPaymeeTransferMaxage();
        $bank           = $this->getInfoInstance()->getAdditionalInformation('transfer_bank');
        $agencia        = $this->getInfoInstance()->getAdditionalInformation('transfer_branch');
        $conta          = $this->getInfoInstance()->getAdditionalInformation('transfer_account');

        $paymentMethod  = $this->_helper->getBanco($bank);

        $_data = array(
            "currency"      => "BRL",
            "amount"        => (float)$grandTotal,
            "referenceCode" => $referenceCode,
            "maxAge"        => $maxAge,
            "paymentMethod" => $paymentMethod,
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
                "bankDetails" => array(
                    "branch"    => $agencia,
                    "account"   => $conta,
                )
            )
        );

        $this->_helper->logs("--- Payee Transfer Data ----");
        $this->_helper->logs($_data);

        $api = new Api();
        $api->setUri("/v1.1/checkout/transparent/");
        $api->setData($_data);
        $api->connect();

        $response = $api->getResponse();
        $this->_helper->logs($response);

        if (isset($response['message']) && ($response['message'] == "success")) {
            try {
                $referenceCode          = $response['response']['referenceCode'];
                $uuid                   = $response['response']['uuid'];
                $name                   = $response['response']['instructions']['name'];
                $beneficiary_branch     = $response['response']['instructions']['beneficiary_branch'];
                $beneficiary_account    = $response['response']['instructions']['beneficiary_account'];
                $beneficiary_name       = $response['response']['instructions']['beneficiary_name'];
                $beneficiary_url        = $response['response']['instructions']['redirect_urls']['desktop'];
                $beneficiary_value      = number_format($response['response']['amount'], 2, ',', '.');

                $payment
                    ->setAdditionalInformation("paymeeReferenceCode", $referenceCode)
                    ->setAdditionalInformation("paymeeUuid", $uuid)
                    ->setAdditionalInformation("instructions_name", $name)
                    ->setAdditionalInformation("instructions_beneficiary_branch", $beneficiary_branch)
                    ->setAdditionalInformation("instructions_beneficiary_account", $beneficiary_account)
                    ->setAdditionalInformation("instructions_beneficiary_name", $beneficiary_name)
                    ->setAdditionalInformation("instructions_beneficiary_url", $beneficiary_url)
                    ->setAdditionalInformation("instructions_beneficiary_value", $beneficiary_value);

            } catch (Exception $e) {
                $this->_helper->logs('Paymee Transfer Error Payment Save: ' . $e->getMessage());
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
