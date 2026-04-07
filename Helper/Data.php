<?php

namespace Paymee\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Paymee\Core\Logger\Logger;

class Data extends AbstractHelper
{
    protected $_sessionManager;
    protected $_pricingHelper;

    /**
     * @var Logger
     */
    protected $_paymeeLogger;

    public function __construct(
        \Magento\Framework\App\Helper\Context     $context,
        \Magento\Framework\Session\SessionManager $sessionManager,
        \Magento\Framework\Pricing\Helper\Data    $pricingHelper,
        Logger $logger
    ) {
        $this->_sessionManager  = $sessionManager;
        $this->_pricingHelper   = $pricingHelper;
        $this->_paymeeLogger    = $logger;
        parent::__construct($context);
    }

    public function getPaymeeAutoInvoice()
    {
        return $this->scopeConfig->getValue(
            'payment/paymee_preferences/auto_invoice',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getPaymeePixInstructions()
    {
        return $this->scopeConfig->getValue(
            'payment/paymee_pix/instructions',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getPaymeePixMaxage()
    {
        return $this->scopeConfig->getValue(
            'payment/paymee_pix/max_age',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getPaymeeBoletoInstructions()
    {
        return $this->scopeConfig->getValue(
            'payment/paymee_boleto/instructions',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getPaymeeBoletoMaxage()
    {
        return $this->scopeConfig->getValue(
            'payment/paymee_boleto/max_age',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getPaymeeTransferInstructions()
    {
        return $this->scopeConfig->getValue(
            'payment/paymee_transfer/instructions',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getPaymeeTransferMaxage()
    {
        return $this->scopeConfig->getValue(
            'payment/paymee_transfer/max_age',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getPaymeeDebug()
    {
        return $this->scopeConfig->getValue(
            'payment/paymee_preferences/debug',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getPaymeeEnvironment()
    {
        return $this->scopeConfig->getValue(
            'payment/paymee_preferences/environment',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getPaymeeFieldCpf()
    {
        return $this->scopeConfig->getValue(
            'payment/paymee_preferences/cpf',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getPaymeeKey()
    {
        return $this->scopeConfig->getValue(
            'payment/paymee_preferences/key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getPaymeeToken()
    {
        return $this->scopeConfig->getValue(
            'payment/paymee_preferences/token',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getPaymeeObservation()
    {
        return $this->scopeConfig->getValue(
            'payment/paymee_preferences/observation',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getPaymeeTransferBanks()
    {
        $banks['001'] = '001 - Banco do Brasil S.A';
        $banks['237'] = '237 - Banco Bradesco S.A';
        $banks['341'] = '341 - Banco Itaú-Unibanco S.A';
        return $banks;
    }

    public function getBanco($codigoBanco)
    {
        switch ($codigoBanco) {
            case '001':
                return 'BB_TRANSFER';
            case '237':
                return 'BRADESCO_TRANSFER';
            case '341':
                return 'ITAU_TRANSFER_GENERIC';
            case '104':
                return 'CEF_TRANSFER';
            case '202':
                return 'ORIGINAL_TRANSFER';
            case '033':
                return 'SANTANDER_TRANSFER';
            case '077':
                return 'INTER_TRANSFER';
            default:
                return null;
        }
    }

    /**
     * Log a message to the Paymee log file.
     * Only logs when debug mode is enabled.
     *
     * @param mixed $message
     * @param string $name
     * @return void
     */
    public function logs($message, $name = 'paymee'): void
    {
        if (!$this->getPaymeeDebug()) {
            return;
        }

        try {
            if (is_array($message) || is_object($message)) {
                $message = print_r($message, true);
            }

            $this->_paymeeLogger->setName($name);
            $this->_paymeeLogger->debug((string)$message);
        } catch (\Exception $e) {
            // Silently fail to avoid breaking payment flow due to logging errors
        }
    }
}
