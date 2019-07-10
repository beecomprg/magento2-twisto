<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Beecom\Twisto\Controller\Payment;

//use Beecom\Twisto\Gateway\Command\GetPaymentNonceCommand;
use Beecom\Twisto\Model\Adapter\TwistoAdapterFactory;
use Beecom\Twisto\Model\Logger\Logger;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\RemoteServiceUnavailableException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Webapi\Exception;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;

/**
 * Class GetNonce
 */
class Status extends Action
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var SessionManagerInterface
     */
    private $session;

    /**
     * @var GetPaymentNonceCommand
     */
    private $command;

    /**
     * @var GetPaymentNonceCommand
     */
    private $adapterFactory;

    protected $_invoiceService;

    protected $_transaction;

    protected $_orderRepository;

    protected $_invoiceSender;

    protected $_resource;

    protected $connection;

    protected $checkoutSession;

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param SessionManagerInterface $session
     * @param GetPaymentNonceCommand $command
     */
    public function __construct(
    Context $context,
    Logger $logger,
    SessionManagerInterface $session,
    TwistoAdapterFactory $adapterFactory,
    \Magento\Sales\Model\Service\InvoiceService $invoiceService,
    \Magento\Framework\DB\Transaction $transaction,
    \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
    InvoiceSender $_invoiceSender,
    \Magento\Framework\App\ResourceConnection $resource,
    \Magento\Checkout\Model\Session $checkoutSession
  ) {
        $this->_logger = $logger;
        $this->session = $session;
        $this->adapterFactory = $adapterFactory;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->_orderRepository = $orderRepository;
        $this->_invoiceSender = $_invoiceSender;
        $this->_resource = $resource;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context);
//    $this->command = $command;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        if (!$this->getRequest()->isPost()) {
            return;
        }
        try {
            $order = $this->checkoutSession->getLastRealOrder();
            $orderAdditionalInformation = $order->getPayment()->getAdditionalInformation();
            return $response->setData(['gw_url' => $orderAdditionalInformation['gw_url'], 'status' => 'success']);
        } catch (RemoteServiceUnavailableException $e) {
            $this->_logger->critical($e->getMessage());
            $this->getResponse()->setStatusHeader(503, '1.1', 'Service Unavailable')->sendResponse();
            /** @todo eliminate usage of exit statement */
            exit;
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            $this->getResponse()->setHttpResponseCode(500);
        }
    }

    /**
     * Return response for bad request
     * @param ResultInterface $response
     * @return ResultInterface
     */
    private function processBadRequest(ResultInterface $response)
    {
        $response->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
        $response->setData(['message' => __('Sorry, but something went wrong')]);

        return $response;
    }
}
