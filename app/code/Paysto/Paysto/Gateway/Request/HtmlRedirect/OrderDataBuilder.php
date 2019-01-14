<?php
/**
 * Copyright © Paysto, Inc. All rights reserved.
 *
 */
namespace Paysto\Paysto\Gateway\Request\HtmlRedirect;

use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Class OrderDataBuilder
 */
class OrderDataBuilder implements BuilderInterface
{

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var UrlInterface
     */
    private $urlHelper;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * Constructor
     *
     * @param ConfigInterface $config
     * @param UrlInterface $urlHelper
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        ConfigInterface $config,
        UrlInterface $urlHelper,
        ResolverInterface $localeResolver
    ) {
        $this->config = $config;
        $this->urlHelper = $urlHelper;
        $this->localeResolver = $localeResolver;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        $order = $paymentDO->getOrder();
        $storeId = $order->getStoreId();

        $address = $order->getBillingAddress();

        $shipping = $order->getShippingAddress();

        $result = [
            'x_description' => __('Order payment #%1',$order->getOrderIncrementId()),
            'x_login' => $this->config->getValue('merchant_id', $storeId),
            'x_amount' => sprintf('%.2F', $order->getGrandTotalAmount()),
            'x_currency_code' => $order->getCurrencyCode(),
            'x_invoice_num' => $order->getOrderIncrementId(),
            'x_email' => $address->getEmail(),
            'x_fp_sequence' => $order->getOrderIncrementId(),
            'x_fp_timestamp' => time(),
            'x_first_name' => $address->getFirstname(),
            'x_last_name' => $address->getLastname(),
            'x_address' => $address->getStreetLine1().' '.$address->getStreetLine2(),
            'x_city' => $address->getCity(),
            'x_state' => $address->getRegionCode(),
            'x_country' => $address->getCountryId(),
            'x_ship_to_first_name' => $shipping->getFirstname(),
            'x_ship_to_last_name' => $shipping->getLastname(),
            'x_ship_to_address' => $shipping->getStreetLine1().' '.$address->getStreetLine2(),
            'x_phone' => $shipping->getTelephone(),
            'x_cust_id' => $order->getCustomerId(),
            'x_ship_to_city' => $shipping->getCity(),
            'x_ship_to_zip' => $shipping->getPostcode(),
            'x_ship_to_state' => $shipping->getRegionCode(),
            'x_ship_to_country' => $shipping->getCountryId(),
            'x_relay_response' => 'TRUE',
            'x_relay_url' => $this->urlHelper->getUrl('paysto/result/response')
        ];

        $cartItems = [];

        foreach ($order->getItems() as $item){
            $name = str_replace(['+','"','«','»',"'",'/','\\'],'', $item->getName());
            $qty = number_format($item->getQtyOrdered());
            $product = [
                substr($item->getSku(),0,31),
                substr($name,0,31),
                $name,
                $qty,
                number_format($item->getPriceInclTax() - $item->getDiscountAmount()/$qty,2,'.',''),
                $item->getTaxPercent() > 0 ? 1 : 0
            ];
            $cartItems[] = implode('<|>', $product);
        }

        $result['x_line_item'] = $cartItems;

        $result['x_fp_hash'] = $this->getSignature($result, $storeId);

        return [
            'fields' => $result,
            'action' => $this->config->getValue('gateway_url', $storeId)
        ];
    }

    /**
     * Returns signature
     *
     * @param array $request
     * @param int $storeId
     * @return null|string
     */
    private function getSignature(array $request, $storeId)
    {
        $fieldsToSign =  ['x_login','x_fp_sequence','x_fp_timestamp','x_amount','x_currency_code'];

        $sign = [];
        foreach ($fieldsToSign as $field) {
            if (array_key_exists($field, $request)) {
                $sign[] = $request[$field];
            } else {
                throw new \LogicException(
                    sprintf(
                        'Field %s is not present in request to build a signature',
                        $field
                    )
                );
            }
        }

        return hash_hmac('md5', implode($sign, '^'), $this->config->getValue('md5_secret', $storeId));
    }
}
