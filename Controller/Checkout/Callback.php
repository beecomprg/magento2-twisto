<?php

namespace Beecom\Twisto\Controller\Checkout;

use Beecom\Twisto\Model\Adapter\TwistoAdapterFactory;
use Beecom\Twisto\Model\Logger\Logger;
use DateTime;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Twisto\Address;
use Twisto\Customer;
use Twisto\Item;
use Twisto\Order;
use Magento\Framework\Api\SearchCriteriaBuilder;


/**
 * Class GetNonce
 */
class Callback extends Action
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SessionManagerInterface
     */
    private $session;

    /**
     * @var GetPaymentNonceCommand
     */
    private $command;

    private $checkoutSession;

    protected $orderPaymentRepository;

    protected $orderRepository;

    protected $searchCriteriaBuilder;

    protected $collectionFactory;

    public function __construct(
    Context $context,
    Logger $logger,
    \Magento\Checkout\Model\Session $session,
    TwistoAdapterFactory $adapterFactory,
    CheckoutSession $checkoutSession,
    \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
    SearchCriteriaBuilder $searchCriteriaBuilder
  ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->session = $session;
        $this->adapterFactory = $adapterFactory;
        $this->checkoutSession = $checkoutSession;
        $this->collectionFactory = $collectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
//    $this->command = $command;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $storeId = $this->session->getStoreId();
            $quote = $this->checkoutSession->getQuote();
            $billingAddress = $quote->getBillingAddress();
            $customerName = implode(" ", [$billingAddress->getFirstname(), $quote->getLastname()]);

            $customer = new Customer(
                $billingAddress->getEmail(),
                $customerName,
                null,
                null, // max 14 chars
                $quote->getCustomerTaxvat()
            );
            $orderItems = $this->getOrderItems($quote);
            $order = new Order(
                new DateTime(),     // date_created
                $this->getAddress($quote, 'billing'), // billing_address
                $this->getAddress($quote, 'shipping'), // delivery_address
                $quote->getGrandTotal(),                // total_price_vat
                $orderItems        // items
            );
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('customer_email', $quote->getCustomerEmail());
            $collection->getSelect()->join(array('payment' => 'sales_order_payment'), 'main_table.entity_id = payment.parent_id', 'payment.method')
                ->where('payment.method = "twisto"');
            $previousOrders = [];
//            foreach ($collection->getItems() as $previousOrder){
//
//                $prevOrderItems = $this->getOrderItems($previousOrder);
//                $prevOrder = new Order(
//                    new DateTime(),     // date_created
//                    $this->getAddress($previousOrder, 'billing'), // billing_address
//                    $this->getAddress($previousOrder, 'shipping'), // delivery_address
//                    $previousOrder->getGrandTotal(),                // total_price_vat
//                    $prevOrderItems        // items
//                );
//                $orderDate = DateTime::createFromFormat('Y-m-d H:i:s', $previousOrder->getCreatedAt());
//                $previousOrders[] = new Order(
//                    $orderDate,
//                    $prevOrder->billing_address,
//                    $prevOrder->delivery_address,
//                    $prevOrder->total_price_vat,
//                    $orderItems
//                );
//            }

            $payload = $this->adapterFactory->create($storeId)
        ->getPayload($customer, $order, $previousOrders);
            //$this->logger->debug('status',$status);

            return $response->setData(['payload' => $payload, 'status' => 'success']);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        }
    }

    protected function getOrderItems($quote){
        $shippingAddress = $quote->getShippingAddress();
        $shipping = $quote->getShippingAddress()->getShippingAmount();
        $orderItems = array(
            new Item(
                Item::TYPE_SHIPMENT,     // type
                $shippingAddress->getShippingDescription(),                  // name
                'shipment',                     // product_id
                1,                              // quantity
                $shipping,                            // price_vat
                21                              // vat
            ),
            new Item(
                Item::TYPE_PAYMENT,                  // type
                'Twisto – Zboží inhed, platím za 14 dní', // name
                'payment',                                  // product_id
                1,                                          // quantity
                0,                                         // price_vat
                21                                          // vat
            ),
//TODO do we need this?
//                new Item(
//                    Item::TYPE_ROUND,         // type
//                    'Zaokrouhlení',                  // name
//                    'round',                         // product_id
//                    1,                               // quantity
//                    -0.31,                           // price_vat
//                    0                                // vat
//                ),
        );

        foreach ($quote->getAllItems() as $item) {
            //a product is simple or configurable but not a variant
            if (!$item->getParentId()) {
                $orderItems[] = new Item(
                    Item::TYPE_DEFAULT,      // type
                    $item->getName(),              // name
                    $item->getSku(),               // product_id (ID produktu - musí být unikátní v rámci objednávky)
                    $item->getQty(),               // quantity
                    $item->getRowTotalInclTax(),          // price_vat (celková cena všech kusů dané položky)
                    21,                       // vat FIXME insert correct tax rate
                    $item->getBeecomEan(),         // ean_code (čárový kód produktu)
                    null,                // isbn_code
                    null,                // issn_code
                    3808           // heureka_category (ID Heureka kategorie)
                );
            }
        }
        return $orderItems;
    }

    protected function getAddress($object, $type){
        $addressType = 'get'. ucfirst($type) . 'Address';
        $address = $object->$addressType();
        $addressName = implode(" ", [$address->getFirstname(), $address->getLastname()]);
        return new Address(
            substr($addressName, 0, 100),
            preg_replace('#\R+#', ' ', substr($address->getStreetFull(), 0, 100)),
            substr($address->getCity(), 0, 100),
            substr($address->getPostcode(), 0, 100),
            substr($address->getCountry(), 0, 100),
            $address->getTelephone()
        );
    }
}
