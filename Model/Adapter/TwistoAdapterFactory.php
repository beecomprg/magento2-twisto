<?php

namespace Beecom\Twisto\Model\Adapter;

use Beecom\Twisto\Gateway\Config\Config;
use Beecom\Twisto\Model\Logger\Logger;
use Magento\Framework\ObjectManagerInterface;

/**
 * This factory is preferable to use for Twisto adapter instance creation.
 */
class TwistoAdapterFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Config
     */
    private $config;

    private $logger;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param Config $config
     */
    public function __construct(
      ObjectManagerInterface $objectManager,
      Config $config,
      Logger $logger)
    {
        $this->config = $config;
        $this->objectManager = $objectManager;
        $this->logger = $logger;
    }

    /**
     * Creates instance of Twisto Adapter.
     *
     * @param int $storeId if null is provided as an argument, then current scope will be resolved
     * by \Magento\Framework\App\Config\ScopeCodeResolver (useful for most cases) but for adminhtml area the store
     * should be provided as the argument for correct config settings loading.
     * @return TwistoAdapter
     */
    public function create($storeId = null)
    {
        return $this->objectManager->create(
            TwistoAdapter::class,
            [
                'publicKey' => $this->config->getValue(Config::KEY_PUBLIC_KEY, $storeId),
                'secretKey' => $this->config->getValue(Config::KEY_SECRET_KEY, $storeId),
                'logger' => $this->logger
            ]
        );
    }
}
