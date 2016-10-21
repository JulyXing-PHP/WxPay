<?php
/**
 * 声明微信支付 JSAPI 功能组件。
 *
 * @author    JulyXing <julyxing@163.com>
 * @copyright © 2016 JulyXing
 * @license   GPL-3.0+
 */

namespace WxPay\Utility;

/**
 * 微信支付 JSAPI 功能组件。
 */
final class WxPayJSApi
{
    /**
     * 获取jsapi支付的参数
     *
     * @param array $UnifiedOrderResult 统一支付接口返回的数据
     * @throws WxPayException
     *
     * @return json数据，可直接填入js函数作为参数
     */
    public function getJSApiParameters($UnifiedOrderResult, $key)
    {
        if (!array_key_exists("appid", $UnifiedOrderResult)
            || !array_key_exists("prepay_id", $UnifiedOrderResult)
            || $UnifiedOrderResult['prepay_id'] == "") {
            throw new WxPayException("参数错误");
        }

        $timemap = time();

        // 生成随机数
        $o_ranchar = new Core\Utility\RandChar();
        $s_ranchar = $o_ranchar->randChar('mix', 32);

        // 生成签名
        $sign_config = array(
            "appId"     => $UnifiedOrderResult['appid'],
            "timeStamp" => "$timemap",
            "nonceStr"  => $s_ranchar,
            "package"   => "prepay_id=" . $UnifiedOrderResult['prepay_id'],
            "signType"  => "MD5",
        );
        $o_sign = new Core\Utility\WxPay\WxPaySign($sign_config);
        $s_sign = $o_sign->makeSign($key);

        $params = array(
            "appId"     => $UnifiedOrderResult['appid'],
            "timeStamp" => "$timemap",
            "nonceStr"  => $s_ranchar,
            "package"   => "prepay_id=" . $UnifiedOrderResult['prepay_id'],
            "signType"  => "MD5",
            "paySign"   => $s_sign
        );

        return $params;

        // 文档格式返回 JSON 格式，配合接口调整。
        // $parameters = json_encode($params);
        // return $parameters;
    }
}
