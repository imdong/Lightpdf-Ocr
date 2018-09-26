<?php
namespace ImDong;

use GuzzleHttp;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;

/**
 * Lightpdf 基础功能类
 */
class Lightpdf
{

    /**
     * 接口基准url
     *
     * @var string
     */
    private $api_base_url = 'https://api.lightpdf.com/api';

    /**
     * Http请求对象
     *
     * @var GuzzleHttp\Client
     */
    private $httpClient;

    /**
     * 错误状态码
     *
     * @var int
     */
    private $err_code = 0;

    /**
     * 错误原因消息
     *
     * @var string
     */
    private $err_message = '';

    /**
     * API Token
     *
     * @var string
     */
    private $api_token;

    /**
     * 构造函数
     */
    public function __construct()
    {
        // 创建Http对象
        $this->httpClient = new HttpClient([
            'base_uri' => $this->api_base_url
        ]);
    }

    /**
     * 获取请求地址
     *
     * @param string $uri 追加后缀地址
     * @return string 完整的url地址
     */
    public function getAPIURL(string $uri): string
    {
        return sprintf('%s%s', $this->api_base_url, $uri);
    }

    public function getSession()
    {

    }

    public function getPdfSesstion()
    {
        // 创建请求正文
        $url = $this->getAPIURL('/sessions');
        $data = [
            'identity_token' => ''
        ];

        // 发起请求
        try {
            $response = $this->httpClient->post($url, $data);
        } catch (RequestException $e) {
            $this->err_code = 401;
            $this->err_message = "请求时错误";

            var_dump("Err", $e->getMessage());
            exit;
            if ($e->hasResponse()) {
                echo $e->getResponse();
            }

            return false;
        }

        // 解析请求结果
        $body = json_decode($response->getBody());
        if ($body->status == 1) {
            $this->api_token = $body->data->user->api_token;
            return true;
        }
        return false;
    }

    /**
     * 创建新任务
     *
     * @param string $service_type
     * @return void
     */
    public function createTask(string $service_type)
    {

    }
}
