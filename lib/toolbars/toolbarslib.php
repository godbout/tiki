<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}

include_once('lib/smarty_tiki/block.self_link.php');

$toolbarPickerIndex = -1;

abstract class Toolbar
{
    protected $wysiwyg;
    protected $icon;
    protected $iconname;
    protected $label;
    protected $type;

    private $requiredPrefs = [];

    public static function getTag($tagName, $wysiwyg = false, $is_html = false) // {{{
    {
        global $section;
        //we detect sheet first because it has unique buttons
        if ($section == 'sheet' && $tag = ToolbarSheet::fromName($tagName)) {
            return $tag;
        } elseif ($wysiwyg && $tag = ToolbarCkOnly::fromName($tagName, $is_html)) {
            return $tag;
        } elseif ($tag = Toolbar::getCustomTool($tagName)) {
            return $tag;
        } elseif ($tag = ToolbarInline::fromName($tagName)) {
            return $tag;
        } elseif ($tag = ToolbarBlock::fromName($tagName)) {
            return $tag;
        } elseif ($tag = ToolbarLineBased::fromName($tagName)) {
            return $tag;
        } elseif ($tag = ToolbarWikiplugin::fromName($tagName)) {
            return $tag;
        } elseif ($tag = ToolbarPicker::fromName($tagName)) {
            return $tag;
        } elseif ($tag = ToolbarDialog::fromName($tagName)) {
            return $tag;
        } elseif ($tagName == 'fullscreen') {
            return new ToolbarFullscreen;
        } elseif ($tagName == 'tikiimage') {
            return new ToolbarFileGallery;
        } elseif ($tagName == 'tikifile') {
            return new ToolbarFileGalleryFile;
        } elseif ($tagName == 'help') {
            return new ToolbarHelptool;
        } elseif ($tagName == 'switcheditor') {
            return new ToolbarSwitchEditor;
        } elseif ($tagName == 'admintoolbar') {
            return new ToolbarAdmin;
        } elseif ($tagName == '-') {
            return new ToolbarSeparator;
        } elseif ($tagName == '|') {
            return new ToolbarSpacer;
        }
    } // }}}

    public static function getList($include_custom = true) // {{{
    {
        global $tikilib;
        $parserlib = TikiLib::lib('parser');
        $plugins = $parserlib->plugin_get_list();

        foreach ($plugins as & $name) {
            $name = "wikiplugin_$name";
        }

        if ($include_custom) {
            $custom = Toolbar::getCustomList();
            $plugins = array_merge($plugins, $custom);
        }

        return array_unique(
            array_merge(
                [
                    '-',
                    '|',
                    'bold',
                    'italic',
                    'underline',
                    'strike',
                    'code',
                    'sub',
                    'sup',
                    'tikilink',
                    'link',
                    'anchor',
                    'color',
                    'bgcolor',
                    'center',
                    'table',
                    'rule',
                    'pagebreak',
                    'box',
                    'email',
                    'h1',
                    'h2',
                    'h3',
                    'titlebar',
                    'pastlink',
                    'toc',
                    'list',
                    'numlist',
                    'specialchar',
                    'smiley',
                    'templates',
                    'cut',
                    'copy',
                    'paste',
                    'pastetext',
                    'pasteword',
                    'print',
                    'spellcheck',
                    'undo',
                    'redo',
                    'find',
                    'replace',
                    'selectall',
                    'removeformat',
                    'showblocks',
                    'left',
                    'right',
                    'full',
                    'indent',
                    'outdent',
                    'unlink',
                    'style',
                    'fontname',
                    'fontsize',
                    'format',
                    'source',
                    'fullscreen',
                    'help',
                    'tikiimage',
                    'tikifile',
                    'switcheditor',
                    'autosave',
                    'admintoolbar',
                    'nonparsed',
                    'bidiltr',
                    'bidirtl',
                    'screencapture',
                    'image',

                    'sheetsave',	// spreadsheet ones
                    'addrow',
                    'addrowmulti',
                    'addrowbefore',
                    'deleterow',
                    'addcolumn',
                    'addcolumnbefore',
                    'deletecolumn',
                    'addcolumnmulti',
                    'sheetgetrange',
                    'sheetfind',
                    'sheetrefresh',
                    'sheetclose',

                    'objectlink',
                    'tikitable',
                ],
                $plugins
            )
        );
    } // }}}

    public static function getCustomList()
    {
        global $prefs;
        if (isset($prefs['toolbar_custom_list'])) {
            $custom = @unserialize($prefs['toolbar_custom_list']);
            sort($custom);
        } else {
            $custom = [];
        }

        return $custom;
    }

    public static function getCustomTool($name)
    {
        global $prefs;
        if (isset($prefs["toolbar_tool_$name"])) {
            $data = unserialize($prefs["toolbar_tool_$name"]);
            $tag = Toolbar::fromData($name, $data);

            return $tag;
        }

        return null;
    }

    public static function isCustomTool($name)
    {
        global $prefs;

        return isset($prefs["toolbar_tool_$name"]);
    }

    public static function saveTool($name, $label, $icon = 'img/icons/shading.png', $token = '', $syntax = '', $type = 'Inline', $plugin = '')
    {
        global $tikilib;

        $name = strtolower(TikiLib::remove_non_word_characters_and_accents($name));
        $standard_names = Toolbar::getList(false);
        $custom_list = Toolbar::getCustomList();
        if (in_array($name, $standard_names)) {		// don't allow custom tools with the same name as standard ones
            $c = 1;
            while (in_array($name . '_' . $c, $custom_list)) {
                $c++;
            }
            $name = $name . '_' . $c;
        }

        $prefName = "toolbar_tool_$name";
        $data = ['name' => $name, 'label' => $label, 'token' => $token, 'syntax' => $syntax, 'type' => $type, 'plugin' => $plugin];

        if (strpos($icon, 'img/icons/') !== false) {
            $data['icon'] = $icon;
        } else {
            $data['iconname'] = $icon;
        }

        $tikilib->set_preference($prefName, serialize($data));

        if (! in_array($name, $custom_list)) {
            $custom_list[] = $name;
            $tikilib->set_preference('toolbar_custom_list', serialize($custom_list));
        }
    }

    public static function deleteTool($name)
    {
        global $prefs, $tikilib;

        $name = strtolower($name);

        $prefName = "toolbar_tool_$name";
        if (isset($prefs[$prefName])) {
            $tikilib->delete_preference($prefName);

            $list = [];
            if (isset($prefs['toolbar_custom_list'])) {
                $list = unserialize($prefs['toolbar_custom_list']);
            }
            if (in_array($name, $list)) {
                $list = array_diff($list, [$name]);
                $tikilib->set_preference('toolbar_custom_list', serialize($list));
            }
        }
    }

    public static function deleteAllCustomTools()
    {
        $tikilib = TikiLib::lib('tiki');

        $tikilib->query('DELETE FROM `tiki_preferences` WHERE `name` LIKE \'toolbar_tool_%\'');
        $tikilib->delete_preference('toolbar_custom_list');
    }


    public static function fromData($tagName, $data)
    {
        // {{{

        $tag = null;

        switch ($data['type']) {
            case 'Inline':
                $tag = new ToolbarInline();
                 $tag->setSyntax($data['syntax']);

                break;
            case 'Block':
                $tag = new ToolbarBlock();
                $tag->setSyntax($data['syntax']);

                break;
            case 'LineBased':
                $tag = new ToolbarLineBased();
                $tag->setSyntax($data['syntax']);

                break;
            case 'Picker':
                $tag = new ToolbarPicker();

                break;
            case 'Separator':
                $tag = new ToolbarSeparator();

                break;
            case 'Spacer':
                $tag = new ToolbarSpacer();

                break;
            case 'CkOnly':
                $tag = new ToolbarCkOnly($tagName);

                break;
            case 'Fullscreen':
                $tag = new ToolbarFullscreen();

                break;
            case 'TextareaResize':
                $tag = new ToolbarTextareaResize();

                break;
            case 'Helptool':
                $tag = new ToolbarHelptool();

                break;
            case 'FileGallery':
                $tag = new ToolbarFileGallery();

                break;
            case 'Wikiplugin':
                if (! isset($data['plugin'])) {
                    $data['plugin'] = '';
                }
                $tag = ToolbarWikiplugin::fromName('wikiplugin_' . $data['plugin']);
                if (empty($tag)) {
                    $tag = new ToolbarWikiplugin();
                }

                break;
            default:
                $tag = new ToolbarInline();

                break;
        }

        $tag->setLabel($data['label'])
            ->setWysiwygToken($data['token'])
            ->setIconName(! empty($data['iconname']) ? $data['iconname'] : 'help')
            ->setIcon(! empty($data['icon']) ? $data['icon'] : 'img/icons/shading.png')
            ->setType($data['type']);

        return $tag;
    }	// }}}

    abstract public function getWikiHtml($areaId);

    public function isAccessible() // {{{
    {
        global $prefs;

        foreach ($this->requiredPrefs as $prefName) {
            if (! isset($prefs[$prefName]) || $prefs[$prefName] != 'y') {
                return false;
            }
        }

        return true;
    } // }}}

    protected function addRequiredPreference($prefName) // {{{
    {
        $this->requiredPrefs[] = $prefName;
    } // }}}

    protected function setIcon($icon) // {{{
    {
        $this->icon = $icon;

        return $this;
    } // }}}

    protected function setIconName($iconname) // {{{
    {
        $this->iconname = $iconname;

        return $this;
    } // }}}

    protected function setLabel($label) // {{{
    {
        $this->label = $label;

        return $this;
    } // }}}

    protected function setWysiwygToken($token) // {{{
    {
        $this->wysiwyg = $token;

        return $this;
    } // }}}

    protected function setSyntax($syntax) // {{{
    {
        return $this;
    } // }}}

    protected function setType($type) // {{{
    {
        $this->type = $type;

        return $this;
    } // }}}

    public function getIcon() // {{{
    {
        return $this->icon;
    } // }}}

    public function getLabel() // {{{
    {
        return $this->label;
    } // }}}

    public function getWysiwygToken($areaId) // {{{
    {
        return $this->wysiwyg;
    } // }}}


    public function getWysiwygWikiToken($areaId) // {{{ // wysiwyg_htmltowiki
    {
        return null;
    } // }}}

    public function getSyntax($areaId) // {{{
    {
        return '';
    } // }}}

    public function getType() // {{{
    {
        return $this->type;
    } // }}}

    public function getIconHtml() // {{{
    {
        if (! empty($this->iconname)) {
            $iname = $this->iconname;
        } elseif (! empty($this->icon)) {
            $headerlib = TikiLib::lib('header');

            return '<img src="' . htmlentities($headerlib->convert_cdn($this->icon), ENT_QUOTES, 'UTF-8') . '" alt="'
            . htmlentities($this->getLabel(), ENT_QUOTES, 'UTF-8') . '" title=":'
            . htmlentities($this->getLabel(), ENT_QUOTES, 'UTF-8') . '" class="tips bottom icon">';
        } else {
            $iname = 'help';
        }
        $smarty = TikiLib::lib('smarty');
        $smarty->loadPlugin('smarty_function_icon');

        return smarty_function_icon(['name' => $iname, 'ititle' => ':'
                . htmlentities($this->getLabel(), ENT_QUOTES, 'UTF-8'), 'iclass' => 'tips bottom'], $smarty->getEmptyInternalTemplate());
    } // }}}

    public function getSelfLink($click, $title, $class)
    {
        // {{{
        global $prefs;
        $smarty = TikiLib::lib('smarty');
        $params = [];
        $params['_onclick'] = $click . (substr($click, strlen($click) - 1) != ';' ? ';' : '') . 'return false;';
        $params['_class'] = 'toolbar btn btn-sm px-2 tips bottom' . (! empty($class) ? ' ' . $class : '');
        $params['_ajax'] = 'n';
        $content = $title;
        if ($this->iconname) {
            $params['_icon_name'] = $this->iconname;
            $colon = $prefs['javascript_enabled'] === 'y' ? ':' : '';
            $params['_title'] = $colon . $title;
        } else {
            $params['_icon'] = $this->icon;
        }

        if (strpos($class, 'qt-plugin') !== false && ($this->iconname == 'plugin'
                || $this->icon == 'img/icons/plugin.png')) {
            $params['_menu_text'] = 'y';
            $params['_menu_icon'] = 'y';
        }
        $smarty->loadPlugin('smarty_block_self_link');

        return smarty_block_self_link($params, $content, $smarty->getEmptyInternalTemplate());
    } // }}}

    protected function setupCKEditorTool($js, $name, $label = '', $icon = '')
    {
        if (empty($label)) {
            $label = $name;
        }
        $label = addcslashes($label, "'");
        TikiLib::lib('header')->add_js(
            <<< JS
if (typeof window.CKEDITOR !== "undefined" && !window.CKEDITOR.plugins.get("{$name}")) {
	window.CKEDITOR.config.extraPlugins += (window.CKEDITOR.config.extraPlugins ? ',{$name}' : '{$name}' );
	window.CKEDITOR.plugins.add( '{$name}', {
		init : function( editor ) {
			var command = editor.addCommand( '{$name}', new window.CKEDITOR.command( editor , {
				modes: { wysiwyg:1 },
				exec: function (editor, data) {
					{$js}
				},
				canUndo: false
			}));
			editor.ui.addButton( '{$name}', {
				label : '{$label}',
				command : '{$name}',
				icon: editor.config._TikiRoot + '{$icon}'
			});
		}
	});
}
JS
            ,
            10
        );
    }
}

class ToolbarSeparator extends Toolbar
{
    public function __construct() // {{{
    {
        $this->setWysiwygToken('-')
            ->setIcon('img/separator.gif')
            ->setType('Separator');
    } // }}}

    public function getWikiHtml($areaId) // {{{
    {
        return '|';
    } // }}}
}

class ToolbarSpacer extends Toolbar
{
    public function __construct() // {{{
    {
        $this->setWysiwygToken('|')
            ->setIcon('img/trans.png')
            ->setType('Spacer');
    } // }}}

    public function getWikiHtml($areaId) // {{{
    {
        return '||';
    } // }}}
}

class ToolbarCkOnly extends Toolbar
{
    public function __construct($token, $icon = '', $iconname = '') // {{{
    {
        if (empty($icon)) {
            $img_path = 'img/ckeditor/' . strtolower($token) . '.png';
            if (is_file($img_path)) {
                $icon = $img_path;
            } else {
                $icon = 'img/icons/shading.png';
            }
        }
        $this->setWysiwygToken($token)
            ->setIcon($icon)
            ->setIconName($iconname)
            ->setType('CkOnly');
    } // }}}

    public static function fromName($name, $is_html) // {{{
    {
        global $prefs;

        switch ($name) {
            case 'templates':
                if ($prefs['feature_wiki_templates'] === 'y') {
                    return new self('Templates');
                }

                    return null;
                
            case 'cut':
                return new self('Cut', null, 'scissors');
            case 'copy':
                return new self('Copy', null, 'copy');
            case 'paste':
                return new self('Paste', null, 'paste');
            case 'pastetext':
                return new self('PasteText', null, 'paste');
            case 'pasteword':
                return new self('PasteFromWord', null, 'paste');
            case 'print':
                return new self('Print', null, 'print');
            case 'spellcheck':
                return new self('SpellChecker', null, 'ok');
            case 'undo':
                return new self('Undo', null, 'undo');
            case 'redo':
                return new self('Redo', null, 'repeat');
            case 'selectall':
                return new self('SelectAll', null, 'selectall');
            case 'removeformat':
                return new self('RemoveFormat', null, 'erase');
            case 'showblocks':
                return new self('ShowBlocks', null, 'box');
            case 'left':
                return new self('JustifyLeft', null, 'align-left');
            case 'right':
                return new self('JustifyRight', null, 'align-right');
            case 'full':
                return new self('JustifyBlock', null, 'align-justify');
            case 'indent':
                return new self('Indent', null, 'indent');
            case 'outdent':
                return new self('Outdent', null, 'outdent');
            case 'style':
                return new self('Styles');
            case 'fontname':
                return new self('Font');
            case 'fontsize':
                return new self('FontSize');
            case 'format':
                return 	new self('Format');
            case 'source':
                global $tikilib, $user, $page;
                $p = $prefs['wysiwyg_htmltowiki'] == 'y' ? 'tiki_p_wiki_view_source' : 'tiki_p_use_HTML';
                if ($tikilib->user_has_perm_on_object($user, $page, 'wiki page', $p)) {
                    return new self('Source', null, 'code_file');
                }

                    return null;
                
            case 'autosave':
                return new self('autosave', 'img/ckeditor/ajaxSaveDirty.gif', 'floppy');
            case 'inlinesave':
                return new self('Inline save', 'img/ckeditor/ajaxSaveDirty.gif');
            case 'inlinecancel':
                return new self('Inline cancel', 'img/icons/cross.png');
            case 'sub':
                return new self('Subscript', null, 'subscript');
            case 'sup':
                return new self('Superscript', null, 'subscript');
            case 'anchor':
                return new self('Anchor', null, 'anchor');
            case 'bidiltr':
                return new self('BidiLtr', null, 'arrow-right');
            case 'bidirtl':
                return new self('BidiRtl', null, 'arrow-left');
            case 'image':
                return new self('Image', null, 'image');
            case 'table':
                return $is_html ? new self('Table') : null;
            case 'link':
                return $is_html ? new self('Link') : null;
            case 'unlink':
                return new self('Unlink', null, 'unlink');
        }
    } // }}}

    public function getWikiHtml($areaId) // {{{
    {
        return null;
    } // }}}

    public function getWysiwygToken($areaId)
    {
        if ($this->wysiwyg === 'Image') {	// cke's own image tool
            global $prefs;
            $headerlib = TikiLib::lib('header');
            // can't do upload the cke way yet
            $url = 'tiki-list_file_gallery.php?galleryId=' . $prefs['home_file_gallery'] . '&filegals_manager=fgal_picker';
            $headerlib->add_js('if (typeof window.CKEDITOR !== "undefined") {window.CKEDITOR.config.filebrowserBrowseUrl = "' . $url . '"}', 5);
        }

        return $this->wysiwyg;
    }

    public function getWysiwygWikiToken($areaId) // {{{ // wysiwyg_htmltowiki
    {
        switch ($this->wysiwyg) {
            case 'autosave':
            case 'Copy':
            case 'Cut':
            case 'Format':
            case 'JustifyLeft':
            case 'Paste':
            case 'PasteText':
            case 'PasteFromWord':
            case 'Redo':
            case 'RemoveFormat':
            case 'ShowBlocks':
            case 'Source':
            case 'Undo':
            case 'Unlink':
                return $this->wysiwyg;

                break;
            default:
                return null;
        }
    } // }}}

    public function getLabel() // {{{
    {
        return $this->wysiwyg;
    } // }}}

    public function getIconHtml() // {{{ for admin page
    {
        if (! empty($this->iconname)) {
            $smarty = TikiLib::lib('smarty');
            $smarty->loadPlugin('smarty_function_icon');

            return smarty_function_icon(['name' => $this->iconname, 'ititle' => ':'
                    . htmlentities($this->getLabel(), ENT_QUOTES, 'UTF-8'), 'iclass' => 'tips bottom'], $smarty->getEmptyInternalTemplate());
        }
        if ((! empty($this->icon) && $this->icon !== 'img/icons/shading.png') || in_array($this->label, ['Autosave'])) {
            return parent::getIconHtml();
        }

        global $prefs;
        $skin = $prefs['wysiwyg_toolbar_skin'];
        $headerlib = TikiLib::lib('header');
        $headerlib->add_cssfile('vendor_bundled/vendor/ckeditor/ckeditor/skins/' . $skin . '/editor.css');
        $cls = strtolower($this->wysiwyg);
        $headerlib->add_css(
            'span.cke_skin_' . $skin . ' {border: none;background: none;padding:0;margin:0;}' .
            '.toolbars-admin .row li.toolbar > span.cke_skin_' . $skin . ' {display: inline-block;}'
        );

        return '<span class="cke_skin_' . $skin
            . '"><a class="cke_button cke_ltr" style="margin-top:-5px"><span class="cke_button__'
            . htmlentities($cls, ENT_QUOTES, 'UTF-8') . '_icon"' .
            ' title="' . htmlentities($this->getLabel(), ENT_QUOTES, 'UTF-8') . '">' .
            '<span class="cke_icon"> </span>' .
            '</span></a></span>';
    } // }}}
}

class ToolbarInline extends Toolbar
{
    protected $syntax;

    public static function fromName($tagName) // {{{
    {
        switch ($tagName) {
            case 'bold':
                $label = tra('Bold');
                $icon = tra('img/icons/text_bold.png');
                $iconname = 'bold';
                $wysiwyg = 'Bold';
                $syntax = '__text__';

                break;
            case 'italic':
                $label = tra('Italic');
                $icon = tra('img/icons/text_italic.png');
                $iconname = 'italic';
                $wysiwyg = 'Italic';
                $syntax = "''text''";

                break;
            case 'underline':
                $label = tra('Underline');
                $icon = tra('img/icons/text_underline.png');
                $iconname = 'underline';
                $wysiwyg = 'Underline';
                $syntax = "===text===";

                break;
            case 'strike':
                $label = tra('Strikethrough');
                $icon = tra('img/icons/text_strikethrough.png');
                $iconname = 'strikethrough';
                $wysiwyg = 'Strike';
                $syntax = '--text--';

                break;
            case 'code':
                $label = tra('Code');
                $icon = tra('img/icons/page_white_code.png');
                $iconname = 'code';
                $wysiwyg = 'Code';
                $syntax = '-+text+-';

                break;
            case 'nonparsed':
                $label = tra('Non-parsed (wiki syntax does not apply)');
                $icon = tra('img/icons/noparse.png');
                $iconname = 'ban';
                $wysiwyg = null;
                $syntax = '~np~text~/np~';

                break;
            default:
                return;
        }

        $tag = new self;
        $tag->setLabel($label)
            ->setWysiwygToken($wysiwyg)
            ->setIconName(! empty($iconname) ? $iconname : 'help')
            ->setIcon(! empty($icon) ? $icon : 'img/icons/shading.png')
            ->setSyntax($syntax)
            ->setType('Inline');

        return $tag;
    } // }}}

    public function getSyntax($areaId) // {{{
    {
        return $this->syntax;
    } // }}}

    public function setSyntax($syntax) // {{{
    {
        $this->syntax = $syntax;

        return $this;
    } // }}}

    public function getWysiwygWikiToken($areaId) // {{{ // wysiwyg_htmltowiki
    {
        return $this->getWysiwygToken($areaId);
    } // }}}

    public function getWikiHtml($areaId) // {{{
    {
        if ($this->syntax == '~np~text~/np~') {	// closing ~/np~ tag breaks toolbar when inside nested plugins
            return $this->getSelfLink(
                'insertAt(\'' . $areaId . '\', \'~np~text~\'+\'/np~\');',
                htmlentities($this->label, ENT_QUOTES, 'UTF-8'),
                'qt-inline'
            );
        }

        return $this->getSelfLink(
            'insertAt(\'' . $areaId . '\', \'' . addslashes(htmlentities($this->syntax, ENT_COMPAT, 'UTF-8')) . '\');',
            htmlentities($this->label, ENT_QUOTES, 'UTF-8'),
            'qt-inline'
        );
    } // }}}
}

class ToolbarBlock extends ToolbarInline // Will change in the future
{
    protected $syntax;

    public static function fromName($tagName) // {{{
    {
        global $prefs;

        $label = null;
        $wysiwyg = null;
        $syntax = null;

        switch ($tagName) {
            case 'center':
                $label = tra('Align Center');
                $iconname = 'align-center';
                $wysiwyg = 'JustifyCenter';
                if ($prefs['feature_use_three_colon_centertag'] == 'y') {
                    $syntax = ":::text:::";
                } else {
                    $syntax = "::text::";
                }

                break;
            case 'rule':
                $label = tra('Horizontal Bar');
                $iconname = 'horizontal-rule';
                $wysiwyg = 'HorizontalRule';
                $syntax = '---';

                break;
            case 'pagebreak':
                $label = tra('Page Break');
                $iconname = 'page-break';
                $wysiwyg = 'PageBreak';
                $syntax = '...page...';

                break;
            case 'box':
                $label = tra('Box');
                $iconname = 'box';
                $wysiwyg = 'Box';
                $syntax = '^text^';

                break;
            case 'email':
                $label = tra('Email');
                $iconname = 'envelope';
                $wysiwyg = null;
                $syntax = '[mailto:email@example.com|text]';

                break;
            case 'h1':
            case 'h2':
            case 'h3':
                $label = tra('Heading') . ' ' . $tagName[1];
                $iconname = $tagName;
                $wysiwyg = null;
                $syntax = str_repeat('!', $tagName[1]) . ' text';

                break;
            case 'titlebar':
                $label = tra('Title bar');
                $iconname = 'title';
                $wysiwyg = null;
                $syntax = '-=text=-';

                break;
            case 'toc':
                $label = tra('Table of contents');
                $iconname = 'book';
                $wysiwyg = 'TOC';
                $syntax = '{maketoc}';

                break;
            default:
                return;
        }

        $tag = new self;
        $tag->setLabel($label)
            ->setWysiwygToken($wysiwyg)
            ->setIconName(! empty($iconname) ? $iconname : 'help')
            ->setSyntax($syntax)
            ->setType('Block');

        return $tag;
    } // }}}

    public function getWikiHtml($areaId) // {{{
    {
        if ($this->syntax == '...page...') {	// for some reason breaks toolbar when inside nested plugins
            return $this->getSelfLink(
                'insertAt(\'' . $areaId . '\', \'...\'+\'page\'+\'...\');',
                htmlentities($this->label, ENT_QUOTES, 'UTF-8'),
                'qt-block'
            );
        }

        return $this->getSelfLink(
            'insertAt(\'' . $areaId . '\', \'' . addslashes(htmlentities($this->syntax, ENT_COMPAT, 'UTF-8')) . '\', true);',
            htmlentities($this->label, ENT_QUOTES, 'UTF-8'),
            'qt-block'
        );
    } // }}}
}

class ToolbarLineBased extends ToolbarInline // Will change in the future
{
    protected $syntax;

    public static function fromName($tagName) // {{{
    {
        switch ($tagName) {
            case 'list':
                $label = tra('Bullet List');
                $iconname = 'list';
                $wysiwyg = 'BulletedList';
                $syntax = '* text';

                break;
            case 'numlist':
                $label = tra('Numbered List');
                $iconname = 'list-numbered';
                $wysiwyg = 'NumberedList';
                $syntax = '# text';

                break;
            case 'indent':
                global $prefs;

                return null;

            break;
            default:
                return null;
        }

        $tag = new self;
        $tag->setLabel($label)
            ->setWysiwygToken($wysiwyg)
            ->setIconName(! empty($iconname) ? $iconname : 'help')
            ->setSyntax($syntax)
            ->setType('LineBased');

        return $tag;
    } // }}}

    public function getWikiHtml($areaId) // {{{
    {
        return $this->getSelfLink(
            'insertAt(\'' . $areaId . '\', \'' . addslashes(htmlentities($this->syntax, ENT_COMPAT, 'UTF-8')) . '\', true, true);',
            htmlentities($this->label, ENT_QUOTES, 'UTF-8'),
            'qt-line'
        );
    } // }}}
}


class ToolbarPicker extends Toolbar
{
    private $list;
    private $name;

    public static function fromName($tagName) // {{{
    {
        global $section, $prefs;
        $headerlib = TikiLib::lib('header');

        $tool_prefs = [];
        $styleType = '';

        switch ($tagName) {
            case 'specialchar':
                $wysiwyg = 'SpecialChar';
                $label = tra('Special Characters');
                $iconname = 'keyboard';
                // Line taken from DokuWiki + some added chars for Tiki
                $list = explode(' ', 'À à Á á Â â Ã ã Ä ä Ǎ ǎ Ă ă Å å Ā ā Ą ą Æ æ Ć ć Ç ç Č č Ĉ ĉ Ċ ċ Ð đ ð Ď ď È è É é Ê ê Ë ë Ě ě Ē ē Ė ė Ę ę Ģ ģ Ĝ ĝ Ğ ğ Ġ ġ Ĥ ĥ Ì ì Í í Î î Ï ï Ǐ ǐ Ī ī İ ı Į į Ĵ ĵ Ķ ķ Ĺ ĺ Ļ ļ Ľ ľ Ł ł Ŀ ŀ Ń ń Ñ ñ Ņ ņ Ň ň Ò ò Ó ó Ô ô Õ õ Ö ö Ǒ ǒ Ō ō Ő ő Œ œ Ø ø Ŕ ŕ Ŗ ŗ Ř ř Ś ś Ş ş Š š Ŝ ŝ Ţ ţ Ť ť Ù ù Ú ú Û û Ü ü Ǔ ǔ Ŭ ŭ Ū ū Ů ů ǖ ǘ ǚ ǜ Ų ų Ű ű Ŵ ŵ Ý ý Ÿ ÿ Ŷ ŷ Ź ź Ž ž Ż ż Þ þ ß Ħ ħ ¿ ¡ ¢ £ ¤ ¥ € ¦ § ª ¬ ¯ ° ± ÷ ‰ ¼ ½ ¾ ¹ ² ³ µ ¶ † ‡ · • º ∀ ∂ ∃ Ə ə ∅ ∇ ∈ ∉ ∋ ∏ ∑ ‾ − ∗ √ ∝ ∞ ∠ ∧ ∨ ∩ ∪ ∫ ∴ ∼ ≅ ≈ ≠ ≡ ≤ ≥ ⊂ ⊃ ⊄ ⊆ ⊇ ⊕ ⊗ ⊥ ⋅ ◊ ℘ ℑ ℜ ℵ ♠ ♣ ♥ ♦ 𝛼 𝛽 𝛤 𝛾 𝛥 𝛿 𝜀 𝜁 𝛨 𝜂 𝛩 𝜃 𝜄 𝜅 𝛬 𝜆 𝜇 𝜈 𝛯 𝜉 𝛱 𝜋 𝛳 𝜍 𝛴 𝜎 𝜏 𝜐 𝛷 𝜑 𝜒 𝛹 𝜓 𝛺 𝜔 𝛻 𝜕 ★ ☆ ☎ ☚ ☛ ☜ ☝ ☞ ☟ ☹ ☺ ✔ ✘ × „ “ ” ‚ ‘ ’ « » ‹ › — – … ← ↑ → ↓ ↔ ⇐ ⇑ ⇒ ⇓ ⇔ © ™ ® ′ ″ @ % ~ | [ ] { } * #');
                $list = array_combine($list, $list);

                break;
            case 'smiley':
                $wysiwyg = 'Smiley';
                $label = tra('Smileys');
                $iconname = 'smile';
                $rawList = [ 'biggrin', 'confused', 'cool', 'cry', 'eek', 'evil', 'exclaim', 'frown', 'idea', 'lol', 'mad', 'mrgreen', 'neutral', 'question', 'razz', 'redface', 'rolleyes', 'sad', 'smile', 'surprised', 'twisted', 'wink', 'arrow', 'santa' ];
                $tool_prefs[] = 'feature_smileys';

                $list = [];
                foreach ($rawList as $smiley) {
                    $tra = htmlentities(tra($smiley), ENT_QUOTES, 'UTF-8');
                    $list["(:$smiley:)"] = '<img src="' . $headerlib->convert_cdn('img/smiles/icon_' . $smiley . '.gif') . '" alt="' . $tra . '" title="' . $tra . '" width="15" height="15" />';
                }

                break;
            case 'color':
                $wysiwyg = 'TextColor';
                $label = tra('Foreground color');
                $iconname = 'font-color';
                $rawList = [];
                $styleType = 'color';

                $hex = ['0', '3', '6', '8', '9', 'C', 'F'];
                $count_hex = count($hex);

                for ($r = 0; $r < $count_hex; $r += 2) { // red
                    for ($g = 0; $g < $count_hex; $g += 2) { // green
                        for ($b = 0; $b < $count_hex; $b += 2) { // blue
                            $color = $hex[$r] . $hex[$g] . $hex[$b];
                            $rawList[] = $color;
                        }
                    }
                }

                $list = [];
                foreach ($rawList as $color) {
                    $list["~~#$color:text~~"] = "<span style='background-color: #$color' title='#$color' />&nbsp;</span>";
                }

                if ($section == 'sheet') {
                    $list['reset'] = "<span title=':" . tra("Reset Colors") . "' class='toolbars-picker-reset' reset='true'>" . tra("Reset") . "</span>";
                }

                break;

            case 'bgcolor':
                $label = tra('Background Color');
                $iconname = 'background-color';
                $wysiwyg = 'BGColor';
                $styleType = 'background-color';
                $rawList = [];

                $hex = ['0', '3', '6', '8', '9', 'C', 'F'];
                $count_hex = count($hex);

                for ($r = 0; $r < $count_hex; $r += 2) { // red
                    for ($g = 0; $g < $count_hex; $g += 2) { // green
                        for ($b = 0; $b < $count_hex; $b += 2) { // blue
                            $color = $hex[$r] . $hex[$g] . $hex[$b];
                            $rawList[] = $color;
                        }
                    }
                }

                $list = [];
                foreach ($rawList as $color) {
                    $list["~~black,#$color:text~~"] = "<span style='background-color: #$color' title='#$color' />&nbsp;</span>";
                }
                if ($section == 'sheet') {
                    $list['reset'] = "<span title='" . tra("Reset Colors") . "' class='toolbars-picker-reset' reset='true'>" . tra("Reset") . "</span>";
                }

                break;

            default:
                return;
        }

        $tag = new self;
        $tag->setWysiwygToken($wysiwyg)
            ->setLabel($label)
            ->setIconName(! empty($iconname) ? $iconname : 'help')
            ->setList($list)
            ->setType('Picker')
            ->setName($tagName)
            ->setStyleType($styleType);

        foreach ($tool_prefs as $pref) {
            $tag->addRequiredPreference($pref);
        }

        global $toolbarPickerIndex;
        ++$toolbarPickerIndex;
        $tag->index = $toolbarPickerIndex;
        ToolbarPicker::setupJs();

        return $tag;
    } // }}}

    public function setName($name) // {{{
    {
        $this->name = $name;

        return $this;
    } // }}}


    public function getWysiwygWikiToken($areaId) // {{{ // wysiwyg_htmltowiki
    {
        switch ($this->wysiwyg) {
            case 'BGColor':
            case 'TextColor':
            case 'SpecialChar':
                return $this->wysiwyg;

                break;
            default:
                return null;
        }
    } // }}}


    public function setList($list) // {{{
    {
        $this->list = $list;

        return $this;
    } // }}}

    protected function setSyntax($syntax) // {{{
    {
        $this->syntax = $syntax;

        return $this;
    } // }}}

    public function getSyntax($areaId = '$areaId')
    {
        global $section;
        if ($section == 'sheet') {
            return 'displayPicker( this, \'' . $this->name . '\', \'' . $areaId . '\', true, \'' . $this->styleType . '\' )';	// is enclosed in double quotes later
        }

        return 'displayPicker( this, \'' . $this->name . '\', \'' . $areaId . '\' )';	// is enclosed in double quotes later
    }

    private static function setupJs()
    {
        static $pickerAdded = false;

        if (! $pickerAdded) {
            TikiLib::lib('header')->add_jsfile('lib/jquery_tiki/tiki-toolbars.js');
            $pickerAdded = true;
        }
    }

    public function getWikiHtml($areaId) // {{{
    {
        global $prefs;
        $headerlib = TikiLib::lib('header');
        $headerlib->add_js("if (! window.pickerData) { window.pickerData = {}; } window.pickerData['$this->name'] = " . str_replace('\/', '/', json_encode($this->list)) . ";");

        return $this->getSelfLink(
            $this->getSyntax($areaId),
            htmlentities($this->label, ENT_QUOTES, 'UTF-8'),
            'qt-picker'
        );
    } // }}}

    protected function setStyleType($type) // {{{
    {
        $this->styleType = $type;

        return $this;
    } // }}}
}

class ToolbarDialog extends Toolbar
{
    private $list;
    private $index;
    private $name;

    public static function fromName($tagName) // {{{
    {
        global $prefs;

        $tool_prefs = [];

        switch ($tagName) {
            case 'tikilink':
                $label = tra('Wiki Link');
                $iconname = 'link';
                $icon = tra('img/icons/page_link.png');
                $wysiwyg = '';	// cke link dialog now adapted for wiki links
                $list = [tra("Wiki Link"),
                        '<label for="tbWLinkDesc">' . tra("Show this text") . '</label>',
                        '<input type="text" id="tbWLinkDesc" class="ui-widget-content ui-corner-all" style="width: 98%" />',
                        '<label for="tbWLinkPage">' . tra("Link to this page") . '</label>',
                        '<input type="text" id="tbWLinkPage" class="ui-widget-content ui-corner-all" style="width: 98%" />',
                        $prefs['wikiplugin_alink'] == 'y' ? '<label for="tbWLinkAnchor">' . tra("Anchor") . ':</label>' : '',
                        $prefs['wikiplugin_alink'] == 'y' ? '<input type="text" id="tbWLinkAnchor" class="ui-widget-content ui-corner-all" style="width: 98%" />' : '',
                        $prefs['feature_semantic'] == 'y' ? '<label for="tbWLinkRel">' . tra("Semantic relation") . ':</label>' : '',
                        $prefs['feature_semantic'] == 'y' ? '<input type="text" id="tbWLinkRel" class="ui-widget-content ui-corner-all" style="width: 98%" />' : '',
                        '{"open": function () { dialogInternalLinkOpen(area_id); },
						"buttons": { "' . tra("Cancel") . '": function() { dialogSharedClose(area_id,this); },' .
                                    '"' . tra("Insert") . '": function() { dialogInternalLinkInsert(area_id,this); }}}'
                    ];

                break;
            case 'objectlink':
                $types = TikiLib::lib('unifiedsearch')->getSupportedTypes();
                $options = '';
                foreach ($types as $type => $title) {
                    $options .= '<option value="' . $type . '">' . $title . '</option>';
                }
                $label = tra('Object Link');
                $iconname = 'link-external-alt';
                $icon = tra('img/icons/page_link.png');
                $wysiwyg = 'Object Link';

                $smarty = TikiLib::lib('smarty');
                $smarty->loadPlugin('smarty_function_object_selector');
                $object_selector = smarty_function_object_selector([
                '_id' => 'tbOLinkObjectSelector',
                '_class' => 'ui-widget-content ui-corner-all',
    //              '_format' => '{title}',
                '_filter' => ['type' => ''],
                '_parent' => 'tbOLinkObjectType',
                '_parentkey' => 'type',
                ], $smarty->getEmptyInternalTemplate());

                $list = [tra('Object Link'),
                        '<label for="tbOLinkDesc">' . tra("Show this text") . '</label>',
                        '<input type="text" id="tbOLinkDesc" />',
                        '<label for="tbOLinkObjectType">' . tra("Types of object") . '</label>',
                        '<select id="tbOLinkObjectType" class="ui-widget-content ui-corner-all" style="width: 98%">' .
                            '<option value="*">' . tra('All') . '</option>' .
                            $options .
                        '</select>',
                        '<label for="tbOLinkObjectSelector">' . tra("Link to this object") . '</label>',
                        $object_selector,
    //                      '<input type="text" id="tbOLinkObjectSelector" class="ui-widget-content ui-corner-all" style="width: 98%" />',
                        '{"open": function () { dialogObjectLinkOpen(area_id); },
						"buttons": { "' . tra("Cancel") . '": function() { dialogSharedClose(area_id,this); },' .
                                    '"' . tra("Insert") . '": function() { dialogObjectLinkInsert(area_id,this); }}}'
                    ];

                break;
            case 'link':
                $wysiwyg = 'Link';
                $label = tra('External Link');
                $iconname = 'link-external';
                $icon = tra('img/icons/world_link.png');
                $list = [tra('External Link'),
                        '<label for="tbLinkDesc">' . tra("Show this text") . '</label>',
                        '<input type="text" id="tbLinkDesc" class="ui-widget-content ui-corner-all" style="width: 98%" />',
                        '<label for="tbLinkURL">' . tra("link to this URL") . '</label>',
                        '<input type="text" id="tbLinkURL" class="ui-widget-content ui-corner-all" style="width: 98%" />',
                        '<label for="tbLinkRel">' . tra("Relation") . ':</label>',
                        '<input type="text" id="tbLinkRel" class="ui-widget-content ui-corner-all" style="width: 98%" />',
                        $prefs['cachepages'] == 'y' ? '<br /><label for="tbLinkNoCache" style="display:inline;">' . tra("No cache") . ':</label>' : '',
                        $prefs['cachepages'] == 'y' ? '<input type="checkbox" id="tbLinkNoCache" class="ui-widget-content ui-corner-all" />' : '',
                        '{"width": 300, "open": function () { dialogExternalLinkOpen( area_id ) },
						"buttons": { "' . tra("Cancel") . '": function() { dialogSharedClose(area_id,this); },' .
                                    '"' . tra("Insert") . '": function() { dialogExternalLinkInsert(area_id,this) }}}'
                    ];

                break;

            case 'table':
            case 'tikitable':
                $iconname = 'table';
                $icon = tra('img/icons/table.png');
                $wysiwyg = 'Table';
                $label = tra('Table Builder');
                $list = [tra('Table Builder'),
                        '{"open": function () { dialogTableOpen(area_id,this); },
						"width": 320, "buttons": { "' . tra("Cancel") . '": function() { dialogSharedClose(area_id,this); },' .
                                                  '"' . tra("Insert") . '": function() { dialogTableInsert(area_id,this); }}}'
                    ];

                break;

            case 'find':
                $icon = tra('img/icons/find.png');
                $iconname = 'search';
                $wysiwyg = 'Find';
                $label = tra('Find Text');
                $list = [tra('Find Text'),
                        '<label>' . tra("Search") . ':</label>',
                        '<input type="text" id="tbFindSearch" class="ui-widget-content ui-corner-all" />',
                        '<label for="tbFindCase" style="display:inline;">' . tra("Case Insensitivity") . ':</label>',
                        '<input type="checkbox" id="tbFindCase" checked="checked" class="ui-widget-content ui-corner-all" />',
                        '<p class="description">' . tra("Note: Uses regular expressions") . '</p>',	// TODO add option to not
                        '{"open": function() { dialogFindOpen(area_id); },' .
                         '"buttons": { "' . tra("Close") . '": function() { dialogSharedClose(area_id,this); },' .
                                      '"' . tra("Find") . '": function() { dialogFindFind(area_id); }}}'
                    ];

                break;

            case 'replace':
                $icon = tra('img/icons/text_replace.png');
                $iconname = 'repeat';
                $wysiwyg = 'Replace';
                $label = tra('Text Replace');
                $tool_prefs[] = 'feature_wiki_replace';

                $list = [tra('Text Replace'),
                        '<label for="tbReplaceSearch">' . tra("Search") . ':</label>',
                        '<input type="text" id="tbReplaceSearch" class="ui-widget-content ui-corner-all" />',
                        '<label for="tbReplaceReplace">' . tra("Replace") . ':</label>',
                        '<input type="text" id="tbReplaceReplace" class="ui-widget-content ui-corner-all clearfix" />',
                        '<label for="tbReplaceCase" style="display:inline;">' . tra("Case Insensitivity") . ':</label>',
                        '<input type="checkbox" id="tbReplaceCase" checked="checked" class="ui-widget-content ui-corner-all" />',
                        '<br /><label for="tbReplaceAll" style="display:inline;">' . tra("Replace All") . ':</label>',
                        '<input type="checkbox" id="tbReplaceAll" checked="checked" class="ui-widget-content ui-corner-all" />',
                        '<p class="description">' . tra("Note: Uses regular expressions") . '</p>',	// TODO add option to not
                        '{"open": function() { dialogReplaceOpen(area_id); },' .
                         '"buttons": { "' . tra("Close") . '": function() { dialogSharedClose(area_id,this); },' .
                                      '"' . tra("Replace") . '": function() { dialogReplaceReplace(area_id); }}}'
                    ];

                break;

            default:
                return;
        }

        $tag = new self;
        $tag->name = $tagName;
        $tag->setWysiwygToken($wysiwyg)
            ->setLabel($label)
            ->setIconName(! empty($iconname) ? $iconname : 'help')
            ->setIcon(! empty($icon) ? $icon : 'img/icons/shading.png')
            ->setList($list)
            ->setType('Dialog');

        foreach ($tool_prefs as $pref) {
            $tag->addRequiredPreference($pref);
        }

        global $toolbarDialogIndex;
        ++$toolbarDialogIndex;
        $tag->index = $toolbarDialogIndex;

        ToolbarDialog::setupJs();

        return $tag;
    } // }}}

    public function setList($list) // {{{
    {
        $this->list = $list;

        return $this;
    } // }}}

    protected function setSyntax($syntax) // {{{
    {
        $this->syntax = $syntax;

        return $this;
    } // }}}

    public function getSyntax($areaId = '$areaId')
    {
        return 'displayDialog( this, ' . $this->index . ', \'' . $areaId . '\')';
    }

    public static function setupJs()
    {
        TikiLib::lib('header')->add_jsfile('lib/jquery_tiki/tiki-toolbars.js');
    }

    public function getWikiHtml($areaId) // {{{
    {
        $headerlib = TikiLib::lib('header');
        $headerlib->add_js("if (! window.dialogData) { window.dialogData = {}; } window.dialogData[$this->index] = "
            . json_encode($this->list) . ";", 1 + $this->index);

        return $this->getSelfLink(
            $this->getSyntax($areaId),
            htmlentities($this->label, ENT_QUOTES, 'UTF-8'),
            'qt-picker'
        );
    } // }}}

    public function getWysiwygToken($areaId) // {{{
    {
        if (! empty($this->wysiwyg)) {
            TikiLib::lib('header')->add_js(
                "window.dialogData[$this->index] = " . json_encode($this->list) . ";",
                1 + $this->index
            );
            $syntax = str_replace('\'' . $areaId . '\'', 'editor.name', $this->getSyntax($areaId));
            $this->setupCKEditorTool($syntax, $this->wysiwyg, $this->label, $this->icon);
        }

        return $this->wysiwyg;
    } // }}}

    public function getWysiwygWikiToken($areaId) // {{{ // wysiwyg_htmltowiki
    {
        switch ($this->name) {
            case 'tikilink':
                $this->wysiwyg = 'tikilink';

                break;
            case 'objectlink':
                $this->wysiwyg = 'objectlink';

                break;
            case 'table':
                $this->wysiwyg = 'tikitable';

                break;
            case 'link':
                $this->wysiwyg = 'externallink';

                break;
            default:
                return $this->wysiwyg;
        }

        return $this->getWysiwygToken($areaId);
    } // }}}
}

class ToolbarFullscreen extends Toolbar
{
    public function __construct() // {{{
    {
        $this->setLabel(tra('Full-screen edit'))
            ->setIconName('fullscreen')
            ->setWysiwygToken('Maximize')
            ->setType('Fullscreen');
    } // }}}

    public function getWikiHtml($areaId) // {{{
    {
        return $this->getSelfLink(
            'toggleFullScreen(\'' . $areaId . '\');return false;',
            htmlentities($this->label, ENT_QUOTES, 'UTF-8'),
            'qt-fullscreen'
        );
    } // }}}

    public function getWysiwygWikiToken($areaId) // {{{ // wysiwyg_htmltowiki
    {
        return $this->getWysiwygToken($areaId);
    } // }}}
}

class ToolbarHelptool extends Toolbar
{
    public function __construct() // {{{
    {
        $this->setLabel(tra('Wiki Help'))
            ->setIcon('img/icons/help.png')
            ->setType('Helptool');
    } // }}}

    public function getWikiHtml($areaId) // {{{
    {
        global $section;

        $smarty = TikiLib::lib('smarty');
        $servicelib = TikiLib::lib('service');

        $params = ['controller' => 'edit', 'action' => 'help', 'modal' => 1];
        $params['wiki'] = 1;
        $params['plugins'] = 1;
        $params['areaId'] = $areaId;

        if ($GLOBALS['section'] == 'sheet') {
            $params['sheet'] = 1;
        }

        $smarty->loadPlugin('smarty_function_icon');
        $icon = smarty_function_icon(['name' => 'help'], $smarty->getEmptyInternalTemplate());
        $url = $servicelib->getUrl($params);
        $help = tra('Help');

        return "<a title=\":$help\" class=\"toolbar btn btn-sm px-2 qt-help tips bottom\" href=\"$url\" data-toggle=\"modal\" data-target=\"#bootstrap-modal\">$icon</a>";
    } // }}}

    public function getWysiwygToken($areaId) // {{{
    {
        global $section;

        $servicelib = TikiLib::lib('service');

        $params = ['controller' => 'edit', 'action' => 'help', 'modal' => 1];
        $params['wysiwyg'] = 1;
        $params['plugins'] = 1;

        if ($section == 'sheet') {
            $params['sheet'] = 1;
        }

        // multiple ckeditors share the same toolbar commands, so area_id (editor.name) must be added when clicked
        $params['areaId'] = '';	// this must be last param

        $this->setLabel(tra('WYSIWYG Help'));
        $this->setIconName('help');
        $name = 'tikihelp';

        $js = '$.openModal({show: true, remote: "' . $servicelib->getUrl($params) . '" + editor.name});';

        $this->setupCKEditorTool($js, $name, $this->label, $this->icon);

        return $name;
    }

    public function getWysiwygWikiToken($areaId) // {{{ // wysiwyg_htmltowiki
    {
        return $this->getWysiwygToken($areaId);
    } // }}}
}

class ToolbarFileGallery extends Toolbar
{
    private $name;

    public function __construct() // {{{
    {
        $this->setLabel(tra('Choose or upload images'))
            ->setIconName('image')
            ->setIcon(tra('img/icons/pictures.png'))
            ->setWysiwygToken('tikiimage')
            ->setType('FileGallery')
            ->addRequiredPreference('feature_filegals_manager');
    } // }}}

    public function getSyntax($areaId)
    {
        global $prefs;
        $smarty = TikiLib::lib('smarty');
        if ($prefs['fgal_elfinder_feature'] !== 'y' || $prefs['fgal_elfinder_on_toolbar'] !== 'y') {
            $smarty->loadPlugin('smarty_function_filegal_manager_url');

            return 'openFgalsWindow(\'' . htmlentities(smarty_function_filegal_manager_url(['area_id' => $areaId], $smarty->getEmptyInternalTemplate())) . '\', true);';
        }
        TikiLib::lib('header')->add_jq_onready(
            'window.handleFinderInsertAt = function (file, elfinder, area_id) {
					$.getJSON($.service("file_finder", "finder"), { cmd: "tikiFileFromHash", hash: file.hash },
						function (data) {
							$(window).data("elFinderDialog").dialog("close");
							$($(window).data("elFinderDialog")).remove();
							$(window).data("elFinderDialog", null);
							window.insertAt(area_id, data.wiki_syntax);
							return false;
						}
					);
				};'
        );
        $smarty->loadPlugin('smarty_function_ticket');

        return '
			var area_id = (typeof editor === \'undefined\' ?  \'' . $areaId . '\' : editor.name);
			openElFinderDialog(
				this,
				{
					defaultGalleryId: ' . (empty($prefs['home_file_gallery']) ? $prefs['fgal_root_id'] : $prefs['home_file_gallery']) . ',
					deepGallerySearch: true,
					ticket: \'' . smarty_function_ticket(['mode' => 'get'], $smarty) . '\',
					getFileCallback: function(file,elfinder) {
							window.handleFinderInsertAt(file,elfinder,area_id);
						},
					eventOrigin:this,
					uploadCallback: function (data) {
							if (data.data.added.length === 1 && confirm(tr(\'Do you want to use this file in your page?\'))) {
								window.handleFinderInsertAt(data.data.added[0],window.elFinder,area_id);
							}
						}
				}
			);';
    }

    public function getWikiHtml($areaId) // {{{
    {
        return $this->getSelfLink($this->getSyntax($areaId), htmlentities($this->label, ENT_QUOTES, 'UTF-8'), 'qt-filegal');
    } // }}}

    public function getWysiwygToken($areaId) // {{{
    {
        if (! empty($this->wysiwyg)) {
            $this->name = $this->wysiwyg;	// temp
            $exec_js = str_replace('&amp;', '&', $this->getSyntax($areaId));	// odd?

            $this->setupCKEditorTool($exec_js, $this->name, $this->label, $this->icon);
        }

        return $this->wysiwyg;
    } // }}}

    public function getWysiwygWikiToken($areaId) // {{{ // wysiwyg_htmltowiki
    {
        return $this->getWysiwygToken($areaId);
    } // }}}

    public function isAccessible() // {{{
    {
        return parent::isAccessible();
    } // }}}
}

class ToolbarFileGalleryFile extends ToolbarFileGallery
{
    public function __construct() // {{{
    {
        $this->setLabel(tra('Choose or upload files'))
            ->setIconName('upload')
            ->setWysiwygToken('tikifile')
            ->setType('FileGallery')
            ->addRequiredPreference('feature_filegals_manager');
    } // }}}

    public function getSyntax($areaId)
    {
        $smarty = TikiLib::lib('smarty');
        $smarty->loadPlugin('smarty_function_filegal_manager_url');

        return 'openFgalsWindow(\'' . htmlentities(smarty_function_filegal_manager_url(['area_id' => $areaId], $smarty->getEmptyInternalTemplate()))
            . '&insertion_syntax=file\', true);';
    }
}

class ToolbarSwitchEditor extends Toolbar
{
    private $name;
    public function __construct() // {{{
    {
        $this->setLabel(tra('Switch Editor (wiki or WYSIWYG)'))
            ->setIconName('pencil')
            ->setIcon(tra('img/icons/pencil_go.png'))
            ->setWysiwygToken('tikiswitch')
            ->setType('SwitchEditor')
            ->addRequiredPreference('feature_wysiwyg');
    } // }}}

    public function getWikiHtml($areaId) // {{{
    {
        return $this->getSelfLink(
            'switchEditor(\'wysiwyg\', $(this).parents(\'form\')[0]);',
            htmlentities($this->label, ENT_QUOTES, 'UTF-8'),
            'qt-switcheditor'
        );
    } // }}}

    public function getWysiwygToken($areaId) // {{{
    {
        global $prefs;
        if (! empty($this->wysiwyg)) {
            $this->name = $this->wysiwyg;	// temp

            if ($prefs['feature_wysiwyg'] == 'y' && $prefs['wysiwyg_optional'] == 'y') {
                $js = "switchEditor('wiki', $('#$areaId').parents('form')[0]);";
                $this->setupCKEditorTool($js, $this->name, $this->label, $this->icon);
            }
        }

        return $this->wysiwyg;
    } // }}}


    public function getWysiwygWikiToken($areaId) // {{{ // wysiwyg_htmltowiki
    {
        return $this->getWysiwygToken($areaId);
    } // }}}


    public function isAccessible() // {{{
    {
        global $tiki_p_edit_switch_mode;

        return parent::isAccessible() &&
                ! isset($_REQUEST['hdr']) &&		// or in section edit
                $tiki_p_edit_switch_mode === 'y';	// or no perm (new in 7.1)
    } // }}}

/*	function getLabel() // {{{
    {
        return $this->label;
    } // }}}
*/
}


class ToolbarAdmin extends Toolbar
{
    private $name;
    public function __construct() // {{{
    {
        $this->setLabel(tra('Admin Toolbars'))
            ->setIconName('wrench')
            ->setIcon(tra('img/icons/wrench.png'))
            ->setWysiwygToken('admintoolbar')
            ->setType('admintoolbar');
        //->addRequiredPreference('feature_wysiwyg');
    } // }}}

    public function getWikiHtml($areaId) // {{{
    {
        return $this->getSelfLink(
            'admintoolbar();',
            htmlentities($this->label, ENT_QUOTES, 'UTF-8'),
            'qt-admintoolbar'
        );
    } // }}}

    public function getWysiwygToken($areaId) // {{{
    {
        global $prefs;
        if (! empty($this->wysiwyg)) {
            $this->name = $this->wysiwyg;	// temp

            if ($prefs['feature_wysiwyg'] == 'y') {
                $js = "admintoolbar();";
                $this->setupCKEditorTool($js, $this->name, $this->label, $this->icon);
            }
        }

        return $this->wysiwyg;
    } // }}}


    public function getWysiwygWikiToken($areaId) // {{{ // wysiwyg_htmltowiki
    {
        return $this->getWysiwygToken($areaId);
    } // }}}
}

class ToolbarWikiplugin extends Toolbar
{
    private $pluginName;

    public static function fromName($name) // {{{
    {
        global $tikilib;
        $parserlib = TikiLib::lib('parser');

        if (substr($name, 0, 11) == 'wikiplugin_') {
            $name = substr($name, 11);
            if ($info = $parserlib->plugin_info($name)) {
                $tag = new self;
                $tag->setLabel(str_ireplace('wikiplugin_', '', $info['name']))
                    ->setWysiwygToken(str_replace(' ', '_', $info['name']))
                    ->setPluginName($name)
                    ->setType('Wikiplugin');

                if (! empty($info['iconname'])) {
                    $tag->setIconName($info['iconname']);
                } elseif (! empty($info['icon'])) {
                    $tag->setIcon($info['icon']);
                } else {
                    $tag->setIcon('img/icons/plugin.png');
                }

                TikiLib::lib('header')->add_jsfile('lib/jquery_tiki/tiki-toolbars.js');

                return $tag;
            }
        }
    } // }}}

    public function setPluginName($name) // {{{
    {
        $this->pluginName = $name;

        return $this;
    } // }}}

    public function getPluginName() // {{{
    {
        return $this->pluginName;
    } // }}}

    public function isAccessible() // {{{
    {
        global $tikilib;
        $parserlib = TikiLib::lib('parser');
        $dummy_output = '';

        return parent::isAccessible() && $parserlib->plugin_enabled($this->pluginName, $dummy_output);
    } // }}}

    private static function getToken($name) // {{{
    {
        switch ($name) {
            case 'flash':
                return 'Flash';
        }
    } // }}}

    public function getWikiHtml($areaId) // {{{
    {
        return $this->getSelfLink(
            'popupPluginForm(\'' . $areaId . '\',\'' . $this->pluginName . '\')',
            htmlentities($this->label, ENT_QUOTES, 'UTF-8'),
            'qt-plugin'
        );
    } // }}}
    public function getWysiwygToken($areaId, $add_js = true) // {{{
    {
        if (! empty($this->wysiwyg) && $add_js) {
            $js = "popupPluginForm(editor.name,'{$this->pluginName}');";
            //CKEditor needs image icons so get legacy plugin icons for the toolbar
            if (! $this->icon && ! empty($this->iconname)) {
                $iconsetlib = TikiLib::lib('iconset');
                $legacy = $iconsetlib->loadFile('themes/base_files/iconsets/legacy.php');
                if (array_key_exists($this->iconname, $legacy['icons'])) {
                    $iconinfo = $legacy['icons'][$this->iconname];
                } elseif (in_array($this->iconname, $legacy['defaults'])) {
                    $iconinfo['id'] = $this->iconname;
                }
                if (isset($iconinfo)) {
                    $prepend = isset($iconinfo['prepend']) ? $iconinfo['prepend'] : 'img/icons/';
                    $append = isset($iconinfo['append']) ? $iconinfo['append'] : '.png';
                    $iconpath = $prepend . $iconinfo['id'] . $append;
                } else {
                    $iconpath = 'img/icons/plugin.png';
                }
            }
            $this->setupCKEditorTool($js, $this->wysiwyg, $this->label, $iconpath);
        }

        return $this->wysiwyg;
    } // }}}

    public function getWysiwygWikiToken($areaId, $add_js = true) // {{{ // wysiwyg_htmltowiki
    {
        switch ($this->pluginName) {
            case 'img':
                $this->wysiwyg = 'wikiplugin_img';	// don't use ckeditor's html image dialog

                break;
            default:
        }

        return $this->getWysiwygToken($areaId, $add_js);
    } // }}}
}

class ToolbarSheet extends Toolbar
{
    protected $syntax;

    public static function fromName($tagName) // {{{
    {
        switch ($tagName) {
            case 'sheetsave':
                $label = tra('Save Sheet');
                $iconname = 'floppy';
                $syntax = '
					$("#saveState").hide();
					$.sheet.saveSheet($.sheet.tikiSheet, function() {
						$.sheet.manageState($.sheet.tikiSheet, true);
					});';

                break;
            case 'addrow':
                $label = tra('Add row after selection or to the end if no selection');
                $icon = tra('img/icons/sheet_row_add.png');
                $syntax = 'sheetInstance.controlFactory.addRow();';	// add row after end to workaround bug in jquery.sheet.js 1.0.2

                break;														// TODO fix properly for 5.1
            case 'addrowmulti':
                $label = tra('Add multiple rows after selection or to the end if no selection');
                $icon = tra('img/icons/sheet_row_add_multi.png');
                $syntax = 'sheetInstance.controlFactory.addRowMulti();';

                break;
            case 'addrowbefore':
                $label = tra('Add row before selection or to end if no selection');
                $icon = tra('img/icons/sheet_row_add.png');
                $syntax = 'sheetInstance.controlFactory.addRow(null, true);';	// add row after end to workaround bug in jquery.sheet.js 1.0.2

                break;
            case 'deleterow':
                $label = tra('Delete selected row');
                $icon = tra('img/icons/sheet_row_delete.png');
                $syntax = 'sheetInstance.deleteRow();';

                break;
            case 'addcolumn':
                $label = tra('Add column after selection or to the end if no selection');
                $icon = tra('img/icons/sheet_col_add.png');
                $syntax = 'sheetInstance.controlFactory.addColumn();';	// add col before current or at end if none selected

                break;
            case 'deletecolumn':
                $label = tra('Delete selected column');
                $icon = tra('img/icons/sheet_col_delete.png');
                $syntax = 'sheetInstance.deleteColumn();';

                break;
            case 'addcolumnmulti':
                $label = tra('Add multiple columns after selection or to the end if no selection');
                $icon = tra('img/icons/sheet_col_add_multi.png');
                $syntax = 'sheetInstance.controlFactory.addColumnMulti();';

                break;
            case 'addcolumnbefore':
                $label = tra('Add column before selection or to the end if no selection');
                $icon = tra('img/icons/sheet_col_add.png');
                $syntax = 'sheetInstance.controlFactory.addColumn(null, true);';	// add col before current or at end if none selected

                break;
            case 'sheetgetrange':
                $label = tra('Get Cell Range');
                $icon = tra('img/icons/sheet_get_range.png');
                $syntax = 'sheetInstance.getTdRange(null, sheetInstance.obj.formula().val()); return false;';

                break;
            case 'sheetfind':
                $label = tra('Find');
                $iconname = 'search';
                $syntax = 'sheetInstance.cellFind();';

                break;
            case 'sheetrefresh':
                $label = tra('Refresh calculations');
                $iconname = 'refresh';
                $syntax = 'sheetInstance.calc();';

                break;
            case 'sheetclose':
                $label = tra('Finish editing');
                $iconname = 'delete';
                $syntax = '$.sheet.manageState(sheetInstance.obj.parent(), true);';	// temporary workaround TODO properly

                break;
            case 'bold':
                $label = tra('Bold');
                $iconname = 'bold';
                $wysiwyg = 'Bold';
                $syntax = 'sheetInstance.cellStyleToggle("styleBold");';

                break;
            case 'italic':
                $label = tra('Italic');
                $iconname = 'italic';
                $wysiwyg = 'Italic';
                $syntax = 'sheetInstance.cellStyleToggle("styleItalics");';

                break;
            case 'underline':
                $label = tra('Underline');
                $iconname = 'underline';
                $wysiwyg = 'Underline';
                $syntax = 'sheetInstance.cellStyleToggle("styleUnderline");';

                break;
            case 'strike':
                $label = tra('Strikethrough');
                $iconname = 'strikethrough';
                $wysiwyg = 'Strike';
                $syntax = 'sheetInstance.cellStyleToggle("styleLineThrough");';

                break;
            case 'center':
                $label = tra('Align Center');
                $iconname = 'align-center';
                $syntax = 'sheetInstance.cellStyleToggle("styleCenter");';

                break;
            default:
                return;
        }

        $tag = new self;
        $tag->setLabel($label)
            ->setSyntax($syntax)
            ->setType('Sheet');
        if (! empty($iconname)) {
            $tag->setIconName(! empty($iconname) ? $iconname : 'help');
        }
        if (! empty($icon)) {
            $tag->setIcon(! empty($icon) ? $icon : 'img/icons/shading.png');
        }

        return $tag;
    } // }}}

    public function getSyntax($areaId) // {{{
    {
        return $this->syntax;
    } // }}}

    protected function setSyntax($syntax) // {{{
    {
        $this->syntax = $syntax;

        return $this;
    } // }}}

    public function getWikiHtml($areaId) // {{{
    {
        return $this->getSelfLink(
            addslashes(htmlentities($this->syntax, ENT_COMPAT, 'UTF-8')),
            htmlentities($this->label, ENT_QUOTES, 'UTF-8'),
            'qt-sheet'
        );
    } // }}}
}



class ToolbarsList
{
    private $lines = [];
    private $wysiwyg = false;
    private $is_html = false;

    private function __construct()
    {
    }

    /***
     * @param array $params            params from smarty_function_toolbars
     * @param array $tags_to_hide      list of tools not to show
     * @return ToolbarsList
     */
    public static function fromPreference($params, $tags_to_hide = []) // {{{
    {
        global $tikilib;

        $global = $tikilib->get_preference('toolbar_global' . ($params['comments'] === 'y' ? '_comments' : ''));
        $local = $tikilib->get_preference('toolbar_' . $params['section'] . ($params['comments'] === 'y' ? '_comments' : ''), $global);

        foreach ($tags_to_hide as $name) {
            $local = str_replace($name, '', $local);
        }
        if ($params['section'] === 'wysiwyg_plugin') {	// quick fix to prevent nested wysiwyg plugins (messy)
            $local = str_replace('wikiplugin_wysiwyg', '', $local);
        }

        $local = str_replace([',,', '|,', ',|', ',/', '/,'], [',', '|', '|', '/', '/'], $local);

        return self::fromPreferenceString($local, $params);
    } // }}}

    public static function fromPreferenceString($string, $params) // {{{
    {
        global $toolbarPickerIndex;
        $toolbarPickerIndex = -1;
        $list = new self;
        $list->wysiwyg = (isset($params['_wysiwyg']) && $params['_wysiwyg'] === 'y');
        $list->is_html = ! empty($params['_is_html']);

        $string = preg_replace('/\s+/', '', $string);

        foreach (explode('/', $string) as $line) {
            $bits = explode('|', $line);
            if (count($bits) > 1) {
                $list->addLine(explode(',', $bits[0]), explode(',', $bits[1]));
            } else {
                $list->addLine(explode(',', $bits[0]));
            }
        }

        return $list;
    } // }}}

    public function addTag($name, $unique = false)
    {
        if ($unique && $this->contains($name)) {
            return false;
        }
        $this->lines[count($this->lines) - 1][0][0][] = Toolbar::getTag($name);

        return true;
    }

    public function insertTag($name, $unique = false)
    {
        if ($unique && $this->contains($name)) {
            return false;
        }
        array_unshift($this->lines[0][0][0], Toolbar::getTag($name));

        return true;
    }

    private function addLine(array $tags, array $rtags = []) // {{{
    {
        $elements = [];
        $j = count($rtags) > 0 ? 2 : 1;

        for ($i = 0; $i < $j; $i++) {
            $group = [];
            $elements[$i] = [];

            if ($i == 0) {
                $thetags = $tags;
            } else {
                $thetags = $rtags;
            }
            foreach ($thetags as $tagName) {
                if ($tagName === '-' || $tagName === '|') {
                    if (count($group)) {
                        $elements[$i][] = $group;
                        $group = [];
                    }
                } else {
                    if (($tag = Toolbar::getTag($tagName, $this->wysiwyg, $this->is_html))
                        && $tag->isAccessible()) {
                        $group[] = $tag;
                    }
                }
            }

            if (count($group)) {
                $elements[$i][] = $group;
            }
        }
        if (count($elements)) {
            $this->lines[] = $elements;
        }
    } // }}}

    public function getWysiwygArray($areaId, $isHtml = true) // {{{
    {
        $lines = [];
        foreach ($this->lines as $line) {
            $lineOut = [];

            foreach ($line as $bit) {
                foreach ($bit as $group) {
                    $group_count = 0;
                    foreach ($group as $tag) {
                        if ($isHtml) {
                            if ($token = $tag->getWysiwygToken($areaId)) {
                                $lineOut[] = $token;
                                $group_count++;
                            }
                        } else {
                            if ($token = $tag->getWysiwygWikiToken($areaId)) {
                                $lineOut[] = $token;
                                $group_count++;
                            }
                        }
                    }
                    if ($group_count) { // don't add separators for empty groups
                        $lineOut[] = '-';
                    }
                }
            }

            $lineOut = array_slice($lineOut, 0, -1);

            if (count($lineOut)) {
                $lines[] = [$lineOut];
            }
        }

        return $lines;
    } // }}}

    public function getWikiHtml($areaId, $comments = '') // {{{
    {
        global $tiki_p_admin, $tiki_p_admin_toolbars, $section, $prefs;
        $headerlib = TikiLib::lib('header');
        $smarty = TikiLib::lib('smarty');
        $html = '';

        $c = 0;
        foreach ($this->lines as $line) {
            $lineHtml = '';
            $right = '';
            if (count($line) == 1) {
                $line[1] = [];
            }

            // $line[0] is left part, $line[1] right floated section
            for ($bitx = 0, $bitxcount_line = count($line); $bitx < $bitxcount_line; $bitx++) {
                $lineBit = '';

                /*if ($c == 0 && $bitx == 1 && ($tiki_p_admin == 'y' or $tiki_p_admin_toolbars == 'y')) {
                    $params = array('_script' => 'tiki-admin_toolbars.php', '_onclick' => 'needToConfirm = true;', '_class' => 'toolbar', '_icon' => 'wrench', '_ajax' => 'n');
                    if (isset($comments) && $comments == 'y')
                        $params['comments'] = 'on';
                    if (isset($section))
                        $params['section'] = $section;
                    $content = tra('Admin Toolbars');
                    $right .= smarty_block_self_link($params, $content, $smarty);
                }*/

                foreach ($line[$bitx] as $group) {
                    $groupHtml = '';
                    foreach ($group as $tag) {
                        $groupHtml .= $tag->getWikiHtml($areaId);
                    }

                    if (! empty($groupHtml)) {
                        $param = ' class="toolbar-list"';
                        $lineBit .= "<span$param>$groupHtml</span>";
                    }
                    if ($bitx == 1) {
                        if (! empty($right)) {
                            $right = '<span class="toolbar-list">' . $right . '</span>';
                        }
                        $lineHtml = "<div class='helptool-admin float-right'>$lineBit $right</div>" . $lineHtml;
                    } else {
                        $lineHtml = $lineBit;
                    }
                }

                // adding admin icon if no right part - messy - TODO better
                if ($c == 0 && empty($lineBit) && ! empty($right)) {
                    $lineHtml .= "<div class='helptool-admin float-right'>$right</div>";
                }
            }
            if (! empty($lineHtml)) {
                $html .= "<div>$lineHtml</div>";
            }
            $c++;
        }

        return $html;
    } // }}}

    public function contains($name)
    {
        // {{{
        foreach ($this->lines as $line) {
            foreach ($line as $group) {
                foreach ($group as $tags) {
                    foreach ($tags as $tag) {
                        if ($tag->getLabel() == $name) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    } // }}}
}


/**
 * Definition of the CKE Toolbar Combos
 */
class ToolbarCombos
{

    /**
     * Get the content of the format combo
     *
     * Valid toolbar types are:
     * - 'html': WYSIWYG-HTML
     * - 'wiki': Visual Wiki
     *
     * @param string $tb_type The CKE toolbar type
     */
    public static function getFormatTags($tb_type)
    {
        switch ($tb_type) {
            case 'wiki':
                return 'p;h1;h2;h3;h4;h5;h6';

                break;
            case 'html':
            default:
                return 'p;h1;h2;h3;h4;h5;h6;pre;address;div'; // CKE default
        }
    }
}
