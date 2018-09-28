# Lightpdf-Ocr
基于Lightpdf的Ocr识别类

## 概述

本代码只是应用一个实现的可能，请勿滥用本包。

请手动至 [https://lightpdf.com/zh/ocr](https://lightpdf.com/zh/ocr) 识别。

## 运行环境
- PHP 5.3+
- cURL extension
- JSON extension

## 安装方法

1. 建议您通过composer管理您的项目依赖，可以在你的项目根目录运行：

        $ composer require imdong/lightpdf-ocr

   或者在你的`composer.json`中声明对Lightpdf-Ocr的依赖：

        "require": {
            "imdong/lightpdf-ocr": "*"
        }

   然后通过`composer install`安装依赖。composer安装完成后，在您的PHP代码中引入依赖即可：

        require_once __DIR__ . '/vendor/autoload.php';

## 快速使用

1. 同步使用，可能会由于时间太久导致超时：

        $ocrBody = ImDong\LightpdfOcr::inFile($filename);
  
2. 异步使用，网页推荐这样使用。

        // 创建 Ocr 识别对象，获取并保存返回状态值
        $clientStatus = ImDong\LightpdfOcr::inFileAsync($filename);
    
        // 异步轮训调用接口并传回状态值直至获取到结果
        $ocrInfo = ImDong\LightpdfOcr::inFileAsyncCheck($clientStatus);
