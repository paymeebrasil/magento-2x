<?php
namespace Paymee\Pix\Observer;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class SavePixInfoToOrderObserver implements ObserverInterface {

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

        $this->logger->debug('Chamou paymee pix integration');

        if ($this->_state->getAreaCode() != \Magento\Framework\App\Area::AREA_ADMINHTML) {
            $inputParams = $this->_inputParamsResolver->resolve();

            foreach ($inputParams as $inputParam) {
                if ($inputParam instanceof \Magento\Quote\Model\Quote\Payment) {
                    
                    $_paymentData   = $inputParam->getData('additional_data');    
                    $paymentOrder   = $observer->getEvent()->getPayment();
                    $order          = $paymentOrder->getOrder();
                    $quote          = $this->_quoteRepository->get($order->getQuoteId());
                    $paymentQuote   = $quote->getPayment();
                    $method         = $paymentQuote->getMethodInstance()->getCode();
                    $idUsuario      = "";

                    if ($method == 'paymeepix') {
                        
                        $observer->getEvent()->getPayment()->setAdditionalInformation("cpf", $_paymentData['cpf']);

                        $lastIncrementId    = $order->getIncrementId();
                        $credentials        = $this->getCredenciais();
                        $env                = $this->getEnv();
                    
                        //------------------------------------------------------------------------
                        $from_time      = strtotime(date('Y-m-d H:i:s'));
                        $data           = date('Y-m-d', strtotime('next Monday'));
                        $to_time        = strtotime($data . ' 23:59:59');
                        $timeMinutes    = (int) round(abs($to_time - $from_time) / 60, 2);
                        $timeMinutes    = 1440;
                        //------------------------------------------------------------------------
                    
                        $obs_paymee     = "";
                        $obs_paymee     .= $lastIncrementId. ";";
                        $customer_id    = $order->getCustomerId();

                        if(isset($customer_id)) {
                            $_customer      = $this->_customerRepositoryInterface->getById($customer_id);
                            $obs_paymee     .= $_customer->getFirstname().' '.$_customer->getLastname() . ";";
                            $idUsuario      = ($_customer->getId() !== null) ? $_customer->getId() : "";
                        }
                        
                        $_orderData     = $order->getData();
                        $observation    = $obs_paymee;
                        $currency       = "BRL";
                        $amount         = $_orderData['grand_total'];
                        $referenceCode  = $lastIncrementId;
                        $maxAge         = $timeMinutes;
                        $firstName      = $_orderData['customer_firstname'];
                        $lastname       = $_orderData['customer_lastname'];
                        $email          = $_orderData['customer_email'];
                        $cpf            = $_paymentData['cpf'];
                        $phone         = $order->getBillingAddress()->getTelephone();
                        $name           = $firstName . " " . $lastname;

                        if(empty($phone)) {
                            $phone = "99999999999";
                        }

                        $data_send_transp = array(
                            "currency" => "BRL",
                            "amount" => (float)$amount,
                            "referenceCode" => $referenceCode,
                            "maxAge" => $timeMinutes,
                            "paymentMethod" => "PIX",
                            "callbackURL" => $this->_urlInterface->getUrl('paymeepix/callback/index/'),
                            "shopper" => array(
                                "id" => $idUsuario,
                                "name" => $name,
                                "email" => $email,
                                "document" => array(
                                    "type" => "CPF",
                                    "number" => $cpf,
                                ),
                                "phone" => array(
                                    "type" => "MOBILE",
                                    "number" => $phone,
                                ),
                            )
                        );

                        $this->logger->info(print_r($data_send_transp, true));

                        $x_api_key      = $credentials[$env]['x_api_key'];
                        $x_api_token    = $credentials[$env]['x_api_token'];
                
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

                        $response   = curl_exec($curl);
                        $err        = curl_error($curl);
                
                        curl_close($curl);
    
                
                        if ($err) {
                            echo "err: " . $err;
                            $observer->getEvent()->getPayment()->setAdditionalInformation("paymeepix_error", true);
                            $observer->getEvent()->getPayment()->setAdditionalInformation("paymeepix_error_message", $err);

                            $this->logger->debug("cURL Error #:" . $err);
                        } else {
                            $this->logger->debug($response);   

                            $responsePixJson    = $response;
                            $responsePix        = json_decode($responsePixJson, true);

                            if ($responsePix['status'] !== 0) {
                                throw new \Exception($response);
                                return $response;   
                            }
                            
                            $body   =  $responsePix['response'];
                            $steps  = $body['instructions']['steps'];

                            $observer->getEvent()->getPayment()->setAdditionalInformation("paymeepix_error", false);
                            $observer->getEvent()->getPayment()->setAdditionalInformation("instructions_name", $body['instructions']['name']);
                            $observer->getEvent()->getPayment()->setAdditionalInformation("instructions_chosen", $body['instructions']['chosen']);
                            $observer->getEvent()->getPayment()->setAdditionalInformation("instructions_qrcode", $body['instructions']["qrCode"]);
                            $observer->getEvent()->getPayment()->setAdditionalInformation("instructions_steps", $body['instructions']["steps"]);
                            $observer->getEvent()->getPayment()->setAdditionalInformation("instructions_beneficiary_value", number_format($body['amount'], 2, ',', '.'));
                            $observer->getEvent()->getPayment()->setAdditionalInformation("instructions_uuid", $body["uuid"]);

                        }
                    }
                }
            }
       } 
    }
}
