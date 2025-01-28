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


class KBClientView_download extends KBClientView_common
{
    
    var $redirect_time = 600;
    
    
    function &execute(&$manager) {
        
        $this->home_link = true;
        $this->parse_form = false;
        $this->meta_title = $this->msg['download_msg'];
        $this->nav_title = $this->msg['download_msg'];
        //$this->category_nav_generate = false; // not to generate categories in navigation line
        
        $data = &$this->getForm($manager);
        
        return $data;
    }
    

    function &getForm($manager) {
        
        $tpl = new tplTemplatez($this->getTemplate('download.html'));

        $link = $this->controller->getRedirectLink('file', $this->category_id, $this->entry_id);
        $tpl->tplAssign('go_url', $link);
        $tpl->tplAssign('time', $this->redirect_time);
        
        $msg = $this->getActionMsg('success', 'download', false, array('link'=>$link));    
        $tpl->tplAssign('msg', $msg);
        
        $tpl->tplAssign('back_link', $this->getLink('files', $this->category_id));
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->getFormData());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>