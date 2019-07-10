<?php

namespace Beecom\Twisto\Model\Logger;

use Magento\Framework\Logger\Handler\Base;

/**
 * Class Handler
 * @package PayU\PaymentGateway\Model\Logger
 */
class Handler extends Base
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
    protected $fileName = '/var/log/twisto.log';
}
