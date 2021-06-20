<?php
namespace Paymee\Pix\Block;
use Magento\Framework\Session\SessionManagerInterface;
class Index extends \Magento\Framework\View\Element\Template
{
    protected $_sessionManager;
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context, SessionManagerInterface $session,
        array $data = []
    ) {
        $this->_sessionManager = $session;
        parent::__construct($context,$data);
    }
    public function getTeste()
    {
        return 'foi';
    }

    public function getPixData()
    {
        return $this->_sessionManager->getPixData();
    }

}