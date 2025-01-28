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



class KBEntryView_draft_delete extends AppView
{
    
    var $template = 'form_draft_delete.html';
    
    function execute(&$obj, &$manager) {
        
        $template_dir = APP_MODULE_DIR . 'knowledgebase/entry/template/';
        $tpl = new tplTemplatez($template_dir . $this->template);
        
        $tpl->tplAssign('error_msg', AppMsg::afterActionBox('delete_draft'));
        
        $d_manager = new KBDraftModel;
        $draft = $d_manager->getByEntryId($obj->get('id'), $manager->entry_type);
        $tpl->tplAssign($draft);
        
        
        $tpl->tplAssign('date_updated_formatted', 
                $this->getFormatedDate($draft['date_posted'], 'datetime'));
        $tpl->tplAssign('date_updated_formatted', 
                $this->getFormatedDate($draft['date_updated'], 'datetime'));
        
        
        if($user = $manager->getUser($draft['author_id'])) {
            $tpl->tplAssign('user_name', $user['first_name'] . ' ' . $user['last_name']);
            $tpl->tplAssign($user);            
        }
        
        $page = ($this->controller->module == 'knowledgebase') ? 'kb_draft' : 'file_draft';
        $link = $this->getLink($this->controller->module, $page, false, 'update', array('id' => $draft['id']));
        $tpl->tplAssign('update_link', $link);
        
        $vars = $this->setCommonFormVars($obj);
        $vars['action_link'] = str_replace('action=draft_remove', 'action=delete', $vars['action_link']);
        
        $tpl->tplAssign($vars);
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>