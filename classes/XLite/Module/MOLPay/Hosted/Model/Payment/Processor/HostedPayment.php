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

namespace XLite\Module\MOLPay\Hosted\Model\Payment\Processor;

class HostedPayment extends \XLite\Model\Payment\Base\WebBased
{
    public function getSettingsWidget()
    {
        return 'modules/MOLPay/Hosted/config.twig';
    }

    /**
     * Detect transaction
     *
     * @return \XLite\Model\Payment\Transaction
     */
    public function getReturnOwnerTransaction()
    {
        return \XLite\Core\Request::getInstance()->orderid
            ? \XLite\Core\Database::getRepo('XLite\Model\Payment\Transaction')->findOneBy(
                ['public_id' => \XLite\Core\Request::getInstance()->orderid]
            )
            : null;
    }

    public function isTestMode(\XLite\Model\Payment\Method $method)
    {
        return $method->getSetting('mode') != 'live';
    }
 
    public function isConfigured(\XLite\Model\Payment\Method $method)
    {
        return parent::isConfigured($method)
            && $method->getSetting('merchantID')
            && $method->getSetting('verifykey')
            && $method->getSetting('privatekey');
    }

    protected function getFormURL()
    {
        return 'https://'.(($this->getSetting('mode') != 'live') ? 'sandbox.molpay.com' : 'www.onlinepayment.com.my').'/MOLPay/pay/'.$this->getSetting('merchantID').'/';
    }

    protected function getFormFields()
    {
        $currency = $this->transaction->getCurrency();

        $vcode = md5(number_format($this->transaction->getValue(), 2, '.', '').$this->getSetting('merchantID').$this->getTransactionId().$this->getSetting('verifykey'));
        
        $fields = [
            'amount'        => number_format(->transaction->getValue(), 2, '.', ''),
            'orderid'       => $this->getTransactionId(),
            'bill_name'     => $this->getProfile()->getBillingAddress()->getFirstname().' '.$this->getProfile()->getBillingAddress()->getLastname(),
            'bill_email'    => $this->getProfile()->getLogin(),
            'bill_mobile'   => $this->getProfile()->getBillingAddress()->getPhone(),
            'bill_desc'     => $this->getTransactionId(),
            'country'       => $this->getProfile()->getBillingAddress()->getCountry()->getCountry(),
            'vcode'         => $vcode,
            'currency'      => $currency->getCode(),
            'returnurl'     => $this->getReturnURL('orderid')
        ];

        if ($this->getSetting('logs'))
            \XLite\Logger::logCustom("MOLPay", print_r($fields, 1), 0);

        return $fields;
    }

    /**
     * Generates MOLPay comparable skey string from request and method data.
     *
     * @param  \XLite\Model\Payment\Transaction $transaction
     *
     * @return string
     */
    public function calculateSkey(\XLite\Model\Payment\Transaction $transaction)
    {
        $request = \XLite\Core\Request::getInstance();
        
        $key0 = md5( $request->tranID.$request->orderid.$request->status.$transaction->getPaymentMethod()->getSetting('merchantID').$request->amount.$request->currency );
        $key1 = md5( $request->paydate.$transaction->getPaymentMethod()->getSetting('merchantID').$key0.$request->appcode.$transaction->getPaymentMethod()->getSetting('privatekey') );
        
        return $key1;
    }

    public function processReturn(\XLite\Model\Payment\Transaction $transaction)
    {
        parent::processReturn($transaction);

        $request = \XLite\Core\Request::getInstance();
        if ($this->transaction->getPaymentMethod()->getSetting('logs'))
            \XLite\Logger::logCustom("MOLPay", $request->getPostDataWithArrayValues(), 0);

        $this->setDetail('transId', $request->tranID, 'Transaction ID');
        $this->setDetail('status', $request->status, 'Status');
        $this->setDetail('amount', $request->amount, 'Amount');
        $this->setDetail('currency', $request->currency, 'Currency');
        $this->setDetail('appcode', $request->appcode, 'App Code');
        $this->setDetail('paydate', $request->paydate, 'Paid Date');
        $this->setDetail('skey', $request->skey, 'Skey');
        $this->setDetail('nbcb', 'Return', 'End Point');

        
        $postData[]= "treq=1";
        foreach ($request->getPostDataWithArrayValues() As $k => $v) {
            $postData[]= $k."=".$v;
        }
        $postdata    = implode("&",$postData);
        $ReturnIPN_URL = "https://".(($this->transaction->getPaymentMethod()->getSetting('mode') != 'live') ? 'sandbox.molpay.com' : 'www.onlinepayment.com.my')."/MOLPay/API/chkstat/returnipn.php";
        $ch = curl_init($ReturnIPN_URL);
        curl_setopt($ch, CURLOPT_URL, $ReturnIPN_URL);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_CERTINFO, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);  // TRUE to force the use of a new connection instead of a cached one.
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);  // TRUE to force the use of a new connection instead of a cached one.
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec( $ch );
        curl_close( $ch );
        if ($transaction->getPaymentMethod()->getSetting('logs'))
            \XLite\Logger::logCustom("MOLPay", $result, 0);

        $status = null;

        if ($request->status == "00") {
            if ($this->calculateSkey($transaction) == $request->skey) {
                $status = $transaction::STATUS_SUCCESS;
            } else {
                $status = $transaction::STATUS_FAILED;
                
                $this->transaction->setNote("Skey unmatch.");
            }
        } elseif ($request->status == "22") {
            $status = $transaction::STATUS_PENDING;
            
            $this->transaction->setNote("Awaiting payment from buyer.");
        } else {
            $status = $transaction::STATUS_FAILED;
            
            if (strlen($request->error_code) > 0)
                $this->transaction->setNote($request->error_code." : ".$request->error_desc);
        }

        $this->transaction->setStatus($status);
    }
    
    /**
     * Get return type
     *
     * @return string
     */
    public function getReturnType()
    {
        return self::RETURN_TYPE_HTML_REDIRECT;
    }

    /**
     * Process callback
     *
     * @param \XLite\Model\Payment\Transaction $transaction Callback-owner transaction
     */
    public function processCallback(\XLite\Model\Payment\Transaction $transaction)
    {
        parent::processCallback($transaction);

        $request = \XLite\Core\Request::getInstance();
        
        if ($this->transaction->getPaymentMethod()->getSetting('logs'))
            \XLite\Logger::logCustom("MOLPay", print_r($notes, 1), 0);

        $this->setDetail('transId', $request->tranID, 'Transaction ID');
        $this->setDetail('status', $request->status, 'Status');
        $this->setDetail('amount', $request->amount, 'Amount');
        $this->setDetail('currency', $request->currency, 'Currency');
        $this->setDetail('appcode', $request->appcode, 'App Code');
        $this->setDetail('paydate', $request->paydate, 'Paid Date');
        $this->setDetail('skey', $request->skey, 'Skey');
        if ($request->nbcb == "1")
            $this->setDetail('nbcb', 'Callback', 'End Point');
        else if ($request->nbcb == "2")
            $this->setDetail('nbcb', 'Notify', 'End Point');

        $status = null;

        if ($request->status == "00") {
            if ($this->calculateSkey($transaction) == $request->skey) {
                $status = $transaction::STATUS_SUCCESS;
            } else {
                $status = $transaction::STATUS_FAILED;
            }
        } elseif ($request->status == "22") {
            $status = $transaction::STATUS_PENDING;
        } else {
            $status = $transaction::STATUS_FAILED;
        }
        
        if ($request->nbcb == "1") {
            header("Content-Type: text/plain;");
            echo "CBTOKEN:MPSTATOK";
        } elseif ($request->nbcb == "2") {
            $postData[]= "treq=1";
            foreach ($request->getPostDataWithArrayValues() As $k => $v) {
                $postData[]= $k."=".$v;
            }
            $postdata    = implode("&",$postData);
            $NotifyIPN_URL = "https://".(($this->transaction->getPaymentMethod()->getSetting('mode') != 'live') ? 'sandbox.molpay.com' : 'www.onlinepayment.com.my')."/MOLPay/API/chkstat/returnipn.php";
            $ch = curl_init($NotifyIPN_URL);
            curl_setopt($ch, CURLOPT_URL, $NotifyIPN_URL);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            curl_setopt($ch, CURLOPT_CERTINFO, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);  // TRUE to force the use of a new connection instead of a cached one.
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);  // TRUE to force the use of a new connection instead of a cached one.
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_FAILONERROR, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            $result = curl_exec( $ch );
            curl_close( $ch );
            if ($this->transaction->getPaymentMethod()->getSetting('logs'))
                \XLite\Logger::logCustom("MOLPay", $result, 0);
        }

        $this->transaction->setStatus($status);
    }
}
