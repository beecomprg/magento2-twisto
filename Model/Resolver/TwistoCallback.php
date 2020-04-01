<?php
declare(strict_types=1);

namespace Beecom\Twisto\Model\Resolver;

use Beecom\Twisto\Model\Adapter\TwistoAdapterFactory;
use DateTime;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Twisto\Address;
use Twisto\Customer;
use Twisto\Item;
use Twisto\Order;

/**
 * CMS testimonials field resolver, used for GraphQL request processing
 */
class TwistoCallback implements ResolverInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var SessionManagerInterface
     */
    private $session;
    private $checkoutSession;
    protected $collectionFactory;
    protected $adapterFactory;
    protected $searchCriteriaBuilder;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * TwistoCallback constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param CheckoutSession $session
     * @param TwistoAdapterFactory $adapterFactory
     * @param CheckoutSession $checkoutSession
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $session,
        TwistoAdapterFactory $adapterFactory,
        CheckoutSession $checkoutSession,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GetCartForUser $getCartForUser,
        CartManagementInterface $cartManagement
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->session = $session;
        $this->adapterFactory = $adapterFactory;
        $this->checkoutSession = $checkoutSession;
        $this->collectionFactory = $collectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->getCartForUser = $getCartForUser;
        $this->cartManagement = $cartManagement;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $data = $args['twistoInput'];
        $guestCartId = $args['guestCartId'] ?? '';

        $customerId = $context->getUserId();
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();

        if ($guestCartId !== '') {
            $quote = $this->getCartForUser->execute($guestCartId, $customerId, $storeId);
        } else {
            $quote = $this->cartManagement->getCartForCustomer($customerId);
        }
        try {
            /** @var Quote $quote */
            $customerName = implode(" ", [$data['firstname'], $data['lastname']]);

            $customer = new Customer(
                $quote->getCustomerEmail(),
                $customerName,
                null,
                null, // max 14 chars
                $quote->getCustomerTaxvat()
            );
            $orderItems = $this->getOrderItems($quote);
            $order = new Order(
                new DateTime(),     // date_created
                $this->getBillingAddress($data), // billing_address
                $this->getShippingAddress($quote), // delivery_address
                $quote->getGrandTotal(),                // total_price_vat
                $orderItems        // items
            );
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('customer_email', $quote->getCustomerEmail());
            $collection->getSelect()->join(['payment' => 'sales_order_payment'], 'main_table.entity_id = payment.parent_id', 'payment.method')
                ->where('payment.method = "twisto"');
            $previousOrders = [];

            $payload = $this->adapterFactory->create($storeId)->getPayload($customer, $order, $previousOrders);

            return ['payload' => $payload];
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
    }

    protected function getOrderItems($quote)
    {
        /** @var Quote $quote */
        $shippingAddress = $quote->getShippingAddress();
        $shipping = $quote->getShippingAddress()->getShippingAmount();
        $orderItems = [
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
                'Twisto – Zboží ihned, platím za 14 dní', // name
                'payment',                                  // product_id
                1,                                          // quantity
                0,                                         // price_vat
                21                                          // vat
            )
        ];

        foreach ($quote->getAllItems() as $item) {
            //a product is simple or configurable but not a variant
            if (!$item->getParentItemId()) {
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

    /**
     * @param $object
     * @return Address
     * @throws \libphonenumber\NumberParseException
     */
    protected function getShippingAddress($object)
    {
        $phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        $address = $object->getShippingAddress();
        $addressName = implode(" ", [$address->getFirstname(), $address->getLastname()]);
        $phoneNumberObject = $phoneNumberUtil->parse($address->getTelephone(), 'CZ');
        $phoneNumberFormatted = $phoneNumberUtil->format($phoneNumberObject, \libphonenumber\PhoneNumberFormat::INTERNATIONAL);
        return new Address(
            substr($addressName, 0, 100),
            preg_replace('#\R+#', ' ', substr($address->getStreetFull(), 0, 100)),
            substr($address->getCity(), 0, 100),
            substr(preg_replace("/[^0-9]/", "", $address->getPostcode()), 0, 5),
            substr($address->getCountry(), 0, 100),
            $phoneNumberFormatted
        );
    }

    /**
     * @param $object
     * @return Address
     * @throws \libphonenumber\NumberParseException
     */
    protected function getBillingAddress($object)
    {
        $phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        $addressName = implode(" ", [$object['firstname'], $object['lastname']]);
        $phoneNumberObject = $phoneNumberUtil->parse($object['telephone'], 'CZ');
        $phoneNumberFormatted = $phoneNumberUtil->format($phoneNumberObject, \libphonenumber\PhoneNumberFormat::INTERNATIONAL);
        return new Address(
            substr($addressName, 0, 100),
            preg_replace('#\R+#', ' ', substr(implode(' ', $object['street']), 0, 100)),
            substr($object['city'], 0, 100),
            substr(preg_replace("/[^0-9]/", "", $object['postcode']), 0, 5),
            substr($object['country_id'], 0, 100),
            $phoneNumberFormatted
        );
    }
}
