<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Beecom\Twisto\Gateway\Validator;

use Beecom\Twisto\Gateway\SubjectReader;
use Beecom\Twisto\Model\Logger\Logger;
use Twisto\Definition\Response\PaymentStatus;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class GeneralResponseValidator extends AbstractValidator
{
    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var SubjectReader
     */
    protected $logger;

    /**
     * @var ErrorCodeValidator
     */
    private $errorCodeValidator;

    /**
     * Constructor
     *
     * @param ResultInterfaceFactory $resultFactory
     * @param SubjectReader $subjectReader
     * @param ErrorCodeValidator $errorCodeValidator
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        SubjectReader $subjectReader,
        ErrorCodeValidator $errorCodeValidator,
        Logger $logger

    ) {
        parent::__construct($resultFactory);
        $this->subjectReader = $subjectReader;
        $this->errorCodeValidator = $errorCodeValidator;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function validate(array $validationSubject)
    {
        /** @var Successful|Error $response */
        $response = $this->subjectReader->readResponseObject($validationSubject);

        $isValid = true;
        $errorMessages = [];

        foreach ($this->getResponseValidators() as $validator) {
            $validationResult = $validator($response);

            if (!$validationResult[0]) {
                $isValid = $validationResult[0];
                $errorMessages = array_merge($errorMessages, $validationResult[1]);
            }
        }
        $this->logger->debug(__METHOD__, $errorMessages);
        return $this->createResult($isValid, $errorMessages);
    }

    /**
     * @return array
     */
    protected function getResponseValidators()
    {
        return [
            function ($response) {
                $this->logger->debug(__METHOD__);
                return [
                    array_key_exists('id', $response)
                    && in_array(
                        $response['state'],
                        [PaymentStatus::AUTHORIZED, PaymentStatus::CREATED, PaymentStatus::PAID, 'AUTHORIZED', 'REQUESTED']
                    ),
                    [__('Twisto error response.')]
                ];
            },
            $this->errorCodeValidator
        ];
    }
}
