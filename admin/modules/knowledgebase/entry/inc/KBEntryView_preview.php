<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KPublisher - web based knowledgebase publishing tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+


class KBEntryView_preview extends AppView
{
    
    var $template = 'preview.html';
    var $embedded = false;
    
    
    function execute(&$obj, &$manager) {
        
        $this->template_dir = APP_MODULE_DIR . 'knowledgebase/entry/template/';
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        list($manager, $controller) = $this->getClientComponents();
        
        if (!$this->embedded) {
            $tpl->tplAssign('codesnippet_files', DocumentParser::parseCode2GetFiles($controller));
            $tpl->tplSetNeeded('/css_js');
            $tpl->tplSetNeeded('/close');
        }
        
        
        // live preview
        if($this->controller->getMoreParam('popup') == 1) {

            $tpl->tplSetNeeded('/ajax_preview');
            $tpl->tplAssign('article_display', 'none');
            
            //xajax
            $ajax = &$this->getAjax($obj, $manager);
            $xajax = &$ajax->getAjax();
            
            $more = array('id'=>$obj->get('id'), 'popup' => 1);
            $link = $this->controller->getAjaxLink('this', 'this', 'this', 'preview', $more);
            $xajax->setRequestURI($link);
            
            $xajax->registerFunction(array('parseBody', $this, 'ajaxParseBody'));
            
            
        // saved or autosaved
        } elseif($this->controller->getMoreParam('id') || $this->controller->getMoreParam('dkey')) { 
            
            $tpl->tplAssign('article_display', 'block');
            $tpl->tplAssign('title', $obj->get('title'));
            $tpl->tplAssign('body', $this->parseBody($manager, $controller, $obj->get('body'), $obj->getCustom(), $obj->get('id'), true));
            
            // detail button
            $detail_button = ($this->controller->getMoreParam('detail_btn'));
            if($detail_button) {
                $tpl->tplSetNeeded('/detail');
                
                $more = array('id' => $obj->get('id'));
                $link = $this->getLink('this', 'this', false, 'detail', $more);
                $tpl->tplAssign('detail_link', $link);
            }
        }

        
        $client_path = $this->conf['client_path'];
        if($this->conf['ssl_admin']) {
            $client_path = str_replace('http://', 'https://', $client_path);
        }
        $tpl->tplAssign('kb_path', $client_path);
        
        $tpl->tplAssign($this->msg);
                
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }

    
    
    function getClientComponents() {
        $setting = KBClientModel::getSettings(array(2, 100));
        $setting['auth_check_ip'] = false;
        $setting['view_style'] = 'default';
        $setting['view_format'] = 'default';

        $controller = new KBClientController();
        $controller->setDirVars($setting);
        $controller->setModRewrite(false); 
        
        $manager = &KBClientLoader::getManager($setting, $controller);
        
        return array($manager, $controller);
    }
    
    
    function ajaxParseBody($body, $custom, $first_call) {
        
        $objResponse = new xajaxResponse();
        
        list($manager, $controller) = $this->getClientComponents();      
        
        $highlighter_needed = DocumentParser::isCode($body);
        
        if($first_call && $highlighter_needed) {
            $langs = DocumentParser::getLangList($body);
    
            $script = array();
            $brushes = DocumentParser::getBrushList();
            $script_str = '$.getScript("%sjscript/syntax_highlighter/scripts/shBrush%s.js")';
            
            $path = $controller->client_path;
            $reg = &Registry::instance();
            $conf = &$reg->getEntry('conf');
            if($conf['ssl_admin']) {
                $path = str_replace('http://', 'https://', $path);
            }
            
            foreach ($langs as $lang) {
                $brush_name = (isset($brushes[$lang])) ? $brushes[$lang] : 'Plain';
                $script[] = sprintf($script_str, $path, $brush_name);
            }
            
            $js_str = '$.when(
                    %s,
                    $.Deferred(function(deferred) {
                        $(deferred.resolve);
                    })
                ).done(function() {
                    parseBody(0);
                });';
                
            
            $objResponse->script(sprintf($js_str, implode(',', $script)));
                
            return $objResponse;
        }
        
        if($is_toc = DocumentParser::isToc($body, RequestDataUtil::getIndexText($body), $manager)) {
            $file = AppMsg::getModuleMsgFile('public', 'client_msg.ini');    
            $msg = AppMsg::parseMsgs($file, false, false);
            $options = array(
                'preview' => true,
                'title' => $msg['contents_msg']
            );
            $body = DocumentParser::getToc($options) . $body;
        }
        
        $body = $this->parseBody($manager, $controller, $body, $custom);
        
        // $objResponse->alert(print_r($custom, 1));
        $objResponse->assign('article_body', 'innerHTML' , $body);
        $objResponse->assign('article', 'style.display' , 'block');
        
        
        if ($highlighter_needed) {
            $path = sprintf('%sjscript/syntax_highlighter', $controller->client_path);
            $clipboardSwf = sprintf('%s/scripts/clipboard.swf', $path);
                
            $js_str = 'SyntaxHighlighter.config.clipboardSwf = "%s";
                SyntaxHighlighter.config.stripBrs = true;
                SyntaxHighlighter.highlight();';
                    
            $objResponse->script(sprintf($js_str, $clipboardSwf));
        }
        
        $objResponse->call('hljs.initHighlighting');
        $objResponse->call('initUserTooltip');
        
        if($is_toc) {
            $options = array(
                'preview' => true,
                'js' => true,
                'data_toc' => 'article_body',
                'data_toc_headings' => $manager->setting['toc_tags']
            );
            
            $this->addMsg('client_msg.ini', 'public');
            
            $toc_js = DocumentParser::getToc($options);
            $objResponse->script($toc_js);
        }
        
        return $objResponse;    
    }


    function parseBody($manager, $controller, $body, $custom, $id = false, $highlighter_files = false) {

         $reg = &Registry::instance();
         $reg->setEntry('controller', $controller);
         $view = new KBClientView();

        // parseLink
        if(DocumentParser::isLink($body)) {
            DocumentParser::parseLink($body, array($view, 'getLink'), $manager,
                                        'all', $id, $controller);
        }

        if(DocumentParser::isTemplate($body)) {
            DocumentParser::parseTemplate($body, array($manager, 'getTemplate'));
        }
                
        if(DocumentParser::isCode($body)) {   
            DocumentParser::parseCode($body, $manager, $controller, $highlighter_files);    
        }
        
        if(DocumentParser::isCode2($body)) {
            DocumentParser::parseCode2($body, $controller);    
        }
        
        DocumentParser::parseCurlyBraces($body);
        
        if($custom) {
            
            $data = array();
            foreach($custom as $field_id => $v) {
                $d = (is_array($v)) ? implode(',', $v) : $v;
                if($d != '' && $d != 'null') {
                    $data[$field_id] = $d;
                }
            }
            
            if($data) {
                $ids = implode(',', array_keys($data));

                $cf_manager = new CommonCustomFieldModel();
                $fields = $cf_manager->getCustomFieldByIds($ids);    
                foreach(array_keys($fields) as $field_id) {
                    $fields[$field_id]['data'] = $data[$field_id];
                }
            
                $custom_data = $view->getCustomData($fields);
                $custom_tmpl_top = $view->parseCustomData($custom_data[1], 1);
                $custom_tmpl_bottom = $view->parseCustomData($custom_data[2], 2);
                $body = $custom_tmpl_top . $body . $custom_tmpl_bottom;            
            }            
        }
        
        return $body;
    }
    
}
?>
