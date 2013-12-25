<?php
/**
 * @package KindEditor
 * @author hizhengfu
 */
class KindEditor_Upload extends Widget_Upload
{
    /**
     * 创建上传路径
     *
     * @access private
     * @param string $path 路径
     * @return boolean
     */
    private static function makeUploadDir($path)
    {
        $path = preg_replace("/\\\+/", '/', $path);
        $current = rtrim($path, '/');
        $last = $current;

        while (!is_dir($current) && false !== strpos($path, '/')) {
            $last = $current;
            $current = dirname($current);
        }

        if ($last == $current) {
            return true;
        }

        if (!@mkdir($last)) {
            return false;
        }

        $stat = @stat($last);
        $perms = $stat['mode'] & 0007777;
        @chmod($last, $perms);

        return self::makeUploadDir($path);
    }

    /**
     * 上传文件处理函数,如果需要实现自己的文件哈希或者特殊的文件系统,请在options表里把uploadHandle改成自己的函数
     *
     * @access public
     * @param array $file 上传的文件
     * @return mixed
     */
    public static function uploadHandle($file)
    {
        if (empty($file['name'])) {
            return '请选择文件！';
        }

        $result = Typecho_Plugin::factory('Widget_Upload')->trigger($hasUploaded)->uploadHandle($file);
        if ($hasUploaded) {
            return $result;
        }

        $fileName = preg_split("(\/|\\|:)", $file['name']);
        $file['name'] = array_pop($fileName);

        //获取扩展名
        $ext = '';
        $part = explode('.', $file['name']);
        if (($length = count($part)) > 1) {
            $ext = strtolower($part[$length - 1]);
        }

        if (!self::checkFileType($ext)) {
            return $ext . '类型文件不允许上传！';
        }

        $options = Typecho_Widget::widget('Widget_Options');
        $date = new Typecho_Date($options->gmtTime);
        $path = Typecho_Common::url(defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : parent::UPLOAD_DIR,
                __TYPECHO_ROOT_DIR__) . '/' . $date->year . '/' . $date->month;

        //创建上传目录
        if (!is_dir($path)) {
            if (!self::makeUploadDir($path)) {
                return '不能创建上传目录！';
            }
        }

        //获取文件名
        $fileName = sprintf('%u', crc32(uniqid())) . '.' . $ext;
        $path = $path . '/' . $fileName;

        if (isset($file['tmp_name'])) {

            //移动上传文件
            if (!move_uploaded_file($file['tmp_name'], $path)) {
                return '上传文件失败!';
            }
        } else if (isset($file['bytes'])) {

            //直接写入文件
            if (!file_put_contents($path, $file['bytes'])) {
                return '上传文件失败!';
            }
        } else {
            return '上传文件失败!';
        }

        if (!isset($file['size'])) {
            $file['size'] = filesize($path);
        }

        //返回相对存储路径
        return array(
            'name' => $file['name'],
            'path' => (defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : self::UPLOAD_DIR)
                . '/' . $date->year . '/' . $date->month . '/' . $fileName,
            'size' => $file['size'],
            'type' => $ext,
            'mime' => Typecho_Common::mimeContentType($path)
        );
    }

    /**
     * 执行上传程序
     *
     * @access public
     * @return void
     */
    public function upload()
    {
        if (!empty($_FILES)) {
            $file = array_pop($_FILES);
            if (0 == $file['error'] && is_uploaded_file($file['tmp_name'])) {
                $result = self::uploadHandle($file);

                if (is_array($result)) {
                    $struct = array(
                        'title' => $result['name'],
                        'slug' => $result['name'],
                        'type' => 'attachment',
                        'status' => 'publish',
                        'text' => serialize($result),
                        'allowComment' => 1,
                        'allowPing' => 0,
                        'allowFeed' => 1
                    );

                    if (isset($this->request->cid)) {
                        $cid = $this->request->filter('int')->cid;

                        if ($this->isWriteable($this->db->sql()->where('cid = ?', $cid))) {
                            $struct['parent'] = $cid;
                        }
                    }

                    $insertId = $this->insert($struct);

                    $this->db->fetchRow($this->select()->where('table.contents.cid = ?', $insertId)
                        ->where('table.contents.type = ?', 'attachment'), array($this, 'push'));

                    $this->throwJson(array('error' => 0, 'url' => $this->attachment->url));
                }
            }
        }

        $this->throwJson(array('error' => 1, 'message' => $result));
    }

    public function throwJson($message)
    {
        /** 设置http头信息 */
        $this->response->setContentType();

        echo json_encode($message);

        /** 终止后续输出 */
        exit;
    }

}

?>
