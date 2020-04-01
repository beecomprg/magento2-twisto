<?php
declare(strict_types=1);

namespace Beecom\Twisto\Model\Resolver;

use Beecom\Twisto\Model\Adapter\TwistoAdapterFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * CMS testimonials field resolver, used for GraphQL request processing
 */
class TwistoCheck implements ResolverInterface
{
    protected $adapterFactory;

    /**
     * TwistoCheck constructor.
     * @param TwistoAdapterFactory $adapterFactory
     */
    public function __construct(
        TwistoAdapterFactory $adapterFactory
    ) {
        $this->adapterFactory = $adapterFactory;
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
        $data = $args['payload'];
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        try {

            $payload = $this->adapterFactory->create($storeId)->checkPayload($data);

            return $payload;
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
    }
}
