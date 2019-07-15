<?php

namespace Beecom\Twisto\Block;

use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;


/**
 * Class Info
 */
class Head extends \Magento\Framework\View\Element\Template
{
    protected $config;

    public function __construct(
        Context $context,
        ScopeConfigInterface $config,
        array $data = []
    )
    {
        $this->config = $config;
        parent::__construct($context, $data);
    }

    public function getPublicKey()
    {
        return $this->config->getValue('payment/twisto/public_key');
    }
}
