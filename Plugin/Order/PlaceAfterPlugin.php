<?php

namespace Beecom\Twisto\Plugin\Order;

use Beecom\Twisto\Model\Adapter\TwistoAdapterFactory;

class  PlaceAfterPlugin
{
    protected $adapterFactory;

    public function __construct(
        TwistoAdapterFactory $adapterFactory
    )
    {
        $this->adapterFactory = $adapterFactory;
    }


    /**
     * @param \Magento\Sales\Api\OrderManagementInterface $orderManagementInterface
     * @param \Magento\Sales\Model\Order\Interceptor $order
     * @return $order
     */
    public function afterPlace(\Magento\Sales\Api\OrderManagementInterface $orderManagementInterface, $order)
    {
        if($order->getPayment()->getMethod() == 'twisto'){
            $this->adapterFactory->create($order->getStoreId())
                ->createInvoice($order);
        }

        return $order;
    }
}
