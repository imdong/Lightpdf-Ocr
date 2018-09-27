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
     * @var ImDong\Lightpdf
     */
    private $lightpdf;

    private function __construct()
    {
        $this->lightpdf = new Lightpdf();
    }
    
    /**
     * 传入图片文件进行识别
     *
     * @param string $filename 要识别图片的储存文件名
     * @return array|false|object
     */
    public static function inFile(string $filename): array
    {
        /**
         * 实例化自己
         */
        $ocr = new static();

        // 创建任务
        $ocr->lightpdf->createTask('ocr');
        
        // 上传文件获取授权
        $ocr->lightpdf->getFileAuthorization($filename);
        
        // 开始识别
        $ocr->lightpdf->startTask();
        
        var_dump($rs);

        if(!$rs) {
            var_dump($ocr->lightpdf->getError(), $ocr);
        }
        
        return $rs;
    }
}
