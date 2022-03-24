<?php

namespace Paymee\Core\Model;

class Api {
    protected $api_url;
    protected $uri;
    protected $header;
    protected $data;
    protected $response;
    protected $_helper;

    public function __construct()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_helper = $objectManager->create('Paymee\Core\Helper\Data');

        $this->api_url = "https://api.paymee.com.br/";

        if ($this->_helper->getPaymeeEnvironment() == 'sandbox') {
            $this->api_url = "https://apisandbox.paymee.com.br/";
        }

        $api_key    = $this->_helper->getPaymeeKey();
        $api_token  = $this->_helper->getPaymeeToken();

        $this->header = array(
            "cache-control: no-cache",
            "content-type: application/json",
            "x-api-key: {$api_key}",
            "x-api-token: {$api_token}"
        );
    }

    public function setUri($uri) {
        $this->uri = $uri;
    }

    public function setData($data) {
        $this->data = json_encode($data);
    }

    public function getResponse() {
        return $this->response;
    }

    public function connect($post = true) {
        try {

            if (!$this->uri) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Parâmetros inválidos na conexão da API'));
            }

            $url = $this->api_url.$this->uri;

            $this->_helper->logs('---- Paymee API Connect ---');
            $this->_helper->logs($url);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            if ($post) {
                $this->_helper->logs($this->data);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->data);
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);

            if (curl_error($ch)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    sprintf('Falha ao tentar enviar parametros a Paymee: %s (%s)', curl_error($ch), curl_errno($ch))
                );
            }

            $response       = curl_exec($ch);
            $http_status    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close ($ch);

            $this->response     = json_decode($response, true);

            $this->_helper->logs('---- API Paymee Response ---');
            $this->_helper->logs(print_r($this->response, true));

            return $this->response;

        } catch (Exception $e) {
            $this->_helper->logs($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException($e->getMessage());
            return null;
        }
    }
}
