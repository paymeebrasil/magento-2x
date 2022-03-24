<?php

namespace Paymee\Core\Block\Payment\Info;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Block\Info;

class Pix extends Info
{

    protected $_helper;

    const TEMPLATE = 'Paymee_Core::info/pix.phtml';

    public function _construct()
    {
        $this->setTemplate(self::TEMPLATE);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_helper = $objectManager->create('Paymee\Core\Helper\Data');
    }

    public function getMethod()
    {
        return $this->getInfo()->getMethod();
    }

    public function getTitle()
    {
        return $this->getInfo()->getAdditionalInformation('method_title');
    }

    public function getPixImg()
    {
        $info   = $this->getInfo();
        $method = $info->getMethod();

        if (strpos($method, "paymee_pix") === false) {
            return null;
        }

        return $this->getInfo()->getAdditionalInformation('paymeeQrCodeImg');
    }

    public function getPixCopy() {
        return $this->getInfo()->getAdditionalInformation('paymeeQrCodeCopy');
    }
}
