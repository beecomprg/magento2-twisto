<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Beecom\Twisto\Gateway\Validator;

/**
 * Class ResponseValidator
 */
class ResponseValidator extends GeneralResponseValidator
{
    /**
     * @return array
     */
    protected function getResponseValidators()
    {
        return array_merge(
            parent::getResponseValidators(),
            [
                function ($response) {
                    $this->logger->debug(__METHOD__);
                    $errorState = property_exists($response, 'errors') && count($response->errors) > 0;
                    $errors = [__('Wrong transaction status')];
                    if ($errorState) {
                        foreach ($response->errors as $error) {
                            $errors[] = __($error->message);
                        }
                    }
                    return [
                        $errorState,
                        $errors
//                        && in_array(
//                            $response->state,
//                            [PaymentStatus::AUTHORIZED, PaymentStatus::CREATED, PaymentStatus::PAID, PreAuthState::AUTHORIZED, PreAuthState::REQUESTED]
//                        )
                    ];
                }
            ]
        );
    }
}
