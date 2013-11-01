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
        $editorUploadFlagDescription = _t('附件指图片、文件、视频、Flash文件等');
        $editorUploadFlag = new Typecho_Widget_Helper_Form_Element_Radio('editorUploadFlag', $editorUploadFlagOptions, '1', _t('附件上传'), $editorUploadFlagDescription);


        $isShowPrettyOptions = array(
            '0' => '关闭',
            '1' => '自己在模板中添加',
            '2' => '默认分格',
        );
        $isShowPretty = new Typecho_Widget_Helper_Form_Element_Radio('isShowPretty', $isShowPrettyOptions, '2', _t('渲染代码'));

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
        $form->addInput($isShowPretty);
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
        $pluginName=self::$pluginName;

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

        $editorNewlineTag = $config->editorUploadFlag;
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
			items : ['source', '|', 'preview', 'template', 'cut', 'copy', 'paste',
        'plainpaste', 'wordpaste', '|', 'justifyleft', 'justifycenter', 'justifyright',
        'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
        'superscript', 'clearhtml', 'quickformat', 'selectall', '|', 'code', 'fullscreen', '/',
        'formatblock', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold',
        'italic', 'underline', 'strikethrough', 'lineheight', 'removeformat', '|', 'image',
        'media', 'insertfile', 'table', 'hr', 'pagebreak',
        'anchor', 'link', 'unlink', '|', 'undo', 'redo']
        });

        /*点击保存按钮的时候同步数据到数据*/
        $("#btn-submit,#btn-save").on("click", function(e) {
            keditor.sync();
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
        /**兼容原有自动保存功能**/
        if ($options->autoSave) {
            echo <<<EOF
<script type="text/javascript">
    var submitted = false, form = $('form[name=write_post],form[name=write_page]').submit(function () {
        submitted = true;
    }), savedData = null;
    var locked = false,
        formAction = form.attr('action'),
        idInput = $('input[name=cid]'),
        cid = idInput.val(),
        autoSave = $('#auto-save-message'),
        autoSaveOnce = !!cid,
        lastSaveTime = null;

    function autoSaveListener () {
        setInterval(function () {
            idInput.val(cid);
            keditor.sync();
            var data = form.serialize();

            if (savedData != data && !locked) {
                locked = true;

                autoSave.text('正在保存');
                $.post(formAction + '?do=save', data, function (o) {
                    savedData = data;
                    lastSaveTime = o.time;
                    cid = o.cid;
                    autoSave.text('内容已经保存' + ' (' + o.time + ')').effect('highlight', 1000);
                    locked = false;
                });
            }
        }, 10000);
    }

    if (autoSaveOnce) {
        savedData = form.serialize();
        autoSaveListener();
    }

    $('#text').bind('input propertychange', function () {
        if (!locked) {
            autoSave.text('内容尚未保存' + (lastSaveTime ? ' 上次保存时间: ' + lastSaveTime + ')' : ''));
        }

        if (!autoSaveOnce) {
            autoSaveOnce = true;
            autoSaveListener();
        }
    });
</script>

EOF;
        }
    }

}
