<?php
namespace Paymee\Pix\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;
class Data extends AbstractHelper
{
    protected $scopeConfig;
    
    CONST merchant_gateway_key = "payment/paymeepix/key";
    CONST merchant_gateway_token = "payment/paymeepix/token";
    CONST debug = "payment/paymeepix/debug";
    
    public function RandomFunc()
    {
            echo "This is Helper in Magento 2";
    }

    public function _getCredenciais(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->scopeConfig = $objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');
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