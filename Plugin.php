<?php
/**
 * KindEditor编辑器
 *
 * @package KindEditor
 * @author hizhengfu
 * @version 4.0
 */
class KindEditor_Plugin implements Typecho_Plugin_Interface
{
    private static $pluginName = 'KindEditor';

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('admin/write-post.php')->richEditor = array(__CLASS__, 'render');
        Typecho_Plugin::factory('admin/write-page.php')->richEditor = array(__CLASS__, 'render');
        Helper::addAction('plugins-kind-upload', 'KindEditor_Upload');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        Helper::removeAction('plugins-kind-upload');
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $editorThemeOptions = array(
            'default' => '默认风格',
            'simple' => '简洁风格',
            'qq' => 'QQ风格'
        );
        $editorTheme = new Typecho_Widget_Helper_Form_Element_Select('editorTheme', $editorThemeOptions, 'default', _t('皮肤'));

        $editorLangOptions = array(
            'zh_CN' => '中文简体',
            'en' => 'English'
        );
        $editorLang = new Typecho_Widget_Helper_Form_Element_Select('editorLang', $editorLangOptions, 'zh_CN', '语言');


        $editorUploadFlagOptions = array('1' => '开启', '0' => '关闭');
        $editorUploadFlagDescription = _t('附件指图片、文件、视频、Flash文件等。上传文件类型与系统一致。');
        $editorUploadFlag = new Typecho_Widget_Helper_Form_Element_Radio('editorUploadFlag', $editorUploadFlagOptions, '1', _t('附件上传'), $editorUploadFlagDescription);


        $line = new Typecho_Widget_Helper_Layout('hr');

        $editorNewlineTagOptions = array(
            'br' => '新开始行',
            'p' => '新开始段落',
        );
        $editorNewlineTag = new Typecho_Widget_Helper_Form_Element_Radio('editorNewlineTag', $editorNewlineTagOptions, 'p', _t('回车处理'));

        $editorPasteTypeOptions = array(
            '0' => '禁止',
            '1' => '纯文本',
            '2' => 'HTML',
        );
        $editorPasteType = new Typecho_Widget_Helper_Form_Element_Radio('editorPasteType', $editorPasteTypeOptions, '2', _t('粘贴类型'));

        $form->addInput($editorTheme);
        $form->addInput($editorLang);
        $form->addInput($editorUploadFlag);
        $form->addItem($line);
        $form->addInput($editorNewlineTag);
        $form->addInput($editorPasteType);
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 插件实现方法
     *
     * @access public
     * @return void
     */
    public static function render($post)
    {
        $options = Helper::options();
        $config = $options->plugin(self::$pluginName);
        $pluginName = self::$pluginName;

        $editorTheme = $config->editorTheme;
        $editorLang = $config->editorLang;
        if (!$editorLang) {
            $editorLang = 'zh_CN';
        }

        $editor_default_css_url = Typecho_Common::url("$pluginName/editor/themes/default/default.css", $options->pluginUrl);
        $editor_css_url = Typecho_Common::url("$pluginName/editor/themes/$editorTheme/$editorTheme.css", $options->pluginUrl);
        $kindeditor_js_url = Typecho_Common::url("$pluginName/editor/kindeditor-min.js", $options->pluginUrl);
        $lang_js_url = Typecho_Common::url("$pluginName/editor/lang/$editorLang.js", $options->pluginUrl);


        $editorUploadFlag = $config->editorUploadFlag ? 'true' : 'false';
        $editorUploadJson = Typecho_Common::url('action/plugins-kind-upload', Typecho_Widget::widget('Widget_Options')->index);

        $editorUploadJson .= isset($post->cid) ? "?cid={$post->cid}" : '';

        $editorNewlineTag = $config->editorNewlineTag;
        $editorPasteType = $config->editorPasteType;
        if (!in_array($editorPasteType, array('1', '2', '0'))) {
            $editorPasteType = 2;
        }

        echo <<<EOF
<link rel="stylesheet" href="{$editor_default_css_url}" />
<link rel="stylesheet" href="{$editor_css_url}" />
<script type="text/javascript" src="{$kindeditor_js_url}"></script>
<script type="text/javascript" src="{$lang_js_url}"></script>
<script type="text/javascript">
var keditor;
KindEditor.ready(function(K) {
        keditor = K.create("textarea#text", {
        	themeType : '{$editorTheme}',
        	width : '100%',
        	height : '300px',
        	langType : '{$editorLang}',
        	allowImageUpload : {$editorUploadFlag},
        	allowFlashUpload : {$editorUploadFlag},
        	allowMediaUpload : {$editorUploadFlag},
        	allowFileManager : false,
        	uploadJson : '{$editorUploadJson}',
        	newlineTag : '{$editorNewlineTag}',
        	pasteType : {$editorPasteType},
        	pagebreakHtml:'<!--more-->',
        	afterBlur : function() {keditor.sync();},
			items : ['source', '|', 'preview', 'template', 'cut', 'copy', 'paste',
        'plainpaste', 'wordpaste', '|', 'justifyleft', 'justifycenter', 'justifyright',
        'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
        'superscript', 'clearhtml', 'quickformat', 'selectall', '|', 'code', 'fullscreen', '/',
        'formatblock', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold',
        'italic', 'underline', 'strikethrough', 'lineheight', 'removeformat', '|', 'image',
        'media', 'insertfile', 'table', 'hr',
        'anchor', 'link', 'unlink', '|', 'undo', 'redo','|','pagebreak']
        });

        //插入编辑器
        Typecho.insertFileToEditor = function (file, url, isImage) {
            var html='<a href="' + url + '" title="' + file + '">' + file + '</a>';
            if(isImage){
                html= '<a href="' + url + '" title="' + file + '"><img src="' + url + '" alt="' + file + '" /></a>';
            }
            keditor.insertHtml(html).hideDialog().focus();
        };
});
</script>
EOF;
    }
}