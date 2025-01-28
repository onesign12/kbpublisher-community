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


class KBEntryView_lock extends AppView 
{
    
    var $template = 'form_lock.html';
    
    var $entry_released = false;
    

    function execute(&$obj, &$manager) {
        
        if ($this->entry_released) {
            $js_str = '<script>
                $(document).ready(function() {
                    window.top.location.reload();
                });
            </script>';
            
            $params = array('entry', false, $obj->get('id'), false, array('em' => 1));
            $link = $this->controller->getClientLink($params);
                
            return sprintf($js_str, $link);
        }
        
        $this->template_dir = APP_MODULE_DIR . 'knowledgebase/entry/template/';
        $tpl = new tplTemplatez($this->template_dir . $this->template);
          
        // afterActionBox($keyword, $factory = 'error', $module = false, $vars = array()) {
        $vars['lock_ignore_msg'] = $this->msg['lock_ignore_msg'];
        $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
        $msgs = AppMsg::parseMsgsMultiIni($file);
        $msg['title'] = $msgs['title_entry_locked'];
        $msg['body'] = $msgs['note_entry_locked'];
        $tpl->tplAssign('msg', BoxMsg::factory('error', $msg, $vars));

        
        $locked = $manager->getEntryLockedData($obj->get('id'));
        $tpl->tplAssign('date_period',  $this->getTimeInterval($locked['date_locked'], false));
        
        if($user = $manager->getUser($locked['user_id'])) {
            $tpl->tplAssign('user_name', $user['first_name'] . ' ' . $user['last_name']);
            $tpl->tplAssign($user);            
        }        
        
        $tpl->tplSetNeeded('/ignore');
        
        
        // set more params to populate in cancel link        
        $vars = $this->setCommonFormVars($obj);
        
        // if referer
        if(isset($_GET['referer'])) {
            $link = array('entry', false, $obj->get('id'));
            $vars_ = $this->setRefererFormVars($_GET['referer'], $link);            
            $vars['cancel_link'] = $vars_['cancel_link'];
        }
        
        if(isset($_GET['back'])) {
            $more = array('action'=>'history','id'=>$obj->get('id'));
            $vars['cancel_link'] = $vars['cancel_link'] . '&' . http_build_query($more);
        }
        
        $tpl->tplAssign($vars);
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg); 

        
        $more = array('id'=>$obj->get('id'));
        if(isset($_GET['referer'])) {
            $more['referer'] = $_GET['referer'];
        }
         
        $action = (isset($_GET['back'])) ? $_GET['back'] : 'update';
        
        $link = $this->controller->getLink('this', 'this', false, $action, $more);
        $update_link = sprintf("location.href='%s';", $link);
        $cancel_link = sprintf("location.href='%s';", $vars['cancel_link']);
        
        if($this->controller->getMoreParam('popup')) {
            $update_link = 'window.top.location.reload();';
            
            $params = array('entry', false, $obj->get('id'));
            $cancel_link = sprintf("window.top.location.href='%s';", $this->controller->getClientLink($params));
        }
        
        $tpl->tplAssign('update_link', $update_link);
        $tpl->tplAssign('cancel_link', $cancel_link);  
  
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>