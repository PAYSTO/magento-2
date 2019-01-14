<?php
/**
 * Copyright © Paysto, Inc. All rights reserved.
 *
 */
namespace Paysto\Paysto\Controller\Result;

use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

/**
 * Redirects to checkout cart page with appropriate message
 *
 * Class Index
 */
class Index extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
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
     * Index constructor.
     * @param Context $context
     * @param Session $checkoutSession
     * @param OrderFactory $order
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        OrderFactory $order
    ) {
        parent::__construct($context);

        $this->checkoutSession = $checkoutSession;
        $this->order = $order;
    }

    /**
     * Index controller check return POST and make redirect
     *
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost()
            || empty($this->getRequest()->getParam('x_invoice_num')))
        {
            $this->messageManager
                ->addErrorMessage(__('Return post error.'));
            return $this->resultRedirect(self::$failureRedirectType);
        }

        $order = $this->order->create()->loadByIncrementId($this->getRequest()->getParam('x_invoice_num'));
        if (!$order->getId())
            return $this->resultRedirect(self::$failureRedirectType);

        switch ($order->getState()){
            case Order::STATE_CANCELED:
                $status = self::$cancelRedirectType;
                break;
            case Order::STATE_PROCESSING:
                $status = self::$successRedirectType;
                break;
            default:
                $status = self::$failureRedirectType;
                break;
        }

        return $this->resultRedirect($status);
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
