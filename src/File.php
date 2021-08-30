<?php

namespace Eb\Support;

class File extends \Illuminate\Support\Facades\File
{

    /**
     * 替换相应的字符
     *
     * @param string $path
     * @return string
     */
    public static function dirReplace($path)
    {
        return str_replace('//', '/', str_replace('\\', '/', $path));
    }

    /**
     * 判断目录是否为空
     *
     * @param string $dir
     * @return boolean
     */
    public static function isEmpty($dir)
    {
        $handle = opendir($dir);
        while (($file = readdir($handle)) !== false)
        {
            if ($file != '.' && $file != '..')
            {
                closedir($handle);
                return true;
            }
        }
        closedir($handle);
        return false;
    }

    /**
     * 文件重命名
     *
     * @param $old_name
     * @param $new_name
     * @return bool
     */
    public static function rename($old_name,$new_name)
    {
        if(($new_name!=$old_name) && static::isWritable($old_name))
        {
            return rename($old_name,$new_name);
        }
    }

    /**
     * 文件保存路径处理
     *
     * @param $path
     * @return string
     */
    public static function checkPath($path)
    {
        return (preg_match('/\/$/',$path)) ? $path : $path . '/';
    }

    /**
     * 实现文件下载的功能
     *
     * @param $file_path 绝对路径
     */
    public static function downFile($file_path) {

        //判断文件是否存在
        $file_path = iconv('utf-8', 'gb2312', $file_path); //对可能出现的中文名称进行转码
        if (!file_exists($file_path)) {
            exit('文件不存在！');
        }
        $file_name = basename($file_path); //获取文件名称
        $file_size = filesize($file_path); //获取文件大小
        $fp = fopen($file_path, 'r'); //以只读的方式打开文件
        header("Content-type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length: {$file_size}");
        header("Content-Disposition: attachment;filename={$file_name}");
        $buffer = 1024;
        $file_count = 0;
        //判断文件是否结束
        while (!feof($fp) && ($file_size-$file_count>0)) {
            $file_data = fread($fp, $buffer);
            $file_count += $buffer;
            echo $file_data;
        }
        fclose($fp); //关闭文件
    }

    /**
     * 获取文件扩展名
     *
     * @param string $filename
     * @return string
     */
    public static function getFileExtension($filename){

        $path_info = pathinfo($filename);
        return strtolower($path_info['extension']);
    }

    /**
     * 目录列表
     *
     * @param	string	$dir		路径
     * @param	int		$parentid	父id
     * @param	array	$dirs		传入的目录
     * @return	array	返回目录列表
     */
    public static function getDirTree($dir, $parentid = 0, $dirs = array()) {

        global $id;
        if ($parentid == 0) $id = 0;
        $list = glob($dir.'*');
        foreach($list as $v) {
            if (is_dir($v)) {
                $id++;
                $dirs[$id] = array('id'=>$id,'parent_id'=>$parentid, 'name'=>basename($v), 'dir'=>$v.'/');
                $dirs = static::getDirTree($v.'/', $id, $dirs);
            }
        }
        return $dirs;

    }

    /**
     * 转化 \ 为 /
     * @param	string	$path	路径
     * @return	string	路径
     */
    public static function dirPath($path) {

        $path = str_replace('\\', '/', $path);
        if(substr($path, -1) != '/') $path = $path.'/';
        return $path;

    }

    /**
     * 创建目录
     *
     * @param	string	$path	路径
     * @param	string	$mode	属性
     * @return	string	如果已经存在则返回true，否则为 false
     */
    public static function dirCreate($path, $mode = 0777) {

        if(is_dir($path)){
            return TRUE;
        }
        $path = static::dirPath($path);
        $temp = explode('/', $path);
        $cur_dir = '';
        $max = count($temp) - 1;
        for($i=0; $i<$max; $i++) {
            $cur_dir .= $temp[$i].'/';
            if (@is_dir($cur_dir)){
                continue;
            }
            @mkdir($cur_dir, $mode,true);
            @chmod($cur_dir, $mode);
        }
        return is_dir($path);
    }

    /**
     * 拷贝目录及下面所有文件
     *
     * @param	string	$from_dir	原路径
     * @param	string	$to_dir		目标路径
     * @return	string	如果目标路径不存在则返回false，否则为true
     */
    public static function dirCopy($from_dir, $to_dir) {

        $from_dir = static::dirPath($from_dir);
        $to_dir = static::dirPath($to_dir);
        if (!is_dir($from_dir)){
            return FALSE;
        }
        if (!is_dir($to_dir)) {
            static::dirCreate($to_dir);
        }
        $list = glob($from_dir.'*');
        if (!empty($list)) {
            foreach($list as $v) {
                $path = $to_dir.basename($v);
                if(is_dir($v)) {
                    static::dirCopy($v, $path);
                } else {
                    copy($v, $path);
                    @chmod($path, 0777);
                }
            }
        }
        return TRUE;

    }

    /**
     * 列出目录下所有文件
     * @param	string	$path		路径
     * @param	string	$exts		扩展名
     * @param	array	$list		增加的文件列表
     * @return	array	所有满足条件的文件
     */
    public static function dirList($path, $exts = '', $list= array()) {

        $path = static::dirPath($path);
        $files = glob($path.'*');
        foreach($files as $v) {
            if (!$exts || pathinfo($v, 4) == $exts) {
                $list[] = $v;
                if (is_dir($v)) {
                    $list = static::dirList($v, $exts, $list);
                }
            }
        }
        return $list;

    }

    /**
     * 删除目录及目录下面的所有文件
     * @param	string	$dir	路径
     * @return	bool	如果成功则返回 TRUE，失败则返回 FALSE
     */
    public static function dirDelete($dir) {

        $dir = static::dirPath($dir);
        if (!is_dir($dir)){
            return FALSE;
        }
        $list = glob($dir.'*');
        foreach($list as $v) {
            is_dir($v) ? static::dirDelete($v) : @unlink($v);
        }
        return @rmdir($dir);

    }

    /**
     * 关闭文件操作
     * @param string $path
     * @return boolean
     */
    public function close($path)
    {
        return fclose($path);
    }

    /**
     * Base64字符串生成图片文件,自动解析格式
     * @param $base64
     * @param $path
     * @param $filename
     * @return array
     */
    public static function createBase64($base64, $path, $filename) {

        $result = array();
        //匹配base64字符串格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64, $result)) {

            //保存最终的图片格式
            $postfix = $result[2];
            $base64 = base64_decode(substr(strstr($base64, ','), 1));
            $filename = $filename . '.' . $postfix;
            $path = $path . $filename;
            //创建图片
            if (file_put_contents($path, $base64)) {
                $result['state'] = 1;
                $result['filename'] = $filename;
            } else {
                $result['state'] = 2;
                $result['err'] = 'Create img failed!';
            }
        } else {
            $result['state'] = 2;
            $result['err'] = 'Not base64 char!';
        }

        return $result;

    }

    /**
     * 文件字节转具体大小 array("B", "KB", "MB", "GB", "TB", "PB","EB","ZB","YB")， 默认转成M
     * @param $size 文件字节
     * @return string
     */
    public static function byteFormat($size, $dec = 2 )
    {
        $units = array("B", "KB", "MB", "GB", "TB", "PB","EB","ZB","YB");
        $pos = 0;
        while ($size >= 1024)
        {
            $size /= 1024;
            $pos++;
        }
        return round($size,$dec)." ".$units[$pos];
    }

    /**
     * 删除文件
     * @param string $path
     * @return boolean
     */
    public static function unlinkFile($path)
    {
        $path = static::dirReplace($path);
        if (file_exists($path))
        {
            return unlink($path);
        }
    }

    /**
     * 文件操作(复制/移动)
     * @param string $old_path 指定要操作文件路径(需要含有文件名和后缀名)
     * @param string $new_path 指定新文件路径（需要新的文件名和后缀名）
     * @param string $type 文件操作类型
     * @param boolean $overWrite 是否覆盖已存在文件
     * @return boolean
     */
    public static function handleFile($old_path,$new_path,$type='copy', $overWrite=FALSE)
    {

        $old_path = static::dirReplace($old_path);
        $new_path = static::dirReplace($new_path);
        if(file_exists($new_path) && $overWrite=FALSE)
        {
            return FALSE;
        }
        else if(file_exists($new_path) && $overWrite=TRUE)
        {
            static::unlinkFile($new_path);
        }

        $aimDir = dirname($new_path);
        static::dirCreate($aimDir);
        if($type=='move'){
            return rename($old_path,$new_path);
        }else{ // copy
            return copy($old_path,$new_path);
        }

    }

    /**
     * 文件夹操作(复制/移动)
     * @param string $old_path 指定要操作文件夹路径
     * @param string $aimDir 指定新文件夹路径
     * @param string $type 操作类型
     * @param boolean $overWrite 是否覆盖文件和文件夹
     * @return boolean
     */
    public static function handleDir($old_path, $new_path, $type='copy', $overWrite=FALSE)
    {
        $new_path = static::checkPath($new_path);
        $old_path = static::checkPath($old_path);
        if (!is_dir($old_path)) return FALSE;

        if (!file_exists($new_path)) static::dirCreate($new_path);

        $dirHandle = opendir($old_path);

        if (!$dirHandle) return FALSE;

        $boolean = TRUE;

        while(FALSE !== ($file=readdir($dirHandle)))
        {
            if ($file=='.' || $file=='..') continue;

            if (!is_dir($old_path.$file))
            {
                $boolean = static::handleFile($old_path.$file,$new_path.$file,$type,$overWrite);
            }
            else
            {
                static::handleDir($old_path.$file,$new_path.$file,$type,$overWrite);
            }
        }

        if($type=='move'){
            closedir($dirHandle);
            return rmdir($old_path);
        }else{ // copy
            closedir($dirHandle);
            return $boolean;
        }

    }

    /**
     * 返回指定文件和目录的信息
     * @param string $file
     * @return ArrayObject
     */
    public static function listInfo($file)
    {
        $dir = array();

        $dir['filename']   = basename($file);//返回路径中的文件名部分。
        $dir['pathname']   = realpath($file);//返回绝对路径名。
        $dir['owner']      = fileowner($file);//文件的 user ID （所有者）。
        $dir['perms']      = fileperms($file);//返回文件的 inode 编号。
        $dir['inode']      = fileinode($file);//返回文件的 inode 编号。
        $dir['group']      = filegroup($file);//返回文件的组 ID。
        $dir['path']       = dirname($file);//返回路径中的目录名称部分。
        $dir['atime']      = fileatime($file);//返回文件的上次访问时间。
        $dir['ctime']      = filectime($file);//返回文件的上次改变时间。
        $dir['perms']      = fileperms($file);//返回文件的权限。
        $dir['size']       = filesize($file);//返回文件大小。
        $dir['type']       = filetype($file);//返回文件类型。
        $dir['ext']        = is_file($file) ? pathinfo($file,PATHINFO_EXTENSION) : '';//返回文件后缀名
        $dir['mtime']      = filemtime($file);//返回文件的上次修改时间。
        $dir['isDir']      = is_dir($file);//判断指定的文件名是否是一个目录。
        $dir['isFile']     = is_file($file);//判断指定文件是否为常规的文件。
        $dir['isLink']     = is_link($file);//判断指定的文件是否是连接。
        $dir['isReadable'] = is_readable($file);//判断文件是否可读。
        $dir['isWritable'] = is_writable($file);//判断文件是否可写。
        $dir['isUpload']   = is_uploaded_file($file);//判断文件是否是通过 HTTP POST 上传的。

        return $dir;

    }

    /**
     * 返回关于打开文件的信息
     * @param $file
     * @return ArrayObject
     * 数字下标     关联键名（自 PHP 4.0.6）     说明
     * 0     dev     设备名
     * 1     ino     号码
     * 2     mode     inode 保护模式
     * 3     nlink     被连接数目
     * 4     uid     所有者的用户 id
     * 5     gid     所有者的组 id
     * 6     rdev     设备类型，如果是 inode 设备的话
     * 7     size     文件大小的字节数
     * 8     atime     上次访问时间（Unix 时间戳）
     * 9     mtime     上次修改时间（Unix 时间戳）
     * 10    ctime     上次改变时间（Unix 时间戳）
     * 11    blksize     文件系统 IO 的块大小
     * 12    blocks     所占据块的数目
     */
    public static function openInfo($file)
    {
        $file = fopen($file,"r");
        $result = fstat($file);
        fclose($file);
        return $result;
    }

    /**
     * 改变文件和目录的相关属性
     * @param string $file 文件路径
     * @param string $type 操作类型 group  mode  ower
     * @param string $ch_info 操作信息
     * @return boolean
     */
    public static function changeFile($file,$type,$ch_info)
    {
        switch ($type)
        {
            case 'group' : $is_ok = chgrp($file,$ch_info);//改变文件组。
                break;
            case 'mode' : $is_ok = chmod($file,$ch_info);//改变文件模式。
                break;
            case 'ower' : $is_ok = chown($file,$ch_info);//改变文件所有者。
                break;
        }
    }

    /**
     * 取得上传文件信息
     * @param $file file属性信息
     * @return array
     */
    public static function getUploaFileInfo($file)
    {
        $file_info = $_FILES[$file];//取得上传文件基本信息
        $info = array();
        $info['type']  = strtolower(trim(stripslashes(preg_replace("/^(.+?);.*$/", "\\1", $file_info['type'])), '"'));//取得文件类型
        $info['temp']  = $file_info['tmp_name'];//取得上传文件在服务器中临时保存目录
        $info['size']  = $file_info['size'];//取得上传文件大小
        $info['error'] = $file_info['error'];//取得文件上传错误
        $info['name']  = $file_info['name'];//取得上传文件名
        $info['ext']   = static::getFileExtension($file_info['name']);//取得上传文件后缀
        return $info;
    }

    /**
     * 设置文件命名规则
     * @param string $type 命名规则
     * @param string $filename 文件名
     * @return string
     */
    public static function setFileName($type)
    {
        switch ($type)
        {
            case 'hash' : $new_file = md5(uniqid(mt_rand()));//mt_srand()以随机数md5加密来命名
                break;
            case 'time' : $new_file = time();
                break;
            default : $new_file = date($type,time());//以时间格式来命名
                break;
        }
        return $new_file;
    }

    /**
     * 创建指定路径下的指定文件
     * @param string $path(需要包含文件名和后缀)
     * @param boolean $over_write 是否覆盖文件
     * @param int $time 设置时间。默认是当前系统时间
     * @param int $atime 设置访问时间。默认是当前系统时间
     * @return boolean
     */
    public static function createFile($path,$over_write=FALSE,$time=NULL,$atime=NULL)
    {
        $path = static::dirReplace($path);
        $time = empty($time) ? time() : $time;
        $atime = empty($atime) ? time() : $atime;
        if(file_exists($path) && $over_write)
        {
            static::unlinkFile($path);
        }
        $aimDir = dirname($path);
        static::dirCreate($aimDir);
        return touch($path,$time,$atime);
    }

    /**
     * 读取文件操作
     * @param string $file
     * @return boolean
     */
    public static function readFile($file)
    {
        return @file_get_contents($file);
    }

    /**
     * 确定服务器的最大上传限制（字节数）
     * @return int 服务器允许的最大上传字节数
     */
    public function allowUploadSize()
    {
        $val = trim(ini_get('upload_max_filesize'));
        return $val;
    }
}
