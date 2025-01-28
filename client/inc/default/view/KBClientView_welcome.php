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

class KBClientView_welcome extends KBClientView_common
{

    function &execute(&$manager) {
        
        $this->home_link = true;
        $this->parse_form = false;
        $this->meta_title = $this->msg['welcome_new_user_msg'];
        $this->nav_title = $this->msg['welcome_new_user_msg'];
        
        $data = &$this->getPage($manager);        
        
        return $data;        
    }
    
    
    
    function &getPage(&$manager)  {
        
        $tpl = new tplTemplatez($this->template_dir . 'welcome.html');
        
        
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);
    }
}
?>