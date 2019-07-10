<?php

namespace Beecom\Twisto\Block;

use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\ConfigurableInfo;
use Magento\Payment\Gateway\ConfigInterface;
use Beecom\Twisto\Gateway\Config\Config as GatewayConfig;


/**
 * Class Info
 */
class Head extends ConfigurableInfo
{
    protected $config;

    public function __construct(
        Context $context,
        ConfigInterface $config,
        array $data = []
    )
    {
        $this->config = $config;
        parent::__construct($context, $config, $data);
    }

    protected function getPublicKey()
    {
        return $this->config->getValue('payment/twisto/public_key');
    }
}
