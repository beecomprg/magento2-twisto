<?php

namespace Beecom\Twisto\Observer;

use Beecom\Twisto\Model\Logger\Logger;
use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Class DataAssignObserver
 */
class DataAssignObserver extends AbstractDataAssignObserver
{
    const TXN_ID = 'txn_id';
    const INVOICE_ID = 'invoice_id';

    /**
     * @var array
     */
    protected $additionalInformationList = [
        self::TXN_ID,
        self::INVOICE_ID,

    ];

    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
       * @param Observer $observer
       * @return void
       */
    public function execute(Observer $observer)
    {
        $this->logger->debug(__CLASS__);
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($this->additionalInformationList as $additionalInformationKey) {
            if (isset($additionalData[$additionalInformationKey])) {
                $paymentInfo->setAdditionalInformation(
                    $additionalInformationKey,
                    $additionalData[$additionalInformationKey]
                );
            }
        }
    }
}
