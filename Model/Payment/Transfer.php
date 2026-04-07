<?php

namespace Paymee\Core\Model\Payment;

use Magento\Checkout\Model\Session as CheckoutSession;
use Paymee\Core\Model\Api;

class Transfer extends \Magento\Payment\Model\Method\AbstractMethod
{
    const METHOD_CODE = 'paymee_transfer';

    protected $_code             = self::METHOD_CODE;
    protected $_isGateway        = true;
    protected $_canCapture       = true;
    protected $_isInitializeNeeded = true;
    protected $_infoBlockType    = 'Paymee\Core\Block\Payment\Info\Transfer';

    protected $_helper;
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

        $this->_urlInterface                = $urlInterface;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->_checkoutSession             = $checkoutSession;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_helper = $objectManager->create('Paymee\Core\Helper\Data');
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        $infoForm = $data->getData();

        if (isset($infoForm['additional_data'])) {
            $infoForm = $infoForm['additional_data'];
        }

        $info = $this->getInfoInstance();
        $info->setAdditionalInformation($infoForm);

        return $this;
    }

    public function initialize($paymentAction, $stateObject)
    {
        $order      = $this->getInfoInstance()->getOrder();
        $customerId = $order->getCustomerId();

        if ($customerId) {
            $_customer  = $this->_customerRepositoryInterface->getById($customerId);
            $customerId = ($_customer->getId() !== null) ? $_customer->getId() : $customerId;
        }

        $payment  = $order->getPayment();
        $fieldCpf = $this->_helper->getPaymeeFieldCpf();

        switch ($fieldCpf) {
            case 'billing':
                $cpf = $order->getBillingAddress()->getVatId();
                break;
            case 'shipping':
                $cpf = $order->getShippingAddress()
                    ? $order->getShippingAddress()->getVatId()
                    : null;
                break;
            case 'customer':
                // BUG FIX: was incorrectly passing through logs() which returns void
                $cpf = $order->getData('customer_taxvat');
                break;
            case 'paymee':
                $cpf = $this->getInfoInstance()->getAdditionalInformation('transfer_cpf');
                break;
            default:
                $cpf = null;
        }

        $this->_helper->logs('--- Payee Transfer ----');
        $this->_helper->logs("field cpf: {$fieldCpf}");
        $this->_helper->logs("cpf: {$cpf}");

        if (!$cpf) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('O campo CPF não está preenchido')
            );
        }

        $firstName     = $order->getData('customer_firstname');
        $lastname      = $order->getData('customer_lastname');
        $email         = $order->getData('customer_email');
        $phone         = $order->getBillingAddress()->getTelephone();
        $name          = $firstName . ' ' . $lastname;
        $grandTotal    = round($order->getGrandTotal(), 2);
        $referenceCode = $order->getIncrementId();
        $maxAge        = $this->_helper->getPaymeeTransferMaxage();
        $bank          = $this->getInfoInstance()->getAdditionalInformation('transfer_bank');
        $agencia       = $this->getInfoInstance()->getAdditionalInformation('transfer_branch');
        $conta         = $this->getInfoInstance()->getAdditionalInformation('transfer_account');
        $observation   = $this->_helper->getPaymeeObservation();
        $paymentMethod = $this->_helper->getBanco($bank);

        $_data = [
            'currency'      => 'BRL',
            'amount'        => (float)$grandTotal,
            'referenceCode' => $referenceCode,
            'maxAge'        => (int)$maxAge,
            'paymentMethod' => $paymentMethod,
            'callbackURL'   => $this->_urlInterface->getUrl('paymee/webhook/'),
            'shopper'       => [
                'id'          => $customerId,
                'name'        => $name,
                'email'       => $email,
                'document'    => [
                    'type'   => 'CPF',
                    'number' => $cpf,
                ],
                'phone'       => [
                    'type'   => 'MOBILE',
                    'number' => $phone,
                ],
                'bankDetails' => [
                    'branch'  => $agencia,
                    'account' => $conta,
                ],
            ],
            'observation'   => $observation,
        ];

        $this->_helper->logs('--- Payee Transfer Data ----');
        $this->_helper->logs($_data);

        $api = new Api();
        $api->setUri('/v1.1/checkout/transparent/');
        $api->setData($_data);
        $api->connect();

        $response = $api->getResponse();
        $this->_helper->logs($response);

        if (isset($response['message']) && $response['message'] === 'success') {
            try {
                $referenceCode       = $response['response']['referenceCode'];
                $uuid                = $response['response']['uuid'];
                $instrName           = $response['response']['instructions']['name'];
                $beneficiaryBranch   = $response['response']['instructions']['beneficiary_branch'];
                $beneficiaryAccount  = $response['response']['instructions']['beneficiary_account'];
                $beneficiaryName     = $response['response']['instructions']['beneficiary_name'];
                $beneficiaryUrl      = $response['response']['instructions']['redirect_urls']['desktop'];
                $beneficiaryValue    = number_format($response['response']['amount'], 2, ',', '.');

                $payment
                    ->setAdditionalInformation('paymeeReferenceCode', $referenceCode)
                    ->setAdditionalInformation('paymeeUuid', $uuid)
                    ->setAdditionalInformation('instructions_name', $instrName)
                    ->setAdditionalInformation('instructions_beneficiary_branch', $beneficiaryBranch)
                    ->setAdditionalInformation('instructions_beneficiary_account', $beneficiaryAccount)
                    ->setAdditionalInformation('instructions_beneficiary_name', $beneficiaryName)
                    ->setAdditionalInformation('instructions_beneficiary_url', $beneficiaryUrl)
                    ->setAdditionalInformation('instructions_beneficiary_value', $beneficiaryValue);

            } catch (\Exception $e) {
                $this->_helper->logs('Paymee Transfer Error Payment Save: ' . $e->getMessage());
                throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
            }
        } else {
            $errors = isset($response['errors']) ? json_encode($response['errors']) : 'Erro desconhecido';
            throw new \Magento\Framework\Exception\LocalizedException(__($errors));
        }

        return $this;
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
