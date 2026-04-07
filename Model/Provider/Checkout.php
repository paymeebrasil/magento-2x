<?php

namespace Paymee\Core\Model\Provider;

use Magento\Checkout\Model\ConfigProviderInterface;

class Checkout implements ConfigProviderInterface
{
    const PAYMEE_METHOD_PIX_CODE      = 'paymee_pix';
    const PAYMEE_METHOD_BOLETO_CODE   = 'paymee_boleto';
    const PAYMEE_METHOD_TRANSFER_CODE = 'paymee_transfer';

    protected $_checkoutSession;
    protected $_scopeConfig;
    protected $_storeManager;
    protected $_helper;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Paymee\Core\Helper\Data $helper
    ) {
        $this->_scopeConfig     = $scopeConfig;
        $this->_checkoutSession = $checkoutSession;
        $this->_storeManager    = $storeManager;
        $this->_helper          = $helper;
    }

    public function getConfig(): array
    {
        $pixInstructions      = $this->_helper->getPaymeePixInstructions();
        $boletoInstructions   = $this->_helper->getPaymeeBoletoInstructions();
        $transferInstructions = $this->_helper->getPaymeeTransferInstructions();
        $transferBanks        = $this->_helper->getPaymeeTransferBanks();
        $fieldCpf             = $this->_helper->getPaymeeFieldCpf() === 'paymee';

        return [
            'payment' => [
                self::PAYMEE_METHOD_PIX_CODE => [
                    'instructions' => $pixInstructions,
                    'fieldCpf'     => $fieldCpf,
                ],
                self::PAYMEE_METHOD_BOLETO_CODE => [
                    'instructions' => $boletoInstructions,
                ],
                self::PAYMEE_METHOD_TRANSFER_CODE => [
                    'instructions' => $transferInstructions,
                    'banks'        => $transferBanks,
                    'fieldCpf'     => $fieldCpf,
                ],
            ],
        ];
    }
}
