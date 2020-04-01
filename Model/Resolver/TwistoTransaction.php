<?php
declare(strict_types=1);

namespace Beecom\Twisto\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartManagementInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;

/**
 * CMS testimonials field resolver, used for GraphQL request processing
 */
class TwistoTransaction implements ResolverInterface
{
    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * TwistoTransaction constructor.
     * @param GetCartForUser $getCartForUser
     * @param CartManagementInterface $cartManagement
     */
    public function __construct(
        GetCartForUser $getCartForUser,
        CartManagementInterface $cartManagement
    ) {
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

        try {
            $guestCartId = $args['guestCartId'] ?? '';

            $customerId = $context->getUserId();
            $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();

            if ($guestCartId !== '') {
                $quote = $this->getCartForUser->execute($guestCartId, $customerId, $storeId);
            } else {
                $quote = $this->cartManagement->getCartForCustomer($customerId);
            }

            $payment = $quote->getPayment();
            $payment->setAdditionalInformation(['txn_id' => $args['transaction_id']]);
            $payment->save();

            return [
                'transaction_id' => $args['transaction_id'],
                'status' => true
            ];
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
    }
}
