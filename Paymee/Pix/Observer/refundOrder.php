<?php
namespace Paymee\Pix\Observer;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class refundOrder implements ObserverInterface {

    protected $_inputParamsResolver;
    protected $_quoteRepository;
    protected $logger;
    protected $_state;
    protected $scopeConfig;
    protected $_urlInterface;

    public function __construct(\Magento\Webapi\Controller\Rest\InputParamsResolver $inputParamsResolver,
                                \Magento\Quote\Model\QuoteRepository $quoteRepository, 
                                \Psr\Log\LoggerInterface $logger,
                                \Magento\Framework\App\State $state,
                                \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
                                \Magento\Framework\UrlInterface $urlInterface
     ) {
        $this->_inputParamsResolver = $inputParamsResolver;
        $this->_quoteRepository = $quoteRepository;
        $this->logger = $logger;
        $this->_state = $state;
        $this->scopeConfig = $scopeConfig;
        $this->_urlInterface = $urlInterface;
    }

    public function getCredenciais(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return array(
            "prod" => array(
                "x_api_key" => $this->scopeConfig->getValue('payment/paymeepix/key', $storeScope),
                "x_api_token" => $this->scopeConfig->getValue('payment/paymeepix/token', $storeScope),
                "prefix" => "api"
            ),
            "sandbox" => array(
                "x_api_key" => $this->scopeConfig->getValue('payment/paymeepix/key', $storeScope),
                "x_api_token" => $this->scopeConfig->getValue('payment/paymeepix/token', $storeScope),
                "prefix" => "apisandbox"
            )
        );
    }

    public function getEnv(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        if($this->scopeConfig->getValue('payment/paymeepix/debug', $storeScope)){
            return 'sandbox';
        } else{
            return 'prod';
        }
    }

    public function execute(EventObserver $observer) {

        $this->logger->debug('Chamou creditmemo');

    }
}
