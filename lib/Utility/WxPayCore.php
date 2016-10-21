<?php
/**
 * 声明微信支付公用函数组件。
 *
 * @author    JulyXing <julyxing@163.com>
 * @copyright © 2016 JulyXing
 * @license   GPL-3.0+
 */

namespace WxPay\Utility;

/**
 * 微信支付公用函数组件。
 */
final class WxPayCore
{
    protected $values;

    /**
     * 输出 xml 字符。
     *
     * @throws WxPayException
     **/
    public function toXml($a_params)
    {
        if (!is_array($a_params) || count($a_params) <= 0) {
            throw new WxPayException("数组数据异常！");
        }
        $xml = "<xml>";
        foreach ($a_params as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";

        return $xml;
    }

    /**
     * 将 xml 转为 array。
     *
     * @param  string $xml
     * @throws WxPayException
     */
    public function fromXml($xml)
    {
        if (!$xml) {
            throw new WxPayException("xml数据异常！");
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

    /**
     * 格式化参数格式化成 url 参数
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

    /**
     * 获取当前服务器的 IP
     *
     * @return Ambigous <string, unknown>
     */
    public function getClientIP()
    {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $cip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv("REMOTE_ADDR")) {
            $cip = getenv("REMOTE_ADDR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
            $cip = getenv("HTTP_CLIENT_IP");
        } else {
            $cip = "127.0.0.1";
        }

        return $cip;
    }

    /**
     * 产生随机字符串，不长于32位
     *
     * @param  int $length
     * @return 产生的随机字符串
     */
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        return $str;
    }

    /**
     * 获取毫秒级别的时间戳。
     *
     * @return time   时间戳
     */
    public static function getMillisecond()
    {
        //获取毫秒的时间戳
        $time = explode(" ", microtime());
        $time = $time[1] . ($time[0] * 1000);
        $time2 = explode(".", $time);
        $time = $time2[0];

        return $time;
    }

    /**
     * 以 post 方式提交xml到对应的接口url
     *
     * @param string $xml  需要post的xml数据
     * @param string $url  url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second   url执行超时时间，默认30s
     * @throws WxPayException
     */
    public static function postXmlCurl($xml, $url, $curl_proxy_host = '0.0.0.0', $curl_proxy_port = '0', $cert_path = '', $key_path = '', $useCert = false, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        //如果有配置代理这里就设置代理
        if ($curl_proxy_host != "0.0.0.0" && $curl_proxy_port != 0) {
            curl_setopt($ch, CURLOPT_PROXY, $curl_proxy_host);
            curl_setopt($ch, CURLOPT_PROXYPORT, $curl_proxy_port);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, false);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($useCert == true) {
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, $cert_path);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, $key_path);
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);

            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            throw new WxPayException("curl出错，错误码:$error");
        }
    }

    /**
     * 上报数据， 上报的时候将屏蔽所有异常流程
     *
     * @param string $usrl
     * @param int $startTimeStamp
     * @param array $data
     */
    private static function reportCostTime($url, $startTimeStamp, $data, $levenl)
    {
        //如果不需要上报数据
        if ($levenl == 0) {
            return;
        }
        //如果仅失败上报
        if ($levenl == 1 && array_key_exists("return_code", $data) && $data["return_code"] == "SUCCESS" &&
             array_key_exists("result_code", $data) && $data["result_code"] == "SUCCESS") {
            return;
        }

        //上报逻辑
        $endTimeStamp = self::getMillisecond();
        $objInput = new WxPayReport();
        $objInput->SetInterface_url($url);
        $objInput->SetExecute_time_($endTimeStamp - $startTimeStamp);
        //返回状态码
        if (array_key_exists("return_code", $data)) {
            $objInput->SetReturn_code($data["return_code"]);
        }
        //返回信息
        if (array_key_exists("return_msg", $data)) {
            $objInput->SetReturn_msg($data["return_msg"]);
        }
        //业务结果
        if (array_key_exists("result_code", $data)) {
            $objInput->SetResult_code($data["result_code"]);
        }
        //错误代码
        if (array_key_exists("err_code", $data)) {
            $objInput->SetErr_code($data["err_code"]);
        }
        //错误代码描述
        if (array_key_exists("err_code_des", $data)) {
            $objInput->SetErr_code_des($data["err_code_des"]);
        }
        //商户订单号
        if (array_key_exists("out_trade_no", $data)) {
            $objInput->SetOut_trade_no($data["out_trade_no"]);
        }
        //设备号
        if (array_key_exists("device_info", $data)) {
            $objInput->SetDevice_info($data["device_info"]);
        }

        try {
            self::report($objInput);
        } catch (WxPayException $e) {
            //不做任何处理
        }
    }
}
