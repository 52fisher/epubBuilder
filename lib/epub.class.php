<?php
// set_error_handler('epub::error');
set_exception_handler('epub::exception');
class epub
{
    protected $toc = '';
    protected $content = '';
    protected $initCssPath = ROOT . 'css/style.css';
    protected $userStylePath = 'Styles/style.css';
    protected $tmpFile = ROOT . '_tmp.zip';
    protected $zipFile;
    // protected $
    public $c = []; //config  ['styles'=>'','txtPath'=>'','coverImg'=>'','toc'=>false];
    public $showLog = true;
    function __construct($config)
    {
        $this->c = $config;
        $this->zipFile = new ZipArchive;
        //copy_dir(ROOT . 'src', ROOT . 'tmp');
    }
    function epub_builder()
    {
        $stime = time();
        $name = iconv("UTF-8", "GBK", $this->c['bookName']) . '.epub';

        if (file_exists(ROOT . $name)) {
            if (@unlink(ROOT . $name) === false) {
                throw new Exception("文件被占用,请先解除占用", 997);
            }
        }
        if (file_exists($this->tmpFile)) {
            @unlink($this->tmpFile);
        }
        if (file_exists(TMPDIR)) {
            $this->del_dir(TMPDIR);
        }
        $this->copy_dir(ROOT . 'src', TMPDIR);
        $this->html_build();
        if ($this->zipFile->open($this->tmpFile, ZIPARCHIVE::CREATE) !== true) {
            throw new Exception("路径错误,无法创建临时缓存文件", 999);
        }
        // var_dump($full_path,$path,$parent);
        $dir = new DirectoryIterator(TMPDIR);
        foreach ($dir as $file) {
            $filename = $dir->getFilename();
            if ($file->isDot()) {
                continue;
            }
            if ($file->isDir()) {
                // $relative_path = $parent. $filename;
                $this->zipFile->addEmptyDir($filename);
                $this->zip(TMPDIR . '/' . $filename, $filename);
                continue;
            }
            $this->zipFile->addFile(TMPDIR . '/' . $filename, $filename);
        }
        $this->zipFile->close();
        // $name = iconv("UTF-8", "GBK", $this->c['bookName']) . '.epub';
        if(!rename($this->tmpFile, './' . $name)){
            throw new Exception("重命名文件失败,请先删除已生成的文件", 999);            
        }
        $this->show_log('文件处理完成');
        $timespan = time() - $stime;
        $this->show_log('耗时' . $timespan . '秒');
    }
    function zip($path = null, $parent = null)
    {
        // $zip->zip(ROOT.'_tmp.zip',ROOT.'tmp','',true);
        if ($this->zipFile->open($this->tmpFile, ZIPARCHIVE::CREATE) !== true) {
            throw new Exception("路径错误,无法创建临时缓存文件", 999);
        }
        // var_dump('绝对路径: ',$path,"  相对路径:",$parent);
        $dir = new DirectoryIterator($path);
        foreach ($dir as $file) {
            $filename = $dir->getFilename();
            $relative_path = $parent . '/' . $filename;
            if ($file->isDot()) {
                continue;
            }
            if ($file->isDir()) {
                $this->zipFile->addEmptyDir($relative_path);
                $this->zip(TMPDIR . '/' . $relative_path, $relative_path);
                continue;
            }
            $this->zipFile->addFile($path . '/' . $filename, $relative_path);
        }
    }
    function html_build()
    {
        $this->custom_style();
        $this->chapter_split();
        //目录索引
        $toc = $this->fgc(ROOT . 'tmpl/toc.ncx.tpl');
        $navPoint = '';
        $navOrder = 0;
        // 章节页面
        $chapterHtml = $this->fgc(ROOT . 'tmpl/html.tpl');
        $tmp = $chapterHtml;
        $pattern = ['%title%', '%heading%', '%content%'];
        //内容mainfest 
        $item = [];
        $itemref = [];
        //正文目录
        $html_doc_toc = [];
        if ($this->c['toc']) {
            $html_doc_toc[] = '<h2 id="toc">目录</h2>';
            array_push($html_doc_toc, '<div class="toc">', '<dl>');
            $navSnap = [
                '<navPoint id="toc" playOrder="' . (++$navOrder) . '">',
                '<navLabel>',
                '<text>目录</text>',
                '</navLabel>',
                '<content src="Text/toc.html" />',
                '</navPoint>',
            ];
            $navPoint .= implode("\n", $navSnap);
            $item[] = '<item href="Text/toc.html" id="toc" media-type="application/xhtml+xml" />';
            $itemref[] = '<itemref idref="toc" />';
        }

        if ($this->cover_img()) {
            $replacement = [
                '封面',
                '',
                '<img src="../Images/' . $this->c['coverImg'] . '" class="cover" />',
            ];
            $tmp = str_replace($pattern, $replacement, $tmp);
            $this->save(OS . 'Text/cover.html', $tmp);
            $tmp = $chapterHtml;
            $navSnap = [
                '<navPoint id="cover" playOrder="' . (++$navOrder) . '">',
                '<navLabel>',
                '<text>封面</text>',
                '</navLabel>',
                '<content src="Text/cover.html" />',
                '</navPoint>',
            ];
            $navPoint .= implode("\n", $navSnap);
            //content
            $item[] = '<item href="Images/' . $this->c['coverImg'] . '" id="coverimg" media-type="image/jpeg" />';
            //正文目录
            $html_doc_toc[] = '<dd class="toc"><a href="../Text/cover.html">封面</a></dd>';
            $item[] = '<item href="Text/cover.html" id="cover" media-type="application/xhtml+xml" />';
            $itemref[] = '<itemref idref="cover" />';
            $this->show_log('封面设置完成');
        }

        foreach ($this->content as $chapterID => $v) {

            // html 
            $replacement = [
                $v['title'],
                '<h2 id="' . $chapterID . '">' . $v['title'] . '</h2>',
                $v['content'],
            ];
            $tmp = str_replace($pattern, $replacement, $tmp);
            // navPoint
            $navSnap = [
                '<navPoint id="' . $chapterID . '" playOrder="' . (++$navOrder) . '">',
                '<navLabel>',
                '<text>' . $v['title'] . '</text>',
                '</navLabel>',
                '<content src="Text/' . $chapterID . '.html" />',
                '</navPoint>',
            ];
            $navPoint .= implode("\n", $navSnap);
            $this->save(OS . 'Text/' . $chapterID . '.html', $tmp);
            $tmp = $chapterHtml;
            $item[] = '<item href="Text/' . $chapterID . '.html" id="' . $chapterID . '" media-type="application/xhtml+xml" />';
            $itemref[] = '<itemref idref="' . $chapterID . '" />';
            //正文目录
            if ($this->c['toc']) {
                $html_doc_toc[] = '<dd class="toc"><a href="../Text/' . $chapterID . '.html">' . $v['title'] . '</a></dd>';
            }
        }
        //toc 
        $pattern = ['%bookID%', '%title%', '%navPoint%'];
        $uid = md5(time());
        $replacement = [$uid, $this->c['bookName'], $navPoint];
        $toc = str_replace($pattern, $replacement, $toc);
        $this->save(OS . 'toc.ncx', $toc);
        //content
        $pattern = ['%bookID%', '%title%', '%creator%', '%language%', '%date%', '%item%', '%itemref%'];
        $replacement = [$uid, $this->c['bookName'], $this->c['creater'], $this->c['language'], $this->c['date'], implode("\n", $item), implode("\n", $itemref)];
        $content = str_replace($pattern, $replacement, $this->fgc(ROOT . 'tmpl/content.opf.tpl'));
        $this->save(OS . 'content.opf', $content);
        //正文目录
        if ($this->c['toc']) {
            array_push($html_doc_toc, '</dl>', '</div>');
            $doc_toc = $this->fgc(ROOT . 'tmpl/html.tpl');
            $pattern = ['%title%', '%heading%', '%content%'];
            $replacement = ['目录', '', implode("\n", $html_doc_toc)];
            $doc_toc = str_replace($pattern, $replacement, $doc_toc);
            $this->save(OS . 'Text/toc.html', $doc_toc);
        }
    }
    protected function chapter_split()
    {
        if (empty($this->c['patternType'])) {
            throw new Exception("必须选择章节类型", 997);
        }
        $pattern = include ROOT . "/tmpl/pattern.inic.php";
        $fp = fopen(iconv('UTF-8', 'GBK', $this->c['txtPath']), "r");
        if ($fp === false) {
            throw new Exception("书源不存在,请检查文件", 998);
        }
        $tmp = '';
        $cn = 0;
        $chapterID = 'Chapter-' . sprintf("%04d", $cn);
        $this->content[$chapterID] = ['title' => '简介', 'content' => ''];
        while (!feof($fp)) {
            $tmp = preg_replace_callback('/' . $pattern[$this->c['patternType']] . '/u', function ($r) use (&$cn, &$chapterID) {
                $cn++;
                $chapterID = 'Chapter-' . sprintf("%04d", $cn);
                // $k = trim($r[0]);
                $this->content[$chapterID]['title'] =   preg_replace("/^(?:\s|\&nbsp\;|　|\xc2\xa0)+|(?:\s|\&nbsp\;|　|\xc2\xa0)+$/", "", $r[0]);
                $this->content[$chapterID]['content'] = '';
                // var_dump($r);
                // die;
                return;
                // var_dump($k);
            }, $this->convertToUTF8(fgets($fp))); //逐行读取。如果fgets不写length参数，默认是读取1k。
            $tmp = preg_replace("/^(?:\s|\&nbsp\;|　|\xc2\xa0)+|(?:\s|\&nbsp\;|　|\xc2\xa0)+$/", "", $tmp);
            if (empty($tmp)) {
                continue;
            }
            $this->content[$chapterID]['content'] .= '<p class="text">' . $tmp . '</p>';
        }

        fclose($fp);
        // $pattern = include ROOT . "/tmpl/pattern.inic.php";
        // $this->content = preg_split($pattern[$this->c['patternType']],$this->content);
        // var_dump(preg_split('/\s{0,4}\d+.{0,15}/', $this->content));
        // var_dump(explode('<hr/>',$this->content));
        // var_dump($this->content);
        if (count($this->content) < 10) {
            throw new Exception("章节获取不全或章节数量太少,请选择对应适当的章节类型", 999);
        }
        $this->show_log('章节分析完成');
    }
    function custom_style($append = false)
    {
        if (empty($this->c['styles'])) {
            return;
        }
        if (!file_exists($this->c['styles'])) {
            $this->c['styles'] = ROOT . 'css/' . $this->c['styles'];
        }
        $style = $this->fgc($this->c['styles']);
        if ($append) {
            $this->copy($this->initCssPath, OS . $this->userStylePath);
        }
        file_put_content(OS . $this->userStylePath, $style, FILE_APPEND);
        $this->show_log('样式加载完成');
    }
    protected function cover_img()
    {
        if (empty($this->c['coverImg'])) {
            $this->show_log('未设置封面，跳过');
            return;
        }
        $cover = $this->fgc($this->c['coverImg']);
        $_suff = explode('.', $this->c['coverImg']);
        $this->c['coverImg'] = 'cover.' . array_pop($_suff);
        $this->save(OS.'Images/'.$this->c['coverImg'],$cover);
        return true;
    }
    public function copy_dir($src, $dst)
    {
        $dir = opendir($src);
        mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->copy_dir($src . '/' . $file, $dst . '/' . $file);
                    continue;
                }
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
        closedir($dir);
    }
    public function del_dir($dir)
    {
        //先删除目录下的文件：
        $dh = opendir($dir);
        while (($file = readdir($dh)) !== false) {
            if ($file == "." || $file == "..") {
                continue;
            }
            $fullpath = $dir . "/" . $file;
            if (!is_dir($fullpath)) {
                unlink($fullpath);
                continue;
            }
            $this->del_dir($fullpath);
        }
        closedir($dh);
        //删除当前文件夹：
        rmdir($dir);
    }
    protected function copy($src, $des)
    {
        if (!copy($src, $des)) {
            throw new Exception("文件路径错误：" . $src, 999);
        }
    }
    protected function fgc($src)
    {
        $r = file_get_contents($src);
        if ($r === false) {
            throw new Exception("文件路径错误：" . $src, 999);
        }
        return $r;
    }
    protected function save($dst = 'undefined', $data)
    {
        file_put_contents($dst, $data);
    }
    protected function convertToUTF8($data, $encoding = 'utf-8')
    {
        if (!empty($data)) {
            $fileType = mb_detect_encoding($data, array('UTF-8', 'GBK', 'LATIN1', 'BIG5'));
            if ($fileType != $encoding) {
                $data = mb_convert_encoding($data, $encoding, $fileType);
            }
        }
        return $data;
    }
    function show_log($log)
    {
        if ($this->showLog) {
            echo $log . "\n";
        }
    }
    static function error($errno, $errstr, $errfile, $errline)
    {
        echo "出现错误: [$errno] $errstr \n";
        echo "行数: $errline in $errfile \n";
        die();
    }
    static function exception($exception)
    {
        echo "生成epub失败: ", $exception->getMessage();
        die;
    }
}
