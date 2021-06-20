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
    protected $_registry;

    public function __construct(\Magento\Webapi\Controller\Rest\InputParamsResolver $inputParamsResolver,
        \Magento\Quote\Model\QuoteRepository $quoteRepository, 
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\State $state,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\Registry $registry
    ) {
        $this->_inputParamsResolver = $inputParamsResolver;
        $this->_quoteRepository = $quoteRepository;
        $this->logger = $logger;
        $this->_state = $state;
        $this->scopeConfig = $scopeConfig;
        $this->_urlInterface = $urlInterface;
        $this->_registry = $registry;
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

        if($this->_registry->registry('isRefundRun')) {
            return;
        }

        $this->_registry->register('isRefundRun', true);
        $this->logger->debug('Chamou creditmemo');

        $creditmemo     = $observer->getEvent()->getCreditmemo();
        $order_id       = $creditmemo->getData('order_id');
        $creditmemo_id  = $creditmemo->getId();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Model\Order')->load($order_id);
        $this->logger->debug($order->getId());
        $instructions_uuid = $order->getPayment()->getAdditionalInformation('instructions_uuid');
        $this->logger->debug($instructions_uuid);

        if (isset($paymee_uuid)) {
            $adjustment             = $creditmemo->getData('adjustment');
            $totalRefund            = $creditMemo->getData('base_grand_total');

            $credentials        = $this->getCredenciais();
            $env                = $this->getEnv();

            $data = array(
                "amount" => $totalRefund,
                "reason" => "Magento Mass Refund"
            );

            $x_api_key      = $credentials[$env]['x_api_key'];
            $x_api_token    = $credentials[$env]['x_api_token'];

            $url = "https://" . $credentials[$env]['prefix'] . ".paymee.com.br/v1.1/transactions/".$paymee_uuid."/refund";

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data, true),
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json",
                    "x-api-key: $x_api_key",
                    "x-api-token: $x_api_token"
                ),
            ));

            $response   = curl_exec($curl);
            $err        = curl_error($curl);

            curl_close($curl);
        }
    }
}
