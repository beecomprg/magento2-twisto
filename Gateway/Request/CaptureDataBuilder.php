<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Beecom\Twisto\Gateway\Request;

use Beecom\Twisto\Gateway\Config\Config;
use Beecom\Twisto\Gateway\SubjectReader;
use Beecom\Twisto\Model\Logger\Logger;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Payment Data Builder
 */
class CaptureDataBuilder implements BuilderInterface
{
    /**
     * Order ID
     */
    const ORDER_ID = 'orderId';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    private $logger;

    protected $storeManager;

    /**
     * Constructor
     *
     * @param Config $config
     * @param SubjectReader $subjectReader
     */
    public function __construct(Config $config, SubjectReader $subjectReader, Logger $logger)
    {
        $this->config = $config;
        $this->subjectReader = $subjectReader;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $this->logger->debug(__METHOD__);
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $paymentAdditionalInfo = $paymentDO->getPayment()->getAdditionalInformation();
        $paymentAdditionalInfo['order_id'] = $paymentDO->getOrder()->getOrderIncrementId();
        return $paymentAdditionalInfo;
    }
}
