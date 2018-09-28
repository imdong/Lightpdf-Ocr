<?php

namespace ImDong;

/**
 * Ocr识别类
 */
class LightpdfOcr
{
    /**
     * lightpdf 核心
     *
     * @var Lightpdf
     */
    private $lightpdf;
    
    /**
     * @var string 任务类型
     */
    private $taskType = 'ocr';
    
    /**
     * @var int 错误代码
     */
    private $err_code;
    
    /**
     * @var string 错误原因
     */
    private $err_message;
    
    /**
     * @var string 待识别文件名
     */
    private $filename;
    
    /**
     * @var string 识别的语言
     */
    private $language = 'English';

    /**
     * @var string 授权token
     */
    public static $api_token;

    private function __construct()
    {
        $this->lightpdf = new Lightpdf();
        $this->lightpdf->setStatus([
            'api_token' => static::$api_token
        ]);
    }
    
    /**
     * 获取错误
     * @return array
     */
    public function getError(): array
    {
        return $this->err_code
            ? ['code' => $this->err_code, 'message' => $this->err_message]
            : $this->lightpdf->getError();
    }
    
    /**
     * 创建任务
     *
     * @param string $filename
     * @param string $language
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function create(string $filename, string $language = 'English'):  bool
    {
        //  保存文件名
        $this->filename = $filename;
        $this->language = $language;
        
        // 创建任务
        if (!$this->lightpdf->createTask($this->taskType)) {
            $this->err_code = 301;
            $this->err_message = '创建任务失败';
            return false;
        }
        
        // 上传文件获取授权
        if (!$this->lightpdf->getFileAuthorization($filename)) {
            return false;
        }
        
        // 开始识别
        if(!$this->lightpdf->modifyTask($this->language)) {
            return false;
        }
        
        if (!$this->lightpdf->startTask()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 传入图片文件进行识别(同步等待返回)
     *
     * @param string $filename 要识别图片的储存文件名
     * @param string $language 待识别的语言
     * @return array
     */
    public static function inFile(string $filename, string $language = 'English'): array
    {
        /**
         * 实例化自己创建任务
         */
        $ocr = new static();
        if(!$ocr->create($filename, $language)) {
            return $ocr->getError();
        }

        // 任务等待
        $body = '';
        if ($info = $ocr->lightpdf->infoTask()) {
            $body = $ocr->lightpdf->getTaskBody($info);
            return ['code' => 0, 'body' => $body];
        } else {
            return $ocr->getError();
        }
    }
    
    /**
     * 传入图片文件进行识别(异步调用返回)
     *
     * @param string $filename 要识别图片的储存文件名
     * @return array
     */
    public static function inFileAsync(string $filename): array
    {
        /**
         * 实例化自己创建任务
         */
        $ocr = new static();
        if(!$ocr->create($filename)) {
            return [];
        }
        
        // 获取任务动态并返回
        return $ocr->lightpdf->getStatus();
    }
    
    public static function inFileAsyncCheck(array $task_status): array
    {
        /**
         * 实例化对象
         */
        $ocr = new static();
    
        /**
         * 恢复现场
         */
        $ocr->lightpdf->setStatus($task_status);
        
        // 检查是否完成
        $result = [];
        $file_url = $ocr->lightpdf->getTaskInfo($result);

        if($result['status'] == 2) {
            // 取到结果就行
            if($body = $ocr->lightpdf->getTaskBody($file_url)) {
                return [
                    'status' => 1,
                    'code' => $body
                ];
            }
            
            // 取不到判断错误
            $errinfo = $ocr->getError();
            if($errinfo['code']) {
                $errinfo['status'] = -1;
                return $errinfo;
            }
            
            // 没出错说明没问题
            return [
                'status' => 1,
                'code' => $body
            ];
        }
        
        return [
            'status' => 0
        ];
    }
    
    
}
