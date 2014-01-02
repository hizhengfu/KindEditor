<?php
/**
 * KindEditor编辑器<br>
 * 基于KindEditor 4.1.10
 *
 * @package KindEditor
 * @author hizhengfu
 * @version 4.0.1
 * @link https://github.com/hizhengfu/typecho-kindeditor
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
        Typecho_Plugin::factory('admin/write-post.php')->richEditor = array(self::$pluginName . '_Plugin', 'render');
        Typecho_Plugin::factory('admin/write-page.php')->richEditor = array(self::$pluginName . '_Plugin', 'render');
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

        $editorToolsOptions = array(
            'source' => 'HTML代码',
            '|' => '分隔',
            'undo' => '后退',
            'redo' => '前进',
            '|1' => '分隔',
            'preview' => '预览',
            'wordpaste' => '从Word粘贴',
            'print' => '打印',
            'template' => '插入模板',
            'code' => '插入程序代码',
            'cut' => '剪切',
            'copy' => ' 复制',
            'paste' => '粘贴',
            'plainpaste' => '粘贴为无格式文本',
            '|2' => '分隔',
            'selectall' => '全选',
            'justifyleft' => '左对齐',
            'justifycenter' => '居中',
            'justifyright' => '右对齐',
            'justifyfull' => '两端对齐',
            'insertorderedlist' => '编号',
            'insertunorderedlist' => '项目符号',
            'indent' => '增加缩进',
            'outdent' => '减少缩进',
            'subscript' => '下标',
            'superscript' => '上标',
            'removeformat' => '删除格式',
            'quickformat' => '一键排版',
            '|3' => '分隔',
            'formatblock' => '段落',
            'fontname' => '字体',
            'fontsize' => '文字大小',
            '|3' => '分隔',
            'forecolor' => '文字颜色',
            'hilitecolor' => '文字背景',
            'bold' => '粗体',
            'italic' => '斜体',
            'underline' => '下划线',
            'strikethrough' => '删除线',
            '|5' => '分隔',
            'image' => '图片',
            'flash' => 'Flash',
            'media' => '视音频',
            'table' => '表格',
            'hr' => '插入横线',
            'emoticons' => '插入表情',
            'link' => '超级链接',
            'unlink' => '取消超级链接',
            'fullscreen' => '全屏显示',
            'map' => 'Google地图',
            'baidumap' => '百度地图',
            'lineheight' => '行距',
            'clearhtml' => '清理HTML代码',
            'pagebreak' => ' 插入分页符',
            'anchor' => '插入锚点',
            'insertfile' => '插入文件',
            '|6' => '分隔',
            'about' => '关于'
        );
        $editorToolsDescription = _t('仅在默认风格有效！');

        $editorTools = new Typecho_Widget_Helper_Form_Element_Checkbox('editorTools', $editorToolsOptions, array('fontname', 'fontsize', '|',
            'forecolor', 'hilitecolor', 'bold', 'italic', 'underline', 'removeformat', '|', 'justifyleft', 'justifycenter', 'justifyright',
            'insertorderedlist', 'insertunorderedlist', '|', 'emoticons', 'image', 'link'), _t('工具栏'), $editorToolsDescription);


        $form->addInput($editorTheme);
        $form->addInput($editorLang);
        $form->addItem($line);
        $form->addInput($editorNewlineTag);
        $form->addInput($editorPasteType);
        $form->addItem($line);
        $form->addInput($editorTools);
        $form->addItem($line);
        echo <<<EOF
        <style>
            #typecho-option-item-editorTools-4 span{width:24%;display:inline-block;margin-right:0;}
        </style>
EOF;
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

        $editorUploadJson = Typecho_Common::url('action/plugins-kind-upload', Typecho_Widget::widget('Widget_Options')->index);
        $editorUploadJson .= isset($post->cid) ? "?cid={$post->cid}" : '';

        $editorNewlineTag = $config->editorNewlineTag;
        $editorPasteType = $config->editorPasteType;
        if (!in_array($editorPasteType, array('1', '2', '0'))) {
            $editorPasteType = 2;
        }

        if ($editorTheme == 'simple') {
            $items = array('fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline', 'removeformat', '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist', 'insertunorderedlist', '|', 'emoticons', 'image', 'link');
        } else if ($editorTheme == 'qq') {
            $items = array('bold', 'italic', 'underline', 'fontname', 'fontsize', 'forecolor', 'hilitecolor', 'plug-align', 'plug-order', 'plug-indent', 'link');
        } else {
            $items = $config->editorTools;
            foreach ($items as $k=>$v) {
                if (strpos($v, '|') === 0) {
                    $items[$k]='|';
                }
            }
        }
        $items = json_encode($items);


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
        	resizeType:1,
        	width : '100%',
        	height : '{$options->editorSize}px',
        	langType : '{$editorLang}',
        	allowImageUpload : true,
        	allowFlashUpload : true,
        	allowMediaUpload : true,
        	allowFileManager : false,
        	uploadJson : '{$editorUploadJson}',
        	newlineTag : '{$editorNewlineTag}',
        	pasteType : {$editorPasteType},
        	pagebreakHtml:'<!--more-->',
        	afterBlur : function() {keditor.sync();},
			items :{$items}
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