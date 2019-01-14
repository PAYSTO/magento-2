<?php
/**
 * Copyright © Paysto, Inc. All rights reserved.
 *
 */
namespace Paysto\Paysto\Gateway\Command;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order\Payment;

/**
 * Class CaptureCommand
 */
class CaptureCommand implements CommandInterface
{
    /**
     * Executes command basing on business object
     *
     * @param array $commandSubject
     * @return void
     */
    public function execute(array $commandSubject)
    {
    }
}
