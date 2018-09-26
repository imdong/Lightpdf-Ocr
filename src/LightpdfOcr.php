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
     * @return void
     */
    public static function inFile(string $filename)
    {
        /**
         * 实例化自己
         */
        $ocr = new static();

        $ocr->lightpdf->getPdfSesstion();

        return $ocr;
    }
}
