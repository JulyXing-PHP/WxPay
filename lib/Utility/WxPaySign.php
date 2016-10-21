<?php
/**
 * 声明微信支付签名组件。
 *
 * @author    JulyXing <julyxing@163.com>
 * @copyright © 2016 JulyXing
 * @license   GPL-3.0+
 */

namespace WxPay\Utility;

/**
 * 微信支付签名组件。
 */
final class WxPaySign
{
    /**
     * 支付签名参数。
     *
     * @internal
     *
     * @var $values
     */
    protected $values;

    /**
     * 构造函数
     *
     * @param array   微信支付参数
     */
    public function __construct($values)
    {
        return $this->values = $values;
    }

    /**
     * 获取签名，详见签名生成算法的值
     *
     * @return 值
     */
    public function getSign()
    {
        return $this->values['sign'];
    }

    /**
     * 判断签名，详见签名生成算法是否存在
     *
     * @return true 或 false
     */
    public function isSignSet()
    {
        return array_key_exists('sign', $this->values);
    }

    /**
     * 设置签名，详见签名生成算法
     *
     * @param string $value
     */
    public function setSign()
    {
        $sign = $this->makeSign();
        $this->values['sign'] = $sign;

        return $sign;
    }

    /**
     * 生成签名
     *
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function makeSign($key)
    {
        //签名步骤一：按字典序排序参数
        ksort($this->values);
        $string = $this->toUrlParams();
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);

        return $result;
    }

    /**
     * 格式化参数格式化成url参数
     */
    public function toUrlParams()
    {
        $buff = "";
        foreach ($this->values as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");

        return $buff;
    }
}
