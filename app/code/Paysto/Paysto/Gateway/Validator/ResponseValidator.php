<?php
/**
 * Copyright © Paysto, Inc. All rights reserved.
 *
 */
namespace Paysto\Paysto\Gateway\Validator;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Framework\App\Request;
use Magento\Sales\Api\OrderRepositoryInterface;
use Paysto\Paysto\Gateway\Request\HtmlRedirect\OrderDataBuilder;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Sales\Model\OrderFactory;

class ResponseValidator extends AbstractValidator
{
    /**
     * @var Request\Http
     */
    private $request;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var OrderFactory
     */
    private $order;

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param Request\Http $request
     * @param RemoteAddress $remoteAddress
     * @param OrderFactory $order
     * @param ConfigInterface $config
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        Request\Http $request,
        RemoteAddress $remoteAddress,
        OrderFactory $order,
        ConfigInterface $config
    ) {
        parent::__construct($resultFactory);

        $this->request = $request;
        $this->config = $config;
        $this->order = $order;
        $this->remoteAddress = $remoteAddress;
    }

    /**
     * Performs domain-related validation for business object
     *
     * @param array $validationSubject
     * @return ResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(array $validationSubject)
    {
        $orderIsNotFound = function () {
            $result = true;
            try {
                $order = $this->order->create()->loadByIncrementId($this->request->getPost('x_invoice_num'));
                if (!$order->getId())
                    throw new NotFoundException(
                    __('Order is not found.')
                );
            } catch (NotFoundException $e) {
                $result = false;
            }

            return [
                $result,
                'Order is not found.'
            ];
        };

        $statements = [
            function () {
                return [
                    $this->request->isPost(),
                    'Wrong request type.'
                ];
            },
/*            function () {
                return [
                    preg_match('/\.paysto\.com$/', (string)$this->remoteAddress->getRemoteHost()),
                    'Domain can\'t be validated as paysto.'
                ];
            },*/
            function () {
                $ips = explode(',', $this->config->getValue('paysto_server_ip'));
                if (count($ips) < 2){
                    return [true,''];
                }
                return [
                    in_array((string)$this->remoteAddress->getRemoteAddress(), $ips),
                    'IP can\'t be validated as paysto.'
                ];
            },
            function () {
                return [
                    !(empty($this->request->getPost())
                    || empty($this->request->getPost('x_response_code'))
                    || empty($this->request->getPost('x_trans_id'))
                    || empty($this->request->getPost('x_amount'))
                    || empty($this->request->getPost('x_MD5_Hash'))
                    || empty($this->request->getPost('x_invoice_num'))),
                    'Request doesn\'t contain required fields.'
                ];
            },
            $orderIsNotFound,
            function () {
                $order = $this->order->create()->loadByIncrementId($this->request->getPost('x_invoice_num'));
                $storeId = $order->getStoreId();
                $hash = implode('', [
                    $this->config->getValue('md5_secret', $storeId),
                    $this->config->getValue('merchant_id', $storeId),
                    $this->request->getPost('x_trans_id'),
                    $this->request->getPost('x_amount'),
                ]);
                return [
                    hash('md5', $hash) == $this->request->getPost('x_MD5_Hash'),
                    'Transaction password is wrong.'
                ];
            }
        ];

        /** @var \Closure $statement */
        foreach ($statements as $statement) {
            $result = $statement();
            if (!array_shift($result)) {
                return $this->createResult(false, [__(array_shift($result))]);
            }
        }

        return $this->createResult(true);
    }
}
