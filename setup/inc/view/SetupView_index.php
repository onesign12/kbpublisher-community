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


class SetupView_index extends SetupView
{

    var $cancel_button = false;

    function &execute($manager) {
        
        $data = $this->getContent($manager);
        return $data;
    }
    
    
    function getContent($manager) {
        
        $tpl = new tplTemplatez($this->template_dir . 'index.html');
        
        // languages
        $select = new FormSelect();
        $select->setFormMethod($_POST);
        //$select->select_tag = false;
        $select->select_width = 250;
        $select->setSelectName('lang');
        $select->setRange($manager->getLangSelectRange());
        
        $tpl->tplAssign('lang_select', $select->select($this->lang));

        
        $link = 'https://www.kbpublisher.com/kb/5';
        $text = 'www.kbpublisher.com/kb';
        $this->msg_vars['doc_link'] = sprintf('<a href="%s" target="_blank">%s</a>', $link, $text);        
        
        $tpl->tplAssign('phrase_msg', $this->getPhraseMsg('index'));
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }        
}
?>