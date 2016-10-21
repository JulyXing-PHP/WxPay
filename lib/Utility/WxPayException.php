<?php
/**
 * 声明微信支付异常抽象类。
 *
 * @author     JulyXing <julyxing@163.com>
 * @copyright  © 2016 JulyXing
 * @license    GPL-3.0+
 */

namespace WxPay\Utility;

use Exception;

/**
 * 微信支付异常抽象类。
 */
final class WxPayException extends Exception
{
    public function errorMessage()
    {
        return $this->getMessage();
    }
}
