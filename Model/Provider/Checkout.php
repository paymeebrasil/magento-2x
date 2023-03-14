<?php

namespace Paymee\Core\Model\Provider;

use \Magento\Checkout\Model\ConfigProviderInterface;

class Checkout implements ConfigProviderInterface
{

    protected $_checkoutSession;
    protected $_scopeConfig;
    protected $_storeManager;

    const PAYMEE_METHOD_PIX_CODE         = 'paymee_pix';
    const PAYMEE_METHOD_BOLETO_CODE      = 'paymee_boleto';
    const PAYMEE_METHOD_TRANSFER_CODE    = 'paymee_transfer';

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_checkoutSession = $checkoutSession;
        $this->_storeManager = $storeManager;
    }

    public function getConfig()
    {
        $objectManager  = \Magento\Framework\App\ObjectManager::getInstance();
        $_helper        = $objectManager->create('Paymee\Core\Helper\Data');

        $pix_instructions       = $_helper->getPaymeePixInstructions();
        $boleto_instructions    = $_helper->getPaymeeBoletoInstructions();
        $transfer_instructions  = $_helper->getPaymeeTransferInstructions();
        $transfer_banks         = $_helper->getPaymeeTransferBanks();
        $fieldCpf               = false;

        if ($_helper->getPaymeeFieldCpf() == 'paymee') {
            $fieldCpf = true;
        }

        return [
            'payment' => [
                self::PAYMEE_METHOD_PIX_CODE => [
                    'instructions'  => $pix_instructions,
                    'fieldCpf'      => $fieldCpf
                ],
                self::PAYMEE_METHOD_BOLETO_CODE => [
                    'instructions'  => $boleto_instructions
                ],
                self::PAYMEE_METHOD_TRANSFER_CODE => [
                    'instructions'  => $transfer_instructions,
                    'banks'         => $transfer_banks,
                    'fieldCpf'      => $fieldCpf
                ]
            ]
        ];
    }
}
