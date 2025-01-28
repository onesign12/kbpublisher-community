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


class SetupView_success extends SetupView
{

    var $cancel_button = false;
    var $back_button = false;


    function &execute($manager) {
        
        $data = $this->getContent($manager);
        return $data;
    }
    
    
    function getContent($manager) {
        
        $tpl = new tplTemplatez($this->template_dir . 'success.html');
        
        $data = $manager->getSetupData();    
        
        $http = ($data['ssl_client']) ? 'https://' : 'http://';
        //$dir = str_replace($data['document_root'], '', $data['client_home_dir']);
        $dir = preg_replace('#' . preg_quote($data['document_root']) . '#i', '', $data['client_home_dir']);
        $dir = str_replace('//', '/', '/' . $dir);
        
        $link = $http . $data['http_host'] . $dir;
        $this->msg_vars['public_link'] = sprintf('<a href="%s" target="_blank">%s</a>', $link, $link);
        
        
        $http = ($data['ssl_admin']) ? 'https://' : 'http://';
        //$dir = str_replace($data['document_root'], '', $data['admin_home_dir']);
        $dir = preg_replace('#' . preg_quote($data['document_root']) . '#i', '', $data['admin_home_dir']);
        $dir = str_replace('//', '/', '/' . $dir);    
        
        $link = $http . $data['http_host'] . $dir;
        $this->msg_vars['admin_link'] = sprintf('<a href="%s" target="_blank">%s</a>', $link, $link);
        
        
        $link = 'https://www.kbpublisher.com/kb/';
        $text = 'www.kbpublisher.com/kb';
        $this->msg_vars['doc_link'] = sprintf('<a href="%s" target="_blank">%s</a>', $link, $text);
        
        $tpl->tplAssign('phrase_msg', $this->getPhraseMsg('success'));
        $tpl->tplAssign('phrase1_msg', $this->getPhraseMsg('change_password'));
        $tpl->tplAssign('phrase2_msg', $this->getPhraseMsg('remove_setup_dir'));
        $tpl->tplAssign('phrase3_msg', $this->getPhraseMsg('user_manual'));
        
        
        if(!$manager->isUpgrade()) {
            $tpl->tplSetNeeded('/credentials');
        }
        
        $tpl->tplAssign($data);
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }        
}
?>