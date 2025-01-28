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


class FileEntryView_delete extends AppView
{
    
    var $template = 'form_delete.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsgPrepend('common_msg.ini', 'knowledgebase');
        
        $template_dir = APP_MODULE_DIR . 'file/entry/template/';
        $tpl = new tplTemplatez($template_dir . $this->template);
        
        $tpl->tplAssign('error_msg', AppMsg::afterActionBox('inaccessible_file'));
        
        // tabs
        // $tpl->tplAssign('menu_block', FileEntryView_common::getEntryMenu($obj, $manager, $this));
        
        $filename = FileEntryUtil::getFilePath($obj->get(), $this->setting['file_dir'], true);
        $tpl->tplAssign('filename', $filename);
        $tpl->tplAssign('id', $obj->get('id'));
        
    
        if ($this->priv->isPriv('delete')) {
            $tpl->tplSetNeeded('/delete_button');
        }
        
        if ($this->priv->isPriv('update')) {
            $tpl->tplSetNeeded('/update_button');
            $link = $this->getActionLink('update', $obj->get('id'), ['do' => 1]);
            $tpl->tplAssign('update_link', $link);
        }
        
        $link = $this->getActionLink('detail', $obj->get('id'));
        $tpl->tplAssign('detail_link', $link);
        
        
        $vars = $this->setCommonFormVars($obj);
        $vars['action_link'] = $this->getActionLink('delete', $obj->get('id'));
        
        $tpl->tplAssign($vars);
        //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        //$tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>