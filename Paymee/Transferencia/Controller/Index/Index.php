<?php
namespace Paymee\Transferencia\Controller\Index;
use Magento\Framework\Controller\ResultFactory;

class Index extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;
    protected $_logger;
    protected $request;
    protected $orderRepository;
    protected $_customerRepositoryInterface;
    protected $helper;
    protected $messageManager;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $_logger,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
		\Magento\Framework\View\Result\PageFactory $pageFactory)
	{
        $this->_pageFactory = $pageFactory;
        $this->_logger = $_logger;
        $this->orderRepository = $orderRepository;
        $this->request = $request;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->helper = $objectManager->create('Paymee\Transferencia\Helper\Data');
        $this->messageManager = $objectManager->create('Magento\Framework\Message\ManagerInterface');
        $this->paymeeSession = $objectManager->create('Magento\Framework\Session\SessionManagerInterface');

        return parent::__construct($context);
	}

	public function execute()
	{
        $paymeeData = $this->_main();
        if($paymeeData === 'error'){
            
            return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success/', ['_current' => true, 'nofill' => true]);
        }
        if($paymeeData === false){
            return $this->resultRedirectFactory->create()->setPath('paymee/index/fail/', ['_current' => true]);
        }
        $this->paymeeSession->setTransferenciaData($paymeeData);
        $resultPage = $this->_pageFactory->create();
        $resultPage->getLayout()->getBlock('paymee_index_index')->setTransferenciaInstructions($paymeeData);
        return $resultPage;
    }
    
    private function _main()
    {
        $_lastOrder = $this->orderRepository->get($this->_request->getPost('order_id'));

        $this->credentials = $this->helper->_getCredenciais();
        $this->env = $this->helper->_getEnv();

        $urlInterface = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\UrlInterface');
        $url = $urlInterface->getBaseUrl();
        $_paymentData = $this->_request->getPost('payment');
        $lastIncrementId = $_lastOrder->getIncrementId();
        $obs_paymee = "";
        $obs_paymee .= $lastIncrementId. ";";

        $customer_id = $_lastOrder->getCustomerId();
        $_customer = $this->_customerRepositoryInterface->getById($customer_id);
        $obs_paymee .= $_customer->getFirstname().' '.$_customer->getLastname() . ";";


        $timeMinutes = $this->_getTimeMinutes();
        $_orderData = $_lastOrder->getData();
        
        $observation = $obs_paymee;
        //------------------------------------------------------------------------

        $currency = "BRL";
        $amount = $_orderData['grand_total'];
        $referenceCode = $lastIncrementId;
        $maxAge = $timeMinutes;
        $firstName = $_orderData['customer_firstname'];
        $lastname = $_orderData['customer_lastname'];
        $email = $_orderData['customer_email'];
        $cpf = $_paymentData['cpf'];
        $mobile = $_lastOrder->getBillingAddress()->getTelephone();

        $agencia = $_paymentData['branch'];
        $conta = $_paymentData['account'];
        $paymee_document = $_paymentData['cpf'];

        if(!$agencia || !$conta || !$paymee_document || !$cpf){
            return 'error';
        }
        
        $data_send = [
            "currency" => "BRL",
            "amount" => $amount,
            "referenceCode" => $referenceCode,
            "maxAge" => $timeMinutes,
            "shopper" => [
                "firstName" => $firstName,
                "lastName" => $lastname,
                "email" => $email,
                "cpf" => $cpf,
                "mobile" => $mobile
            ],
            "observation" => $observation
        ];
        //--------------------------------------------------------------------------------------------------------------
        

        $idUsuario = $_customer->getId();
        $phone = $mobile;

        $paymentMethod = $this->_getBanco($_paymentData['banco']);
        

        $data_send_transp = [
            "currency" => "BRL",
            "amount" => (float)$amount,
            "referenceCode" => $referenceCode,
            "maxAge" => $timeMinutes,
            "discriminator" => $this->helper->getDiscriminator(),
            "paymentMethod" => $paymentMethod,
            "callbackURL" => $url.'paymee/index/callback/',
            "shopper" => [
                "id" => $idUsuario,
                "name" => $firstName.' '.$lastname,
                "email" => $email,
                "document" => [
                    "type" => "CPF",
                    "number" => $paymee_document,
                ],
                "phone" => [
                    "type" => "MOBILE",
                    "number" => $phone,
                ],
                "bankDetails" => [
                    "branch" => $agencia,
                    "account" => $conta,
                ]
            ]
        ];
        $this->_logger->debug(__METHOD__);
        $this->_logger->debug(json_encode($data_send_transp));
        $x_api_key = $this->credentials[$this->env]['x_api_key'];
        $x_api_token = $this->credentials[$this->env]['x_api_token'];

        $url = "https://" . $this->credentials[$this->env]['prefix'] . ".paymee.com.br/v1.1/checkout/transparent/";

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data_send_transp, true),
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
        } else {
            $responseData = json_decode($response, true);
            if($responseData["status"] == -1){
                return false;
            } 
            return $response;
        }
        
    }

    private function _getTimeMinutes(){
         //------------------------------------------------------------------------
        //*** Calculando tempo para proxima quarta-feira as 23:59:59
        $from_time = strtotime(date('Y-m-d H:i:s'));
        $data = date('Y-m-d', strtotime('next Monday'));
        $to_time = strtotime($data . ' 23:59:59');
        $timeMinutes = (int) round(abs($to_time - $from_time) / 60, 2);
        $timeMinutes = 1440;
        return $timeMinutes;
        //------------------------------------------------------------------------
    }

    private function _getBanco($codigoBanco){
        /**
         * 001 - BANCO DO BRASIL 	BB_TRANSFER 	Register chosen bank (Banco do Brasil) and payment type (wire transfer)
            237 - BRADESCO 	BRADESCO_TRANSFER 	Register chosen bank (Bradesco) and payment type (wire transfer)
            341 - BANCO ITAÚ-UNIBANCO 	ITAU_TRANSFER_GENERIC 	Register chosen bank (Itaú) and payment type (wire transfer)
            341 - BANCO ITAÚ-UNIBANCO 	ITAU_TRANSFER_PF 	Register chosen bank (Itaú) and payment type (wire transfer)
            341 - BANCO ITAÚ-UNIBANCO 	ITAU_TRANSFER_PJ 	Register chosen bank (Itaú) and payment type (wire transfer)
            341 - BANCO ITAÚ-UNIBANCO 	ITAU_DI 	Register chosen bank (Itaú) and payment type (Cash)
            104 - BANCO CAIXA ECONOMICA FEDERAL 	CEF_TRANSFER 	Register chosen bank (Caixa) and payment type (wire transfer)
            202 - BANCO ORIGINAL 	ORIGINAL_TRANSFER 	Register chosen bank (Original) and payment type (wire transfer)
            033 - SANTANDER BRASIL 	SANTANDER_TRANSFER 	Register chosen bank (Santander) and payment type (wire transfer)
            033 - SANTANDER BRASIL 	SANTANDER_DI 	Register chosen bank (Santander) and payment type (cash)
         */
        switch($codigoBanco){
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
            default:
                $banco = null;
                break;
        }

        return $banco;
    }
}
