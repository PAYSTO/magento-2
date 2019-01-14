<?php
/**
 * Copyright © Paysto, Inc. All rights reserved.
 *
 */
namespace Paysto\Paysto\Gateway\Command;

use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Payment\Model\Method\Logger;

/**
 * Class ResponseCommand
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ResponseCommand implements CommandInterface
{
    const ACCEPT_COMMAND = 'accept_command';

    const CANCEL_COMMAND = 'cancel_command';

    const STATUS_ACCEPT = 1;
    const STATUS_DECLINE = 2;
    const STATUS_ERROR = 3;

    /**
     * Transaction result codes map onto commands
     *
     * @var array
     */
    static private $commandsMap = [
        self::STATUS_ERROR => self::CANCEL_COMMAND,
        self::STATUS_DECLINE => self::CANCEL_COMMAND,
        self::STATUS_ACCEPT => self::ACCEPT_COMMAND
    ];

    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var OrderFactory
     */
    private $order;

    /**
     * @var PaymentDataObjectFactory
     */
    private $paymentDataObjectFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param CommandPoolInterface $commandPool
     * @param ValidatorInterface $validator
     * @param OrderFactory $order
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param Logger $logger
     */
    public function __construct(
        CommandPoolInterface $commandPool,
        ValidatorInterface $validator,
        OrderFactory $order,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        Logger $logger
    ) {
        $this->commandPool = $commandPool;
        $this->validator = $validator;
        $this->order = $order;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->logger = $logger;
    }

    /**
     * @param array $commandSubject
     *
     * @return void
     * @throws CommandException
     */
    public function execute(array $commandSubject)
    {
        $this->logger->debug($commandSubject);

        $response = SubjectReader::readResponse($commandSubject);
        $result = $this->validator->validate($commandSubject);

        if (!$result->isValid()) {
            $this->logger->debug(['ResponseCommandError' => $result->getFailsDescription()]);
            throw new CommandException(
                $result->getFailsDescription()
                ? __(implode(', ', $result->getFailsDescription()))
                : __('Gateway response is not valid.')
            );
        }

        $order = $this->order->create()->loadByIncrementId($response['x_invoice_num']);

        $actionCommandSubject = [
            'response' => $response,
            'payment' => $this->paymentDataObjectFactory->create(
                $order->getPayment()
            )
        ];

        $command = $this->commandPool->get(
            self::$commandsMap[
            $response['x_response_code']
            ]
        );

        $command->execute($actionCommandSubject);
    }
}
