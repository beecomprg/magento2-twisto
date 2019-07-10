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
class Notify extends Action
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

    /**
     * Notify constructor.
     * @param Context $context
     * @param Logger $logger
     * @param SessionManagerInterface $session
     * @param TwistoAdapterFactory $adapterFactory
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\DB\Transaction $transaction
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param InvoiceSender $_invoiceSender
     * @param \Magento\Framework\App\ResourceConnection $resource
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
    \Magento\Framework\App\ResourceConnection $resource

//    GetPaymentNonceCommand $command
  ) {
        $this->_logger = $logger;
        $this->session = $session;
        $this->adapterFactory = $adapterFactory;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->_orderRepository = $orderRepository;
        $this->_invoiceSender = $_invoiceSender;
        $this->_resource = $resource;
        parent::__construct($context);
//    $this->command = $command;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $id = $this->getRequest()->getParam('id');
            $storeId = $this->session->getStoreId();
            $this->_resource->getConnection('core_write');
            $table=$this->_resource->getTableName('sales_order_payment');
            $orderId = $this->getConnection()->fetchRow('SELECT parent_id FROM ' . $table . ' where additional_information like "%' . $id . '%"');
            $order = $this->_orderRepository->get($orderId['parent_id']);
            if ($order->canInvoice()) {
                $invoice = $this->_invoiceService->prepareInvoice($order);
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                $invoice->register();
                $invoice->save();
                $transactionSave = $this->_transaction->addObject(
                    $invoice
                  )->addObject(
                    $invoice->getOrder()
                  );
                $transactionSave->save();
                try {
                    $this->_invoiceSender->send($invoice);
                } catch (Exception $exception){
                    $this->logger->critical($exception->getMessage());
                }
                //send notification code
                $order->addStatusHistoryComment(
            __('Notified customer about invoice #%1.', $invoice->getId())
          )
            ->setIsCustomerNotified(true)
            ->save();
            }
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

    protected function getConnection()
    {
        if (!$this->connection) {
            $this->connection = $this->_resource->getConnection('core_write');
        }
        return $this->connection;
    }
}
