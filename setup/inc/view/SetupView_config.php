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


class SetupView_config extends SetupView
{

    var $cancel_button = false;
    var $back_button = false;

    
    function &execute($manager) {
        
        $data = $this->getContent($manager);
        return $data;
    }
    
    
    function getContent($manager) {
        
        $tpl = new tplTemplatez($this->template_dir . 'config.html');
        $tpl->tplAssign('user_msg', $this->getErrors());
        
        $tpl->tplAssign('content', $this->config_content);
        $tpl->tplAssign('download_link', $this->controller->getLink($this->view_id) . '&file=1');
        
        $tpl->tplAssign('phrase_msg', $this->getPhraseMsg('config'));
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }        
}
?>