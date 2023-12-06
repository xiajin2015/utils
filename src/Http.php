<?php

namespace Xiajin2015\Utils; // 注意命名空间与 composer.json 中的一致


/**
 * Http 请求类
 */
class Http
{

    /**
     * 发送一个POST请求
     * @param string $url 请求URL
     * @param array $params 请求参数
     * @param array $options 扩展参数
     * @return mixed|string
     */
    public static function post($url, $params = [], $options = [])
    {
        $req = self::sendRequest($url, $params, 'POST', $options);
        return $req['ret'] ? $req['msg'] : '';
    }

    /**
     * 发送一个GET请求
     * @param string $url 请求URL
     * @param array $params 请求参数
     * @param array $options 扩展参数
     * @return mixed|string
     */
    public static function get($url, $params = [], $options = [])
    {
        $req = self::sendRequest($url, $params, 'GET', $options);
        return $req['ret'] ? $req['msg'] : '';
    }

    /**
     * CURL发送Request请求,含POST和REQUEST
     * @param string $url 请求的链接
     * @param mixed $params 传递的参数
     * @param string $method 请求的方法
     * @param mixed $options CURL的参数
     * @return array
     */
    public static function sendRequest($url, $params = [], $method = 'POST', $options = [], $header = [])
    {
        $method       = strtoupper($method);
        $protocol     = substr($url, 0, 5);
        $query_string = is_array($params) ? http_build_query($params) : $params;

        $ch       = curl_init();
        $defaults = [];
        if ('GET' == $method) {
            $geturl                = $query_string ? $url . (stripos($url, "?") !== false ? "&" : "?") . $query_string : $url;
            $defaults[CURLOPT_URL] = $geturl;
        } else {
            $defaults[CURLOPT_URL] = $url;
            if ($method == 'POST') {
                $defaults[CURLOPT_POST] = 1;
            } else {
                $defaults[CURLOPT_CUSTOMREQUEST] = $method;
            }
            $defaults[CURLOPT_POSTFIELDS] = $params;
        }

        $defaults[CURLOPT_HEADER]         = false;
        $defaults[CURLOPT_USERAGENT]      = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.98 Safari/537.36";
        $defaults[CURLOPT_FOLLOWLOCATION] = true;
        $defaults[CURLOPT_RETURNTRANSFER] = true;
        $defaults[CURLOPT_CONNECTTIMEOUT] = 5;
        $defaults[CURLOPT_TIMEOUT]        = 5;

        // disable 100-continue
        $header[] = 'Expect:';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        if ('https' == $protocol) {
            $defaults[CURLOPT_SSL_VERIFYPEER] = false;
            $defaults[CURLOPT_SSL_VERIFYHOST] = false;
        }

        curl_setopt_array($ch, (array)$options + $defaults);

        $ret = curl_exec($ch);
        $err = curl_error($ch);

        if (false === $ret || !empty($err)) {
            $errno = curl_errno($ch);
            $info  = curl_getinfo($ch);
            curl_close($ch);
            return [
                'ret'   => false,
                'errno' => $errno,
                'msg'   => $err,
                'info'  => $info,
            ];
        }
        curl_close($ch);
        return [
            'ret' => true,
            'msg' => $ret,
        ];
    }

    /**
     * 异步发送一个请求
     * @param string $url 请求的链接
     * @param mixed $params 请求的参数
     * @param string $method 请求的方法
     * @return boolean TRUE
     */
    public static function sendAsyncRequest($url, $params = [], $method = 'POST')
    {
        $method = strtoupper($method);
        $method = $method == 'POST' ? 'POST' : 'GET';
        //构造传递的参数
        if (is_array($params)) {
            $post_params = [];
            foreach ($params as $k => &$v) {
                if (is_array($v)) {
                    $v = implode(',', $v);
                }
                $post_params[] = $k . '=' . urlencode($v);
            }
            $post_string = implode('&', $post_params);
        } else {
            $post_string = $params;
        }
        $parts = parse_url($url);
        //构造查询的参数
        if ($method == 'GET' && $post_string) {
            $parts['query'] = isset($parts['query']) ? $parts['query'] . '&' . $post_string : $post_string;
            $post_string    = '';
        }
        $parts['query'] = isset($parts['query']) && $parts['query'] ? '?' . $parts['query'] : '';
        //发送socket请求,获得连接句柄
        $fp = fsockopen($parts['host'], isset($parts['port']) ? $parts['port'] : 80, $errno, $errstr, 3);
        if (!$fp) {
            return false;
        }
        //设置超时时间
        stream_set_timeout($fp, 3);
        $out = "{$method} {$parts['path']}{$parts['query']} HTTP/1.1\r\n";
        $out .= "Host: {$parts['host']}\r\n";
        $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out .= "Content-Length: " . strlen($post_string) . "\r\n";
        $out .= "Connection: Close\r\n\r\n";
        if ($post_string !== '') {
            $out .= $post_string;
        }
        fwrite($fp, $out);
        //不用关心服务器返回结果
        //echo fread($fp, 1024);
        fclose($fp);
        return true;
    }

    /**
     * 发送文件到客户端
     * @param string $file
     * @param bool $delaftersend
     * @param bool $exitaftersend
     */
    public static function sendToBrowser($file, $delaftersend = true, $exitaftersend = true)
    {
        if (file_exists($file) && is_readable($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment;filename = ' . basename($file));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check = 0, pre-check = 0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            ob_clean();
            flush();
            readfile($file);
            if ($delaftersend) {
                unlink($file);
            }
            if ($exitaftersend) {
                exit;
            }
        }
    }

    public static function sendRequestJson($url, $params = [], $method = 'POST', $options = [])
    {
        $data_string = json_encode($params, JSON_UNESCAPED_UNICODE);
        $curl_con    = curl_init();
        curl_setopt($curl_con, CURLOPT_URL, $url);
        curl_setopt($curl_con, CURLOPT_HEADER, false);
        curl_setopt($curl_con, CURLOPT_POST, strtolower($method) == 'post' ? true : false);
        curl_setopt($curl_con, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_con, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt_array($curl_con, $options);
        curl_setopt($curl_con, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        curl_setopt($curl_con, CURLOPT_POSTFIELDS, $data_string);
        $res    = curl_exec($curl_con);
        $status = curl_getinfo($curl_con);
        curl_close($curl_con);
        $result = isset($status['http_code']) && $status['http_code'] == 200 ? json_decode($res, true) : [];
        return $result;
    }

    public static function sendRequestV1($postUrl, $postData)
    {

        $postData = http_build_query($postData);

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $postUrl);

        curl_setopt($curl, CURLOPT_USERAGENT, 'Opera/9.80 (Windows NT 6.2; Win64; x64) Presto/2.12.388 Version/12.15');

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_TIMEOUT, 3);

        curl_setopt($curl, CURLOPT_POST, true);

        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);


        $ret = curl_exec($curl);
        curl_close($curl);
        $ret = json_decode($ret, true);
        return $ret;
    }


    public static function sendRequestV2($url, $params = [], $method = 'POST', $options = [], $header = [])
    {
        $method       = strtoupper($method);
        $protocol     = substr($url, 0, 5);
        $query_string = is_array($params) ? http_build_query($params) : $params;

        $ch       = curl_init();
        $defaults = [];
        if ('GET' == $method) {
            $url                   = $query_string ? $url . (stripos($url, "?") !== false ? "&" : "?") . $query_string : $url;
            $defaults[CURLOPT_URL] = $url;
        } else {
            $defaults[CURLOPT_URL] = $url;
            if ($method == 'POST') {
                $defaults[CURLOPT_POST] = 1;
            } else {
                $defaults[CURLOPT_CUSTOMREQUEST] = $method;
            }
            $defaults[CURLOPT_POSTFIELDS] = $params;
        }

        $defaults[CURLOPT_HEADER]         = false;
        $defaults[CURLOPT_USERAGENT]      = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.98 Safari/537.36";
        $defaults[CURLOPT_FOLLOWLOCATION] = true;
        $defaults[CURLOPT_RETURNTRANSFER] = true;
        $defaults[CURLOPT_CONNECTTIMEOUT] = 3;
        $defaults[CURLOPT_TIMEOUT]        = 3;

        // disable 100-continue
        $header[] = 'Expect:';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        if ('https' == $protocol) {
            $defaults[CURLOPT_SSL_VERIFYPEER] = false;
            $defaults[CURLOPT_SSL_VERIFYHOST] = false;
        }

        curl_setopt_array($ch, (array)$options + $defaults);

        $ret = curl_exec($ch);
        $err = curl_error($ch);
        $status = curl_getinfo($ch);
        curl_close($ch);
        $result = isset($status['http_code']) && $status['http_code'] == 200 ? json_decode($ret, true) : [];
        return $result;
    }


    /**
     * 请求微信
     * @param $url
     * @param $param
     * @param $authorization
     * @return bool|string
     */
    public static function wxPost($url, $param, $authorization)
    {
        $curl    = curl_init();
        $headers = [
            'Authorization:' . $authorization,
            'Accept:application/json',
            'Content-Type:application/json;charset=utf-8',
            'User-Agent:Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36',
        ];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $param);
        curl_setopt($curl, CURLOPT_POST, true);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }


    /**
     * 时刻音视频专用
     * @param $url
     * @param $params
     * @param $method
     * @param $options
     * @param $header
     * @return array
     */
    public static function ShiKeSendRequest($url, $params = [], $method = 'POST', $options = [], $header = [])
    {
        $method       = strtoupper($method);
        $protocol     = substr($url, 0, 5);
        $query_string = is_array($params) ? http_build_query($params) : $params;

        $ch                    = curl_init();
        $defaults              = [];
        $geturl                = $query_string ? $url . (stripos($url, "?") !== false ? "&" : "?") . $query_string : $url;
        $defaults[CURLOPT_URL] = $geturl;
        if ('GET' == $method) {

        } elseif ('PUT' == $method) {
            $defaults[CURLOPT_PUT] = 1;
        } else {
            if ($method == 'POST') {
                $defaults[CURLOPT_POST] = 1;
            } else {
                $defaults[CURLOPT_CUSTOMREQUEST] = $method;
            }
            $defaults[CURLOPT_POSTFIELDS] = $params;
        }

        $defaults[CURLOPT_HEADER]         = false;
        $defaults[CURLOPT_USERAGENT]      = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.98 Safari/537.36";
        $defaults[CURLOPT_FOLLOWLOCATION] = true;
        $defaults[CURLOPT_RETURNTRANSFER] = true;
        $defaults[CURLOPT_CONNECTTIMEOUT] = 5;
        $defaults[CURLOPT_TIMEOUT]        = 5;

        // disable 100-continue
        $header[] = 'Expect:';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        if ('https' == $protocol) {
            $defaults[CURLOPT_SSL_VERIFYPEER] = false;
            $defaults[CURLOPT_SSL_VERIFYHOST] = false;
        }

        curl_setopt_array($ch, (array)$options + $defaults);

        $ret = curl_exec($ch);
        $err = curl_error($ch);

        if (false === $ret || !empty($err)) {
            $errno = curl_errno($ch);
            $info  = curl_getinfo($ch);
            curl_close($ch);
            return [
                'ret'   => false,
                'errno' => $errno,
                'msg'   => $err,
                'info'  => $info,
            ];
        }
        curl_close($ch);
        return [
            'ret' => true,
            'msg' => $ret,
        ];
    }
}
