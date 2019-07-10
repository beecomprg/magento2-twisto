<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Beecom\Twisto\Gateway\Response;

use Beecom\Twisto\Gateway\SubjectReader;
use Beecom\Twisto\Model\Logger\Logger;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;

class TransactionIdHandler implements HandlerInterface
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    protected $logger;

    /**
     * TransactionIdHandler constructor.
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader,
        Logger $logger
    ) {
        $this->subjectReader = $subjectReader;
        $this->logger = $logger;
    }

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $this->logger->debug(__METHOD__);
        $this->logger->debug(__METHOD__, $response);
        if ($paymentDO->getPayment() instanceof Payment) {
            /** @var \Twisto\Transaction $transaction */
            $transaction = $this->subjectReader->readTransaction($response);

            /** @var Payment $orderPayment */
            $orderPayment = $paymentDO->getPayment();
            $this->setTransactionId(
                $orderPayment,
                $transaction
            );

            $this->setAdditionalInformation(
                $orderPayment,
                $transaction
            );

            $orderPayment->setIsTransactionClosed($this->shouldCloseTransaction());
            $closed = $this->shouldCloseParentTransaction($orderPayment);
            $orderPayment->setShouldCloseParentTransaction($closed);
        }
    }

    /**
     * @param Payment $orderPayment
     * @param \Twisto\Transaction $transaction
     * @return void
     */
    protected function setTransactionId(Payment $orderPayment, \Twisto\Http\Response $transaction)
    {
        $orderPayment->setTransactionId($transaction->json['id']);
    }

    /**
     * @param Payment $orderPayment
     * @param \Twisto\Transaction $transaction
     * @return void
     */
    protected function setAdditionalInformation(Payment $orderPayment, \Twisto\Http\Response $transaction)
    {
        $orderPayment->setAdditionalInformation('txn_id', $transaction->json['id']);
    }

    /**
     * Whether transaction should be closed
     *
     * @return bool
     */
    protected function shouldCloseTransaction()
    {
        return false;
    }

    /**
     * Whether parent transaction should be closed
     *
     * @param Payment $orderPayment
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function shouldCloseParentTransaction(Payment $orderPayment)
    {
        return false;
    }
}
