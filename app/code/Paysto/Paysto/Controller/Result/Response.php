<?php
/**
 * Copyright © Paysto, Inc. All rights reserved.
 *
 */
namespace Paysto\Paysto\Controller\Result;

use Magento\Framework\App\Request;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\LayoutFactory;
use Paysto\Paysto\Gateway\Command\ResponseCommand;
use Magento\Payment\Model\Method\Logger;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Webapi\Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Action;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Checkout\Model\Session;

/**
 * Process response
 *
 * Class Response
 */
class Response extends Action implements CsrfAwareActionInterface
{
    /**
     * Redirect types.
     *
     * @var string
     */
    private static $cancelRedirectType = 'cancel';

    /**
     * @var string
     */
    private static $failureRedirectType = 'failure';

    /**
     * @var string
     */
    private static $successRedirectType = 'success';

    /**
     * Relative urls for different redirect types.
     *
     * @var string
     */
    private static $defaultRedirectUrl = 'checkout/cart';

    /**
     * @var string
     */
    private static $successRedirectUrl = 'checkout/onepage/success';

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var OrderFactory
     */
    private $order;

    /**
     * @var ResponseCommand
     */
    private $command;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var LoggerInterface
     */
    private $loggerException;

    /**
     * Response constructor.
     * @param Context $context
     * @param ResponseCommand $command
     * @param Logger $logger
     * @param LoggerInterface $loggerException
     * @param Session $checkoutSession
     * @param OrderFactory $order
     */
    public function __construct(
        Context $context,
        ResponseCommand $command,
        Logger $logger,
        LoggerInterface $loggerException,
        Session $checkoutSession,
        OrderFactory $order
    ) {
        parent::__construct($context);

        $this->command = $command;
        $this->logger = $logger;
        $this->loggerException = $loggerException;
        $this->checkoutSession = $checkoutSession;
        $this->order = $order;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        $this->logger->debug($params);

        // проверяем пост и поля
        if ($this->getRequest()->isPost()
            && !empty($this->getRequest()->getParam('x_invoice_num'))
        ){
            $order = $this->order->create()->loadByIncrementId($this->getRequest()->getParam('x_invoice_num'));
            // заказ найден и статус соответствует
            if ($order->getId()
                && in_array($order->getState(), [
                    Order::STATE_CANCELED,
                    Order::STATE_PROCESSING
                ])
            ){
                switch ($order->getState()){
                    case Order::STATE_CANCELED:
                        $status = self::$cancelRedirectType;
                        break;
                    case Order::STATE_PROCESSING:
                        $status = self::$successRedirectType;
                        break;
                    default:
                        $status = self::$successRedirectType;
                        break;
                }
                // редиректим покупателя
                return $this->resultRedirect($status);
            }
        }

        // обрабатываем заказ
        $response = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $response->setHeader('Content-type', 'text/plain');
        $response->setContents(json_encode(['success' => true]));
        try {
            $result = $this->command->execute(['response' => $params]);
        } catch (\Exception $e) {
            $response->setContents(json_encode(['success' => false]));
            $response->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
            $this->loggerException->critical($e);
            return $response;
        }
        return $response;
    }

    /**
     * Redirect by type
     *
     * @param $redirectType
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function resultRedirect($redirectType)
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $redirectUrl = self::$defaultRedirectUrl;

        switch ($redirectType) {
            case self::$successRedirectType:
                $redirectUrl = self::$successRedirectUrl;
                break;
            case self::$cancelRedirectType:
                $this->messageManager->addSuccessMessage(__('Your purchase process has been cancelled.'));
                $this->checkoutSession->setLastRealOrderId($this->getRequest()->getParam('x_invoice_num'));
                $this->checkoutSession->restoreQuote();
                break;
            case self::$failureRedirectType:
            default:
                $this->messageManager
                    ->addErrorMessage(__('Something went wrong while processing your order. Please try again later.'));
        }

        return $resultRedirect->setPath($redirectUrl);
    }

    /**
     * Create exception in case CSRF validation failed.
     * Return null if default exception will suffice.
     *
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Perform custom request validation.
     * Return null if default validation is needed.
     *
     * @param RequestInterface $request
     *
     * @return bool|null
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
