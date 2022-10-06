<?php

namespace Paymee\Core\Logger\Handler;

use Monolog\Logger;

/**
 * Paymee logger handler
 */
class System extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/paymee.log';
}
