<?php
/**
 * @package KindEditor
 * @author hizhengfu
 */
class KindEditor_Upload extends Widget_Upload implements Widget_Interface_Do
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
        if (!@mkdir($path, 0777, true)) {
            return false;
        }

        $stat = @stat($path);
        $perms = $stat['mode'] & 0007777;
        @chmod($path, $perms);

        return true;
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
        $path = Typecho_Common::url(parent::UPLOAD_PATH, __TYPECHO_ROOT_DIR__);

        //创建上传目录
        if (!is_dir($path)) {
            if (!self::makeUploadDir($path)) {
                return '不能创建上传文件夹！';
            }
        }

        //创建年份目录
        if (!is_dir($path = $path . '/' . $date->year)) {
            if (!self::makeUploadDir($path)) {
                return '不能创建上传文件夹!';
            }
        }

        //创建月份目录
        if (!is_dir($path = $path . '/' . $date->month)) {
            if (!self::makeUploadDir($path)) {
                return '不能创建上传文件夹!';
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
            'path' => self::UPLOAD_PATH . '/' . $date->year . '/' . $date->month . '/' . $fileName,
            'size' => $file['size'],
            'type' => $ext,
            'mime' => Typecho_Common::mimeContentType($path)
        );
    }

    /**
     * 修改文件处理函数,如果需要实现自己的文件哈希或者特殊的文件系统,请在options表里把modifyHandle改成自己的函数
     *
     * @access public
     * @param array $content 老文件
     * @param array $file 新上传的文件
     * @return mixed
     */
    public static function modifyHandle($content, $file)
    {
        if (empty($file['name'])) {
            return '请选择文件！';;
        }

        $result = Typecho_Plugin::factory('Widget_Upload')->trigger($hasModified)->modifyHandle($content, $file);
        if ($hasModified) {
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

        if ($content['attachment']->type != $ext) {
            return '文件扩展名不能修改!';
        }

        $path = Typecho_Common::url($content['attachment']->path, __TYPECHO_ROOT_DIR__);

        if (isset($file['tmp_name'])) {

            @unlink($path);

            //移动上传文件
            if (!move_uploaded_file($file['tmp_name'], $path)) {
                return '上传文件失败!';
            }
        } else if (isset($file['bytes'])) {

            @unlink($path);

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
            'name' => $content['attachment']->name,
            'path' => $content['attachment']->path,
            'size' => $file['size'],
            'type' => $content['attachment']->type,
            'mime' => $content['attachment']->mime
        );
    }

    /**
     * 执行升级程序
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

                    $this->response->throwJson(array('error' => 0, 'url' => $this->attachment->url));
                }
            }
        }

        $this->response->throwJson(array('error' => 1, 'message' => $result));
    }

}

?>
