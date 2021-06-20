<?php
namespace Paymee\Pix\Controller\Index;
use Magento\Sales\Model\Order;

class Callback extends \Magento\Framework\App\Action\Action
{
	private $env;
    private $credentials = array();
    protected $_logger;
    protected $orderFactory;    
    protected $helper;
    private $orderRepository;
    private $searchCriteriaBuilder;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $_logger
        )
	{
        $this->_logger = $_logger;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->helper = $objectManager->create('Paymee\Pix\Helper\Data');
        $this->orderRepository = $objectManager->create('Magento\Sales\Model\OrderRepository');
        $this->searchCriteriaBuilder = $objectManager->create('Magento\Framework\Api\SearchCriteriaBuilder');
        $this->orderFactory = $objectManager->create('Magento\Sales\Model\OrderFactory');

        return parent::__construct($context);
    }
    
    public function execute() {
    
        $this->_logger->debug(__METHOD__);
        $this->credentials = $this->helper->_getCredenciais();
        $this->env = $this->helper->_getEnv();
        $contents = file_get_contents('php://input');
        //$contents = '{"saleToken":"ff66b277-7d76-3a4c-970c-4a50a43fb1ad","referenceCode":"35","currency":"BRL","amount":305.00,"shopper":{"fullName":"nome sobrenome","firstName":"nome","lastName":"sobrenome","email":"andre@Paymee.com.br","cpf":"35313621866","agency":"0000","account":"000000-1"},"date":null,"newStatus":"PAID"} ';
        $this->_logger->debug($contents);
        $date = date("YmdHis");
        $paymentStatus = $this->checkTransactionStatus(json_decode($contents));
        $referenceCode = explode('"referenceCode":"', $contents);
        $referenceCodeReal = explode('","currency"', $referenceCode[1]);
        $email = explode('"email":"', $contents);
        $email = explode('","cpf"', $email[1]);
        $incrementId = (int)$referenceCodeReal[0];
        $this->_logger->debug($incrementId);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($incrementId);
        $this->_logger->debug($order->getId());
        $this->_logger->debug($incrementId);
        $this->_logger->debug($paymentStatus);

       if ($paymentStatus == true && $incrementId) {
            try{
                $order->setState(Order::STATE_PROCESSING, true);
                $order->setStatus(Order::STATE_PROCESSING);
                $order->save();
                $this->_logger->debug($order->getId());
                var_dump(http_response_code(200));
                http_response_code(200);
            } catch(Exception $e) {
                $this->_logger->debug($e->getMessage());
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
            $this->_logger->debug("cURL Error #:" . $err);
            return false;
        } 

        $responseData = json_decode($response, true);

        if($responseData['message'] == 'success') {
            return true;
        } 

        return false;

    }

    public function _getCredenciais(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        //return $this->scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENT, $storeScope);
        return array(
            "prod" => array(
                "x_api_key" => $this->scopeConfig->getValue(self::merchant_gateway_key, $storeScope),
                "x_api_token" => $this->scopeConfig->getValue(self::merchant_gateway_token, $storeScope),
                "prefix" => "api"
            ),
            "sandbox" => array(
                "x_api_key" => $this->scopeConfig->getValue(self::merchant_gateway_key, $storeScope),
                "x_api_token" => $this->scopeConfig->getValue(self::merchant_gateway_token, $storeScope),
                "prefix" => "apisandbox"
            )
        );
    }

    public function _getEnv(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        if($this->scopeConfig->getValue(self::debug, $storeScope)){
            return 'sandbox';
        } else{
            return 'prod';
        }
    }

}
