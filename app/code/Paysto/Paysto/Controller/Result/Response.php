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
/**
 * Process response
 *
 * Class Response
 */
class Response extends Action implements CsrfAwareActionInterface
{
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
     */
    public function __construct(
        Context $context,
        ResponseCommand $command,
        Logger $logger,
        LoggerInterface $loggerException
    ) {
        parent::__construct($context);

        $this->command = $command;
        $this->logger = $logger;
        $this->loggerException = $loggerException;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        $this->loggerException->debug('Response');

        $this->logger->debug($params);

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
