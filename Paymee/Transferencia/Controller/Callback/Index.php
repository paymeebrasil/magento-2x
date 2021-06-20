<?php
namespace Paymee\Transferencia\Controller\Callback;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order;

class Index extends \Magento\Customer\Controller\AbstractAccount
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
                "x_api_key" => $this->scopeConfig->getValue('payment/paymee/key', $storeScope),
                "x_api_token" => $this->scopeConfig->getValue('payment/paymee/token', $storeScope),
                "prefix" => "api"
            ),
            "sandbox" => array(
                "x_api_key" => $this->scopeConfig->getValue('payment/paymee/key', $storeScope),
                "x_api_token" => $this->scopeConfig->getValue('payment/paymee/token', $storeScope),
                "prefix" => "apisandbox"
            )
        );
    }

    public function getEnv(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        if($this->scopeConfig->getValue('payment/paymee/debug', $storeScope)){
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

        $contents = file_get_contents('php://input');
        $this->logger->debug(__METHOD__.__LINE__);

        $this->logger->debug('contents');
        $this->logger->debug($contents);
        $date = date("YmdHis");
        
        $paymentStatus = $this->checkTransactionStatus(json_decode($contents));

        $referenceCode = explode('"referenceCode":"', $contents);
        $referenceCodeReal = explode('","currency"', $referenceCode[1]);

        $email = explode('"email":"', $contents);
        $email = explode('","cpf"', $email[1]);
        $incrementId = $referenceCodeReal[0];
        
       if($paymentStatus == true && $incrementId) {
            try{
                $order = $this->order->loadByIncrementId($incrementId);
                $order->setState(Order::STATE_PROCESSING, true)->save();
                var_dump(http_response_code(200));
                http_response_code(200);
            } catch(Exception $e) {
                $this->logger->debug($e->getMessage());
                var_dump(http_response_code(400));
                http_response_code(400);
            }
        }
    }

    public function checkTransactionStatus($obj) {

        $x_api_key = $this->credentials[$this->env]['x_api_key'];
        $x_api_token = $this->credentials[$this->env]['x_api_token'];

        $url = "https://" . $this->credentials[$this->env]['prefix'] . ".paymee.com.br/v1.1/transactions/" . $obj->saleToken;
        
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
            return true;
        } return false;

    }
}
