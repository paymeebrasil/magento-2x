<?php
namespace Paymee\Pix\Model\Session;
class Storage extends \Magento\Framework\Session\Storage
{
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        $namespace = 'paymeepix',
        array $data = []
    ) {
        parent::__construct($namespace, $data);
    }
}