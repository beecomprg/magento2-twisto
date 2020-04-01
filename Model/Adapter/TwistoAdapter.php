<?php

namespace Beecom\Twisto\Model\Adapter;

use Beecom\Twisto\Gateway\Config\Config;
use Beecom\Twisto\Model\Logger\Logger;
use Twisto\Twisto;
use Twisto\Invoice;

/**
 * Class TwistoAdapter
 * Use \Magento\Twisto\Model\Adapter\TwistoAdapterFactory to create new instance of adapter.
 * @codeCoverageIgnore
 */
class TwistoAdapter
{
    private $logger;
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Config
     */
    private $client;

    /**
     * @param string $goId
     * @param string $publicKey
     * @param string $secretKey
     * @param string $environment
     */
    public function __construct($publicKey, $secretKey, Logger $logger)
    {
        $this->config = [
        'publicKey' => $publicKey,
        'secretKey' => $secretKey
      ];
        $this->client = new Twisto();
        $this->client->setPublicKey($publicKey);
        $this->client->setSecretKey($secretKey);
        $this->logger = $logger;
    }

    public function checkPayload($payload)
    {
        return $this->client->requestJson('POST', 'check/', $payload);
    }

    /**
     * @param $customer
     * @param $order
     * @param $previousOrders
     * @return string|null
     */
    public function getPayload($customer, $order, $previousOrders)
    {
        try {
            return $this->client->getCheckPayload($customer, $order, $previousOrders);
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
            return null;
        }
    }

    public function createInvoice($order)
    {
        try {
            $txn = $order->getPayment()->getAdditionalInformation();
            $invoice = Invoice::create($this->client, $txn['txn_id'], $order->getIncrementId());
            $invoice->activate();
            $this->logger->debug($invoice->invoice_id);
            return $invoice;
        } catch (\Twisto\Error $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * @param array $attributes
     * @return \Twisto\Result\Successful|\Twisto\Result\Error
     */
    public function capture(array $attributes)
    {
        $this->logger->debug(__METHOD__, $attributes);
        try {
            $invoice = Invoice::create($this->client, $attributes['txn_id'], $attributes['order_id']);
            $invoice->activate();
            $this->logger->debug($invoice->invoice_id);
            return $invoice;
        } catch (\Twisto\Error $e) {
            $this->logger->debug($e->getMessage());
        }
    }
}
