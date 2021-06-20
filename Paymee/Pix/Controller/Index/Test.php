<?php
namespace Paymee\Pix\Controller\Index;

class Test extends \Magento\Framework\App\Action\Action
{
    protected $orderFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory
        )
	{
        $this->orderFactory = $orderFactory;

        return parent::__construct($context);
    }
    
    public function execute() {
        \Zend_Debug::dump(__METHOD__);
    
        $orderIncrementId = '000000028';
        $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
        \Zend_Debug::dump($order->getId());
        die(__METHOD__);
    }

}

