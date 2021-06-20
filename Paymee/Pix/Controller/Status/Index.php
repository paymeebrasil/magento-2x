<?php
namespace Paymee\Pix\Controller\Status;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;
    protected $logger;
    protected $order;

    protected $credentials;
    protected $env;
    protected $scopeConfig;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->logger = $logger;
        $this->order = $order;
        $this->scopeConfig = $scopeConfig;
        return parent::__construct($context);
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

    public function execute()
    {
        $this->logger->debug(__METHOD__.__LINE__);
        $this->credentials = $this->getCredenciais();
        $this->env = $this->getEnv();

        $uuid = $this->getRequest()->getParam('uuid');
        
        $paymentStatus = $this->checkTransactionStatus($uuid);
        
        $this->logger->debug('bate no controller /Status/Index.php');
        $this->logger->debug($uuid);

        if ($uuid != null) {
            $paymentStatus = $this->checkTransactionStatus($uuid);
            $this->logger->debug($paymentStatus);
            echo $paymentStatus;
        }

    }

    public function checkTransactionStatus($uuid) {

        $x_api_key = $this->credentials[$this->env]['x_api_key'];
        $x_api_token = $this->credentials[$this->env]['x_api_token'];

        $url = "https://" . $this->credentials[$this->env]['prefix'] . ".paymee.com.br/v1.1/transactions/" . $uuid;
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "x-api-key: $x_api_key",
                "x-api-token: $x_api_token"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $this->logger->debug("cURL Error #:" . $err);
            return false;
        } 

        $responseData = json_decode($response, true);

        if($responseData['message'] == 'success' && $responseData['situation'] == 'PAID') {
            return 'PAID';
        } 

        return false;
    }
}
