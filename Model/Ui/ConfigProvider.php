<?php

namespace Beecom\Twisto\Model\Ui;

use Beecom\Twisto\Gateway\Config\Config;
use Beecom\Twisto\Model\Adapter\TwistoAdapterFactory;
use Beecom\Twisto\Model\Logger\Logger;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'twisto';

    protected $_methodCodes = [
        self::CODE
    ];

    /**
     * @var Config
     */
    private $config;

    /**
     * @var TwistoAdapterFactory
     */
    private $adapterFactory;

    /**
     * @var string
     */
    private $paymentDO;
    /**
     * @var string
     */
    private $gwUrl;

    /**
     * @var SessionManagerInterface
     */
    private $session;

    protected $storeManager;

    protected $checkoutSession;

    protected $logger;

    protected $orderRepository;

    protected $searchCriteriaBuilder;

    public function __construct(
        Config $config,
        TwistoAdapterFactory $adapterFactory,
        SessionManagerInterface $session,
        StoreManagerInterface $storeManager,
        CheckoutSession $checkeckoutSession,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Logger $logger
    )
    {
        $this->config = $config;
        $this->adapterFactory = $adapterFactory;
        $this->session = $session;
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkeckoutSession;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $storeId = $this->session->getStoreId();
        $paymentMethods = ['payment' => []];
        foreach ($this->_methodCodes as $methodCode) {
            $paymentMethods['payment'][$methodCode] = [
                'isActive' => $this->config->isActive($storeId),
                'publicKey' => $this->config->getPublicKey($storeId)//,
                //'payload' => $this->getPayload(),
            ];
        }
        return $paymentMethods;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createPayment()
    {
        if (empty($this->paymentDO)) {
            $storeId = $this->session->getStoreId();

            $this->paymentDO = $this->adapterFactory->create($storeId)
                ->createPayload($customer, $order, $previousOrders);
        }

        return $this->paymentDO;
    }

    /**
     * Generate a new client token if necessary
     * @return string
     */
    public function getPayload()
    {
        if (empty($this->gwUrl)) {
            $this->createPayment();
            $this->gwUrl = $this->paymentDO;
        }

        return $this->gwUrl;
    }

}
