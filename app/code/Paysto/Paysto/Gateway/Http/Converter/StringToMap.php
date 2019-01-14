<?php
/**
 * Copyright © Paysto, Inc. All rights reserved.
 *
 */
namespace Paysto\Paysto\Gateway\Http\Converter;

use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Payment\Gateway\Http\ConverterInterface;

class StringToMap implements ConverterInterface
{
    /**
     * Converts gateway response to ENV structure
     *
     * @param mixed $response
     * @return array
     * @throws ConverterException
     */
    public function convert($response)
    {
        if (!is_string($response)) {
            throw new ConverterException(__('Wrong response type'));
        }

        return explode(',', $response);
    }
}
