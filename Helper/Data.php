<?php
namespace Paymee\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Paymee\Core\Logger\Logger;

class Data extends AbstractHelper
{
    protected $_sessionManager;
    protected $_pricingHelper;

    /**
     * Logging instance
     *
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
        return $this->scopeConfig->getValue('payment/paymee_preferences/auto_invoice', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getPaymeePixInstructions()
    {
        return $this->scopeConfig->getValue('payment/paymee_pix/instructions', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getPaymeePixMaxage()
    {
        return $this->scopeConfig->getValue('payment/paymee_pix/max_age', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getPaymeeBoletoInstructions()
    {
        return $this->scopeConfig->getValue('payment/paymee_boleto/instructions', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getPaymeeBoletoMaxage()
    {
        return $this->scopeConfig->getValue('payment/paymee_boleto/max_age', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getPaymeeTransferInstructions()
    {
        return $this->scopeConfig->getValue('payment/paymee_transfer/instructions', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getPaymeeTransferMaxage()
    {
        return $this->scopeConfig->getValue('payment/paymee_transfer/max_age', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getPaymeeDebug()
    {
        return $this->scopeConfig->getValue('payment/paymee_preferences/debug', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getPaymeeEnvironment()
    {
        return $this->scopeConfig->getValue('payment/paymee_preferences/environment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getPaymeeFieldCpf()
    {
        return $this->scopeConfig->getValue('payment/paymee_preferences/cpf', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getPaymeeKey()
    {
        return $this->scopeConfig->getValue('payment/paymee_preferences/key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getPaymeeToken()
    {
        return $this->scopeConfig->getValue('payment/paymee_preferences/token', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getPaymeeObservation()
    {
        return $this->scopeConfig->getValue('payment/paymee_preferences/observation', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
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
        /**
         * 001 - BANCO DO BRASIL    BB_TRANSFER     Register chosen bank (Banco do Brasil) and payment type (wire transfer)
        237 - BRADESCO  BRADESCO_TRANSFER   Register chosen bank (Bradesco) and payment type (wire transfer)
        341 - BANCO ITAÚ-UNIBANCO   ITAU_TRANSFER_GENERIC   Register chosen bank (Itaú) and payment type (wire transfer)
        341 - BANCO ITAÚ-UNIBANCO   ITAU_TRANSFER_PF    Register chosen bank (Itaú) and payment type (wire transfer)
        341 - BANCO ITAÚ-UNIBANCO   ITAU_TRANSFER_PJ    Register chosen bank (Itaú) and payment type (wire transfer)
        341 - BANCO ITAÚ-UNIBANCO   ITAU_DI     Register chosen bank (Itaú) and payment type (Cash)
        104 - BANCO CAIXA ECONOMICA FEDERAL     CEF_TRANSFER    Register chosen bank (Caixa) and payment type (wire transfer)
        202 - BANCO ORIGINAL    ORIGINAL_TRANSFER   Register chosen bank (Original) and payment type (wire transfer)
        033 - SANTANDER BRASIL  SANTANDER_TRANSFER  Register chosen bank (Santander) and payment type (wire transfer)
        033 - SANTANDER BRASIL  SANTANDER_DI    Register chosen bank (Santander) and payment type (cash)
         */
        switch ($codigoBanco) {
            case '001':
                $banco = 'BB_TRANSFER';
                break;
            case '237':
                $banco = 'BRADESCO_TRANSFER';
                break;
            case '341':
                $banco = 'ITAU_TRANSFER_GENERIC';
                break;
            case '104':
                $banco = 'CEF_TRANSFER';
                break;
            case '202':
                $banco = 'ORIGINAL_TRANSFER';
                break;
            case '033':
                $banco = 'SANTANDER_TRANSFER';
                break;
            case '077':
                $banco = 'INTER_TRANSFER';
                break;
            default:
                $banco = null;
                break;
        }

        return $banco;
    }

    public function checkVersionMagento23Less() {
        $objectManager      = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata    = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        $version            = $productMetadata->getVersion();
        if (version_compare($version, '2.4', '<')) {
            return true;
        } return false;
    }

    public function logs($message, $name = "paymee")
    {
        if (!$this->getPaymeeDebug()) {
            return;
        }

        try {
            //Check magento version
            $objectManager      = \Magento\Framework\App\ObjectManager::getInstance();
            $productMetadata    = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
            $version            = $productMetadata->getVersion();

            if (is_array($message)) {
                $message = print_r($message, true);
            }

            if (version_compare($version, '2.4', '>=')) {
                $this->_paymeeLogger->setName($name);
                $this->_paymeeLogger->debug($message);
            } elseif (version_compare($version, '2.3.5', '=')) {
                $writer = new \Laminas\Log\Writer\Stream(BP . '/var/log/paymee.log');
                $logger = new \Laminas\Log\Logger();
                $logger->addWriter($writer);
                $logger->info($message);
            } else {
                $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/paymee.log');
                $logger = new \Zend_Log();
                $logger->addWriter($writer);
                $logger->info($message);
            }
        } catch (Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }
}
