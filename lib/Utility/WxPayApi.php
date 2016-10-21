<?php
/**
 * 声明微信支付组件。
 *
 * @author    JulyXing <julyxing@163.com>
 * @copyright © 2016 JulyXing
 * @license   GPL-3.0+
 */

namespace WxPay\Utility;

/**
 * 微信支付组件。
 */
final class WxPayApi
{
    /**
     * 统一下单，WxPayUnifiedOrder中 out_trade_no、body、total_fee、trade_type必填
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     *
     * @param  WxPayUnifiedOrder $inputObj
     * @param  int $timeOut
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    public static function unifiedOrder($wxpay_config = array(), $ssl_config = array(), $proxy_config = array())
    {
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";

        //检测必填参数
        if (!array_key_exists('out_trade_no', $wxpay_config)) {
            throw new WxPayException("缺少统一支付接口必填参数 out_trade_no！");
        }
        if (!array_key_exists('body', $wxpay_config)) {
            throw new WxPayException("缺少统一支付接口必填参数 body！");
        }
        if (!array_key_exists('total_fee', $wxpay_config)) {
            throw new WxPayException("缺少统一支付接口必填参数 total_fee！");
        }
        if (!array_key_exists('trade_type', $wxpay_config)) {
            throw new WxPayException("缺少统一支付接口必填参数 trade_type！");
        }

        //关联参数
        if ('JSAPI' == $wxpay_config['trade_type']) {
            if (!array_key_exists('openid', $wxpay_config)) {
                throw new WxPayException("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！");
            }
        }
        if ('NATIVE' == $wxpay_config['trade_type']) {
            if (!array_key_exists('product_id', $wxpay_config)) {
                throw new WxPayException("统一支付接口中，缺少必填参数product_id！trade_type为JSAPI时，product_id为必填参数！");
            }
        }
        if (!array_key_exists('notify_url', $wxpay_config)) {
            throw new WxPayException("缺少统一支付接口必填参数！notify_url");
        }

        $xml = WxPayCore::toXml($wxpay_config);

        $startTimeStamp = WxPayCore::getMillisecond();//请求开始时间
        $response = WxPayCore::postXmlCurl($xml, $url, $proxy_config['curl_proxy_host'], $proxy_config['curl_proxy_port'], $ssl_config['sslcert_path'], $ssl_config['sslkey_path'], true);

        // 响应结果
        $responseArr = WxPayCore::fromXml($response);

        return $responseArr;
    }

    /**
     * 查询订单，WxPayOrderQuery中out_trade_no、transaction_id至少填一个
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     *
     * @param WxPayOrderQuery $inputObj
     * @param int $timeOut
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    public static function orderQuery($inputObj, $key, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/pay/orderquery";
        //检测必填参数
        if (!array_key_exists('out_trade_no', $inputObj) && !array_key_exists('transaction_id', $inputObj)) {
            throw new WxPayException("订单查询接口中，out_trade_no、transaction_id至少填一个！");
        }
        // 生成随机数
        $o_ranchar = new Core\Utility\RandChar();
        $s_ranchar = $o_ranchar->randChar('mix', 32);

        // 生成签名
        $sign_config = array(
            "appid"           => $inputObj['appid'],
            "mch_id"          => $inputObj['mch_id'],
            "out_trade_no"    => $inputObj['out_trade_no'],
            "transaction_id"  => $inputObj['transaction_id'],
            "nonce_str"       => $s_ranchar
        );
        $o_sign = new Core\Utility\WxPay\WxPaySign($sign_config);
        $s_sign = $o_sign->makeSign($key);

        $params = array(
            "appid"           => $inputObj['appid'],
            "mch_id"          => $inputObj['mch_id'],
            "out_trade_no"    => $inputObj['out_trade_no'],
            "transaction_id"  => $inputObj['transaction_id'],
            "nonce_str"       => $s_ranchar,
            "sign"            => $s_sign
        );

        $xml = WxPayCore::toXml($params);
        $startTimeStamp = WxPayCore::getMillisecond();//请求开始时间
        $response = WxPayCore::postXmlCurl($xml, $url, false);
        $responseArr = WxPayCore::fromXml($response);
        // $response = self::postXmlCurl($xml, $url, false, $timeOut);
        // $result = WxPayResults::Init($response);
        // self::reportCostTime($url, $startTimeStamp, $result);//上报请求花费时间

        return $responseArr;
    }
}
