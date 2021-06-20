<?php
namespace Paymee\Pix\Controller\Index;
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
        $this->helper = $objectManager->create('Paymee\Pix\Helper\Data');
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
            return $this->resultRedirectFactory->create()->setPath('paymeepix/index/fail/', ['_current' => true]);
        }
        $this->paymeeSession->setPixData($paymeeData);
        $resultPage = $this->_pageFactory->create();
        $resultPage->getLayout()->getBlock('paymeepix_index_index')->setPixInstructions($paymeeData);
        return $resultPage;
    }
    
    private function _main()
    {
        $this->_logger->debug('Bateu no main do Controller/Index/Index.php');
        
    }

}
