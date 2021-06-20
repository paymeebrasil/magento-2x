<?php
namespace Paymee\Pix\Controller\Index;

class Fail extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $_logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
		\Magento\Framework\View\Result\PageFactory $pageFactory)
	{
		$this->_pageFactory = $pageFactory;
        $this->_logger = $_logger;
        $this->orderRepository = $orderRepository;
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        return parent::__construct($context);
	}

	public function execute()
	{
		return $this->_pageFactory->create();
    }
    
}
