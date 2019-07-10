<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Beecom\Twisto\Gateway\Http\Client;

use Beecom\Twisto\Model\Adapter\TwistoAdapterFactory;
use Beecom\Twisto\Model\Logger\Logger;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractTransaction
 */
abstract class AbstractTransaction implements ClientInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Logger
     */
    protected $customLogger;

    /**
     * @var TwistoAdapterFactory
     */
    protected $adapterFactory;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param Logger $customLogger
     * @param TwistoAdapterFactory $adapterFactory
     */
    public function __construct(LoggerInterface $logger, Logger $customLogger, TwistoAdapterFactory $adapterFactory)
    {
        $this->logger = $logger;
        $this->customLogger = $customLogger;
        $this->adapterFactory = $adapterFactory;
    }

    /**
     * @inheritdoc
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $data = $transferObject->getBody();
        $log = [
            'request' => $data,
            'client' => static::class
        ];
        $response['object'] = [];

        try {
            $response['object'] = $this->process($data);
        } catch (\Exception $e) {
            $message = __($e->getMessage() ?: 'Sorry, but something went wrong');
            $this->logger->critical($message);
            throw new ClientException($message);
        } finally {
            $message = 'Sorry, but something went wrong';
            $log['response'] = (array) $response['object'];
            $this->customLogger->debug($message, $log);
        }

        return $response;
    }

    /**
     * Process http request
     * @param array $data
     * @return \Twisto\Result\Error|\Twisto\Result\Successful
     */
    abstract protected function process(array $data);
}
