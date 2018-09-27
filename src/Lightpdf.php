<?php

namespace ImDong;

use GuzzleHttp;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use OSS\OssClient;
use \OSS\Core\OssException;


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
     * 任务 ID
     *
     * @var string
     */
    private $task_id;
    
    /**
     * 工作 id
     *
     * @var string
     */
    private $file_id;
    
    
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
     * 获取错误
     * @return array
     */
    public function getError(): array
    {
        return ['code' => $this->err_code, 'message' => $this->err_message];
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
    
    /**
     * 读取 API Token
     * @return bool
     */
    public function getPdfSesstion(): bool
    {
        // 创建请求正文
        $url  = $this->getAPIURL('/sessions');
        $data = [
            'identity_token' => ''
        ];
        
        // 发起请求
        try {
            $response = $this->httpClient->post($url, $data);
        } catch (RequestException $e) {
            $this->err_code    = 401;
            $this->err_message = $e->getMessage();
            return false;
        }
        
        // 解析请求结果
        $body = json_decode($response->getBody());
        if ($body->status == 1) {
            $this->api_token = $body->data->user->api_token;
            return true;
        }
        
        $this->err_code    = 400;
        $this->err_message = "返回数据错误:" . $response->getBody();
        
        return false;
    }
    
    /**
     * 获取 Api Token
     * @return string
     */
    public function getSession(): string
    {
        if (!$this->api_token && !$this->getPdfSesstion()) {
            return '';
        }
        return $this->api_token;
    }
    
    /**
     * 发送 Post 请求
     * @param string $uri 接口相对路径
     * @param array $data
     * @param string $method
     * @return array
     * @throws GuzzleHttp\Exception\GuzzleException
     */
    public function sendPost(string $uri, array $data, string $method = 'POST'): array
    {
        // 创建请求正文
        $url = $this->getAPIURL($uri);
        
        // 获取 Api Token
        if (!$this->getSession()) {
            return [];
        }
        
        // 附加头信息
        $options = [
            'body'    => json_encode($data),
            'headers' => [
                'referer'       => 'https://lightpdf.com/zh/ocr',
                'content-type'  => 'application/json',
                'authorization' => 'Bearer ' . $this->api_token
            ]
            // 'auth' => ['Bearer', $this->api_token]
        ];
        
        // 发起请求
        try {
            $response = $this->httpClient->request($method, $url, $options);
        } catch (RequestException $e) {
            $this->err_code    = 401;
            $this->err_message = $e->getMessage();
            return [];
        }
        
        // 解析请求结果
        $result = json_decode($response->getBody(), true);
        if ($result && $result['status'] == 1) {
            return $result['data'];
        }
        
        $this->err_code    = 401;
        $this->err_message = "未知返回结果";
        
        return [];
    }
    
    /**
     * 创建新任务
     *
     * @param string $service_type
     * @return string
     * @throws GuzzleHttp\Exception\GuzzleException
     */
    public function createTask(string $service_type): string
    {
        $data = [
            'service_type' => $service_type
        ];
        
        if ($result = $this->sendPost('/tasks', $data)) {
            return $this->task_id = $result['task_id'];
        }
        
        return '';
    }
    
    /**
     * 上传文件到 阿里云 Oss
     *
     * @param array $info
     * @param string $file
     * @return array
     */
    public function uploadToOss(array $info, string $file): array
    {
        // 创建对象
        try {
            $ossClient = new OssClient(
                $info['access_id'],
                $info['access_secret'],
                $info['endpoint'],
                false,
                $info['security_token']
            );
        } catch (OssException $e) {
            $this->err_code    = 403;
            $this->err_message = $e->getMessage();
            return [];
        }
        
        // 读取文件内容
        if (!file_exists($file)) {
            $this->err_code    = 405;
            $this->err_message = '文件不存在';
            return [];
        }
        $content = file_get_contents($file);
        $filename = basename($file);
        $object = $info['objects'][$filename];
        
        
        // 上传文件
        try {
            $result = $ossClient->putObject($info['bucket'], $object, $content);
        } catch (OssException $e) {
            $this->err_code    = 404;
            $this->err_message = $e->getMessage();
            return [];
        }
        
        // 构建回调信息
        $replace_arr = [
            'bucket' => $info['bucket'],
            'object' => $object,
            'etag'   => $result['etag'],
            'size'   => $result['info']['size_upload'],
            'mimeType' => $result['oss-requestheaders']['Content-Type'],
            'imageInfo.height' => '100',
            'imageInfo.width' => '100',
            'imageInfo.format' => 'png'
        ];
        $replaceData = ['search' => [], 'replace' => []];
        foreach ($replace_arr as $_key => $value) {
            $replaceData['search'][] = sprintf('${%s}', $_key);
            $replaceData['replace'][] = $value;
        }
        $callbackBody = str_ireplace($replaceData['search'], $replaceData['replace'], $info['callback_url']['callbackBody']);
        
        // 发起请求
        $options = [
            'body'    => $callbackBody,
            'headers' => [
                'content-type'  => $info['callback_url']['callbackBodyType']
            ]
        ];
        
        try {
            $response = $this->httpClient->post($info['callback_url']['callbackUrl'], $options);
        } catch (RequestException $e) {
            $this->err_code    = 401;
            $this->err_message = $e->getMessage();
            return [];
        }
        
        // 解析请求结果
        $result = json_decode($response->getBody(), true);
        if ($result && $result['status'] == 1) {
            return $result['data'];
        }
        
        return $result;
    }
    
    /**
     * 获取文件授权
     *
     * @param string $file
     * @return array
     * @throws GuzzleHttp\Exception\GuzzleException
     */
    public function getFileAuthorization(string $file): array
    {
        $filename = basename($file);
        $data = [
            'files' => [$filename]
        ];
        
        if ($result = $this->sendPost('/authentications', $data)) {
            if ($result = $this->uploadToOss($result, $file)){
                $this->file_id = $result['id'];
                return $result;
            }
        }
        
        return [];
    }
    
    /**
     * 开始任务
     * @param string $language
     * @return bool
     * @throws GuzzleHttp\Exception\GuzzleException
     */
    public function startTask(string $language = 'English'): bool
    {
        $uri = '/tasks/' . $this->task_id;
        $data = [
            'files' => [
                [
                    'file_id' => $this->file_id,
                    'password' => ''
                ]
            ],
            'args' => [
                'language' => $language,
                'output-format' => 'txt'
            ]
        ];
        
        $result = $this->sendPost($uri, $data, 'PUT');
        
        return !is_null($result);
            
    }
    
    
    
    
}
