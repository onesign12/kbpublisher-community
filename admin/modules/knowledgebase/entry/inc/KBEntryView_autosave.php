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


class KBEntryView_autosave extends AppView 
{
    
    var $template = 'form_autosave.html';
    
    var $autosave_skipped = false;
 

    function execute(&$obj, &$manager) {

        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');
        
        if ($this->autosave_skipped) {
            $js_str = '<script>
                $(document).ready(function() {
                    window.top.location.reload();
                });
            </script>';
            
            $params = array('entry', false, $obj->get('id'), false, array('em' => 1));
            $link = $this->controller->getClientLink($params);
                
            return sprintf($js_str, $link);
        }
        
                
        $template_dir = APP_MODULE_DIR . 'knowledgebase/entry/template/';
        $tpl = new tplTemplatez($template_dir . $this->template);
          
        $vars['autosave_ignore_msg'] = $this->msg['autosave_ignore_msg'];
        $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
        $msgs = AppMsg::parseMsgsMultiIni($file);
        $msg['title'] = $msgs['title_entry_autosave'];
        $msg['body'] = $msgs['note_entry_autosave'];
        $tpl->tplAssign('msg', BoxMsg::factory('hint', $msg, $vars));

        
        $data = $manager->getAutosavedData($obj->get('id'));
        
        $tpl->tplAssign('autosave_date_period',  $this->getTimeInterval($data['date_saved'], false));
        $tpl->tplAssign('autosave_date_formatted', $this->getFormatedDate($data['date_saved'], 'datetime'));
        
        $tpl->tplAssign('article_date_period',  $this->getTimeInterval($obj->get('date_updated'), false)); 
        $tpl->tplAssign('article_date_formatted', $this->getFormatedDate($obj->get('date_updated'), 'datetime'));        
        
        $vars = $this->setCommonFormVars($obj);
        
        // if referer
        if(isset($_GET['referer'])) {
            $link = array('entry', false, $obj->get('id'));
            $vars_ = $this->setRefererFormVars($_GET['referer'], $link);            
            $vars['cancel_link'] = $vars_['cancel_link'];
        }
        
        $tpl->tplAssign($vars);
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $more = array('id' => $obj->get('id'), 'dkey' => $data['id_key']);
        if(isset($_GET['referer'])) {
            $more['referer'] = $_GET['referer'];
        }
        
        $link = $this->controller->getLink('this', 'this', false, 'update', $more);
        $open_link = sprintf("location.href='%s';", $link);
        $cancel_link = sprintf("location.href='%s';", $vars['cancel_link']);
        
        if($this->controller->getMoreParam('popup')) {
            $open_link = 'window.top.' . $open_link;
            
            $params = array('entry', false, $obj->get('id'));
            $cancel_link = sprintf("window.top.location.href='%s';", $this->controller->getClientLink($params));
        }
        
        $tpl->tplAssign('open_link', $open_link);
        $tpl->tplAssign('cancel_link', $cancel_link);
  
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>