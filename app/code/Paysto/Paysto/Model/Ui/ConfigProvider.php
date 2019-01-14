<?php
/**
 * Copyright © Paysto, Inc. All rights reserved.
 *
 */
namespace Paysto\Paysto\Model\Ui;

use Magento\Framework\UrlInterface;
use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const PAYSTO_CODE = 'paysto';
    const TRANSACTION_DATA_URL = 'paysto/redirect/gettransactiondata';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Constructor
     *
     * @param UrlInterface $urlBuilder
     */
    public function __construct(UrlInterface $urlBuilder)
    {
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::PAYSTO_CODE => [
                    'transactionDataUrl' => $this->urlBuilder->getUrl(self::TRANSACTION_DATA_URL, ['_secure' => true])
                ]
            ]
        ];
    }
}
