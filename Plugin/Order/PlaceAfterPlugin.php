<?php

namespace Beecom\Twisto\Plugin\Order;

use Beecom\Twisto\Model\Adapter\TwistoAdapterFactory;
use Beecom\Twisto\Gateway\Config\Config;

class  PlaceAfterPlugin
{
    protected $adapterFactory;

    protected $config;

    public function __construct(
        TwistoAdapterFactory $adapterFactory,
        Config $config
    )
    {
        $this->adapterFactory = $adapterFactory;
        $this->config = $config;
    }


    /**
     * @param \Magento\Sales\Api\OrderManagementInterface $orderManagementInterface
     * @param \Magento\Sales\Model\Order\Interceptor $order
     * @return $order
     */
    public function afterPlace(\Magento\Sales\Api\OrderManagementInterface $orderManagementInterface, $order)
    {
        if($this->config->isAutoInvoiceActivationEnabled($order->getStoreId())
            && $order->getPayment()->getMethod() == 'twisto'){
            $this->adapterFactory->create($order->getStoreId())
                ->createInvoice($order);
        }

        return $order;
    }
}
