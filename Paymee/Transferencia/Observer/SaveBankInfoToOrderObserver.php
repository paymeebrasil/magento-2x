<?php
namespace Paymee\Transferencia\Observer;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class SaveBankInfoToOrderObserver implements ObserverInterface {

    protected $_inputParamsResolver;
    protected $_quoteRepository;
    protected $logger;
    protected $_state;
    protected $scopeConfig;
    protected $_customerRepositoryInterface;
    protected $_urlInterface;

    public function __construct(\Magento\Webapi\Controller\Rest\InputParamsResolver $inputParamsResolver,
                                \Magento\Quote\Model\QuoteRepository $quoteRepository, 
                                \Psr\Log\LoggerInterface $logger,
                                \Magento\Framework\App\State $state,
                                \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
                                \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
                                \Magento\Framework\UrlInterface $urlInterface
     ) {
        $this->_inputParamsResolver = $inputParamsResolver;
        $this->_quoteRepository = $quoteRepository;
        $this->logger = $logger;
        $this->_state = $state;
        $this->scopeConfig = $scopeConfig;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->_urlInterface = $urlInterface;
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
            case '077':
                $banco = 'INTER_TRANSFER';
                break;
            default:
                $banco = null;
                break;
        }

        return $banco;
    }

    public function execute(EventObserver $observer) {
        if ($this->_state->getAreaCode() === \Magento\Framework\App\Area::AREA_WEBAPI_REST) {
            $inputParams = $this->_inputParamsResolver->resolve();

            foreach ($inputParams as $inputParam) {
        
                
                if ($inputParam instanceof \Magento\Quote\Model\Quote\Payment) {
                    
                    $_paymentData = $inputParam->getData('additional_data');    
        
            
                    $paymentOrder = $observer->getEvent()->getPayment();
                    $order = $paymentOrder->getOrder();
                    $quote = $this->_quoteRepository->get($order->getQuoteId());
                    $paymentQuote = $quote->getPayment();
                    $method = $paymentQuote->getMethodInstance()->getCode();
                    $idUsuario = "";
            
                    if ($method == 'paymee') {
                        
                        $observer->getEvent()->getPayment()->setAdditionalInformation("cpf", $_paymentData['cpf']);
                        $observer->getEvent()->getPayment()->setAdditionalInformation("branch", $_paymentData['branch']);
                        $observer->getEvent()->getPayment()->setAdditionalInformation("account", $_paymentData['account']);
                        $observer->getEvent()->getPayment()->setAdditionalInformation("banco", $_paymentData['banco']);

            

                        $lastIncrementId = $order->getIncrementId();
                    
                        $credentials = $this->getCredenciais();
                        $env = $this->getEnv();
                    
                        //------------------------------------------------------------------------
                        //*** Calculando tempo para proxima quarta-feira as 23:59:59
                        $from_time = strtotime(date('Y-m-d H:i:s'));
                        $data = date('Y-m-d', strtotime('next Monday'));
                        $to_time = strtotime($data . ' 23:59:59');
                        $timeMinutes = (int) round(abs($to_time - $from_time) / 60, 2);
                        $timeMinutes = 1440;
                        //------------------------------------------------------------------------
                    
                        $obs_paymee = "";
                        $obs_paymee .= $lastIncrementId. ";";
                        
                        $_lastOrder = $order;

                        $customer_id = $_lastOrder->getCustomerId();
                        if(isset($customer_id)) {
                            $_customer = $this->_customerRepositoryInterface->getById($customer_id);
                            $obs_paymee .= $_customer->getFirstname().' '.$_customer->getLastname() . ";";
                            $idUsuario = ($_customer->getId() !== null) ? $_customer->getId() : "";
                        }
                        
                        $_orderData = $_lastOrder->getData();
                        //$_paymentData = $_lastOrder->getPayment()->getData();
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
                        $name = $firstName . " " . $lastname;
                        if(empty($mobile)) {
                            $mobile = "99999999999";
                        }
                        

                        $data_send = [
                            "currency" => "BRL",
                            "amount" => $amount,
                            "referenceCode" => $referenceCode,
                            "maxAge" => $timeMinutes,
                            "shopper" => [
                                "name" => $name,
                                "email" => $email,
                                "cpf" => $cpf,
                                "mobile" => $mobile
                            ],
                            "observation" => $observation
                        ];
                        //--------------------------------------------------------------------------------------------------------------
                       
                        $phone = $mobile;

                        $paymentMethod = $this->_getBanco($_paymentData['banco']);
                        
                        $agencia = $_paymentData['branch'];
                        $conta = $_paymentData['account'];
                        $paymee_document = $_paymentData['cpf'];

                        $this->logger->debug($this->_urlInterface->getUrl('paymee/callback/index/'));
                        $data_send_transp = [
                            "currency" => "BRL",
                            "amount" => (float)$amount,
                            "referenceCode" => $referenceCode,
                            "maxAge" => $timeMinutes,
                            "paymentMethod" => $paymentMethod,
                            "callbackURL" => $this->_urlInterface->getUrl('paymee/callback/index/'),
                            "shopper" => [
                                "id" => $idUsuario,
                                "name" => $name,
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
                        $x_api_key = $credentials[$env]['x_api_key'];
                        $x_api_token = $credentials[$env]['x_api_token'];
                
                        $url = "https://" . $credentials[$env]['prefix'] . ".paymee.com.br/v1.1/checkout/transparent/";
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
                            echo "err: " . $err;
                            $this->logger->debug("cURL Error #:" . $err);
                        } else {
                            $this->logger->debug($response);   

                            $responseTransferenciaJson = $response;
                            $responseTransferencia = json_decode($responseTransferenciaJson, true);
                            if($responseTransferencia['status'] !== 0) {
                                print_r($responseTransferencia);
                                $this->logger->debug($responseTransferencia);
                                return;   
                            }
                            
                            $body =  $responseTransferencia['response'];
                            $steps = $body['instructions']['steps'];
                            $observer->getEvent()->getPayment()->setAdditionalInformation("instructions_name", $body['instructions']['name']);
                            $observer->getEvent()->getPayment()->setAdditionalInformation("instructions_beneficiary_branch", $body['instructions']['beneficiary_branch']);
                            $observer->getEvent()->getPayment()->setAdditionalInformation("instructions_beneficiary_account", $body['instructions']['beneficiary_account']);
                            $observer->getEvent()->getPayment()->setAdditionalInformation("instructions_beneficiary_name", $body['instructions']['beneficiary_name']);
                            $observer->getEvent()->getPayment()->setAdditionalInformation("instructions_beneficiary_url", $body['instructions']["redirect_urls"]["desktop"]);
                            $observer->getEvent()->getPayment()->setAdditionalInformation("instructions_beneficiary_value", number_format($body['amount'], 2, ',', '.'));
                        }
                    }
                }
            }
       }
    }
}
