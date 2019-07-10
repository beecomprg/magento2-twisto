<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Beecom\Twisto\Gateway\Validator;

/**
 * Processes errors codes from Twisto response.
 */
class ErrorCodeValidator
{
    /**
     * Invokes validation.
     *
     * @param Successful|Error $response
     * @return array
     */
    public function __invoke($response)
    {
        if (!$response instanceof Error) {
            return [true, [__('Transaction is successful.')]];
        }

        return [false, $this->getErrorCodes($response['errors'])];
    }

    /**
     * Retrieves list of error codes from Twisto response.
     *
     * @param ErrorCollection $collection
     * @return array
     */
    private function getErrorCodes($collection)
    {
        $result = [];
        /** @var Validation $error */
        foreach ($collection as $error) {
            $result[] = $error['message'];
        }

        return $result;
    }
}
