<?php
define('ROOT', dirname(__FILE__) . '/');
define('TMPDIR', ROOT . 'tmp');
define('OS', TMPDIR . '/OEBPS/');
require ROOT . 'lib/epub.class.php';

## 填写区，勿动以上内容
$config = [
    'styles' => '', //自定义样式路径，本地
    'txtPath' => ROOT . '此处填写txt文件名称', //把txt文件放到index.php文件目录下
    'coverImg' => '', //自定义封面图片路径，可以为网络图片地址,建议放在本地
    'toc' => false, //是否要插入正文目录. ireader对正文目录的兼容效果不好（实际上是不支持dl dd标签），默认关闭。
    'patternType' => 1, //必填,书籍目录类型，见下方参数说明，
    'creater' => '', //作者,必填
    'bookName' => '', //书名,必填
    'language' => 'zh-CN', //语言，默认中文
    'date' => date("Y-m-d"), //发布时间,默认当前日期
];


## 填写区结束，勿动以下内容

(new epub($config))->epub_builder();