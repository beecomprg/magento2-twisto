<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Beecom\Twisto\Gateway\Data\Order;

use Magento\Payment\Gateway\Data\Order\AddressAdapterFactory;
use Magento\Payment\Gateway\Data\Order\OrderAdapter as CoreOrderAdapter;
use Magento\Sales\Model\Order;

/**
 * Class OrderAdapter
 */
class OrderAdapter extends CoreOrderAdapter
{
    /**
     * @var Order
     */
    private $order;

    /**
     * @var AddressAdapter
     */
    private $addressAdapterFactory;

    /**
     * @param Order $order
     * @param AddressAdapterFactory $addressAdapterFactory
     */
    public function __construct(
        Order $order,
        AddressAdapterFactory $addressAdapterFactory
    ) {
        parent::__construct($order, $addressAdapterFactory);
        $this->order = $order;
        $this->addressAdapterFactory = $addressAdapterFactory;
    }

    /**
     * Returns currency code
     *
     * @return string
     */
    public function getShippingAmount()
    {
        return $this->order->getShippingAmount();
    }

    /**
     * Returns currency code
     *
     * @return string
     */
    public function getData($field = null)
    {
        return ($field) ? $this->order->getData($field) : $this->order->getData();
    }
}
