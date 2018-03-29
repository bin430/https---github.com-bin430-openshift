<?php
/**
 * wechat php test
 */
require_once('lib/log4php/Logger.php');
Logger::configure('lib/log4.xml');


//define your token
define("TOKEN", "weixin");
$wechatObj = new wechatCallbackapiTest();
$wechatObj->valid();//接口验证

class wechatCallbackapiTest
{
    private $pay;

    /**
     * wechatCallbackapiTest constructor.
     */
    public function __construct()
    {
        $this->pay = Logger::getRootLogger();
    }

    public function valid()
    {
        $this->pay->debug("get_http_raw:" . $this->get_http_raw());

        $echoStr = $_GET["echostr"];
        //$this->pay->debug($echoStr);
        //valid signature , option
        if (strlen($echoStr) > 0) {
            $this->pay->debug("checkSignature");
            //接口验证
            $this->checkSignature();
            echo $echoStr;
        } else {
            #http://218.66.48.231:30159/weixin/debug.php
            //调用回复消息方法
            $postStr = file_get_contents('php://input', 'r');
            #$this->pay->debug("postStr:" . $postStr);
            #echo $this->send_post("http://127.0.0.1:8080/weixin.php",$postStr);
            $returnXML = $this->post3("218.66.48.231","/weixin/weixin.php",$postStr);
            $res = preg_match("#(?P<xmlInfo><xml>[\s\S]*?</xml>)#",$returnXML, $m);
            $this->pay->debug("$m:" . $m["xmlInfo"]);
            echo $m["xmlInfo"];
        }
    }

    public function post3($host,$path,$query,$others=''){
        $post="POST $path HTTP/1.1\r\n";
        $post.="Host: $host\r\n";
        $post.="ACCEPT: */*\r\n";
        $post.="User-Agent: Mozilla 4.0\r\nContent-length: ";
        $post.=strlen($query)."\r\nConnection: close\r\n\r\n$query";
        $h=fsockopen($host,30159);
        fwrite($h,$post);
        for($a=0,$r='';!$a;){
            $b=fread($h,8192);
            $r.=$b;
            $a=(($b=='')?1:0);
        }
        fclose($h);
        $this->pay->debug("result:" . $r);
        return $r;

    }

    /**
     * 发送post请求
     * @param string $url 请求地址
     * @param array $post_data post键值对数据
     * @return string
     */
    public function send_post($url, $post_data)
    {
        $this->pay->debug("postStr:" . $post_data);
        $postdata = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'content' => $postdata
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $this->pay->debug("result:" . $result);
        return $result;
    }


    /**
     * 获取HTTP请求原文
     * @return string
     */
    public function get_http_raw()
    {
        $raw = '';
        // (1) 请求行
        $raw .= $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'] . ' ' . $_SERVER['SERVER_PROTOCOL'] . "\r\n";
        // (2) 请求Headers
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $key = substr($key, 5);
                $key = str_replace('_', '-', $key);

                $raw .= $key . ': ' . $value . "\r\n";
            }
        }
        // (3) 空行
        $raw .= "\r\n";
        // (4) 请求Body
        $raw .= file_get_contents('php://input');

        return $raw;
    }

    private function checkSignature()
    {
        // you must define TOKEN by yourself
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }

        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }
}

?>
