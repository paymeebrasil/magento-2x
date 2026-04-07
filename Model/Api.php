<?php

namespace Paymee\Core\Model;

class Api
{
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

        $this->api_url = 'https://api.paymee.com.br/';

        if ($this->_helper->getPaymeeEnvironment() === 'sandbox') {
            $this->api_url = 'https://apisandbox.paymee.com.br/';
        }

        $api_key   = $this->_helper->getPaymeeKey();
        $api_token = $this->_helper->getPaymeeToken();

        $this->header = [
            'cache-control: no-cache',
            'content-type: application/json',
            "x-api-key: {$api_key}",
            "x-api-token: {$api_token}",
        ];
    }

    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }

    public function setData($data): void
    {
        $this->data = json_encode($data);
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function connect(bool $post = true)
    {
        try {
            if (!$this->uri) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Parâmetros inválidos na conexão da API')
                );
            }

            $url = $this->api_url . $this->uri;

            $this->_helper->logs('---- Paymee API Connect ---');
            $this->_helper->logs($url);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            if ($post) {
                $this->_helper->logs($this->data);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->data);
            }

            $response    = curl_exec($ch);
            $curlError   = curl_error($ch);
            $curlErrno   = curl_errno($ch);
            curl_close($ch);

            if ($curlError) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Falha ao conectar com a Paymee: %1 (%2)', $curlError, $curlErrno)
                );
            }

            $this->response = json_decode($response, true);

            $this->_helper->logs('---- API Paymee Response ---');
            $this->_helper->logs(print_r($this->response, true));

            return $this->response;

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->_helper->logs($e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->_helper->logs($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }
}
