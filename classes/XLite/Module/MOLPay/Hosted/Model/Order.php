<?php
/*
 * Copyright (C) 2018 MOLPay Sdn. Bhd.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author      MOLPay team
 * @copyright   2018 MOLPay Sdn. Bhd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace XLite\Module\MOLPay\Hosted\Model;

/**
 * Order model
 */
class Order extends \XLite\Module\XC\CanadaPost\Model\Order implements \XLite\Base\IDecorator
{
    /**
     * Checks if order payment method is MOLPay Hosted Payment
     *
     * @param \XLite\Model\Payment\Method $method
     *
     * @return bool
     */
    public function isHostedMethod($method)
    {
        return null !== $method
            && 'MOLPayHostedPayment' === $method->getServiceName();
    }
}