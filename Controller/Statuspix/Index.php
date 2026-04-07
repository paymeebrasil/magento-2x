<?php

namespace Paymee\Core\Controller\Statuspix;

use Magento\Framework\App\Action\Context;
use Paymee\Core\Controller\AbstractNotification;
use Paymee\Core\Model\Api;

class Index extends AbstractNotification
{
    protected $_helper;

    public function __construct(
        Context $context
    ) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_helper = $objectManager->create('Paymee\Core\Helper\Data');
        parent::__construct($context);
    }

    /**
     * Check PIX payment status by UUID.
     * Called via AJAX from the success page to poll payment status.
     * Route: paymee/statuspix/
     */
    public function execute()
    {
        $uuid = $this->getRequest()->getParam('uuid');

        $this->_helper->logs('--- Paymee Check Pix Status ----');
        $this->_helper->logs($uuid);

        $resultJson = $this->resultFactory->create(
            \Magento\Framework\Controller\ResultFactory::TYPE_JSON
        );

        if (!$uuid) {
            return $resultJson->setHttpResponseCode(400)->setData(['error' => 'Missing uuid']);
        }

        try {
            $api = new Api();
            $api->setUri("/v1.1/transactions/{$uuid}");
            $api->connect(false);

            $response = $api->getResponse();
            $this->_helper->logs($response);

            if (isset($response['message']) && $response['message'] === 'success') {
                return $resultJson->setData([
                    'situation' => $response['situation'] ?? 'PENDING',
                    'paid'      => ($response['situation'] ?? '') === 'PAID',
                ]);
            }

            return $resultJson->setHttpResponseCode(400)->setData(['error' => 'API error']);

        } catch (\Exception $e) {
            $this->_helper->logs($e->getMessage());
            return $resultJson->setHttpResponseCode(500)->setData(['error' => $e->getMessage()]);
        }
    }
}
