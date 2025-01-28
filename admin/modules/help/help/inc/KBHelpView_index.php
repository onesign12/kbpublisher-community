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


class KBHelpView_index extends AppView
{
    
    var $tmpl = 'index.html';

    
    function execute(&$obj, &$manager) {

        $this->addMsg('random_msg.ini');
        

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
    
        $links = array();
        $links['client_area'] = array(
            $this->msg['client_area_msg'],
            'https://www.kbpublisher.com/client/',
            ''
        );
                                      
        $links['user_manual'] = array(
            $this->msg['user_manual_msg'],
            'https://www.kbpublisher.com/kb/1/',
            ''
        );
        
        // $links['admin_manual'] = array(
        //     $this->msg['admin_manual_msg'],
        //     'https://www.kbpublisher.com/kb/1/',
        //     ''
        // );
        // 
        // $links['dev_manual'] = array(
        //     $this->msg['dev_manual_msg'],
        //     'https://www.kbpublisher.com/kb/1/',
        //     ''
        // );
        
        $links['faq'] = array(
            $this->msg['faq_msg'],
            'https://www.kbpublisher.com/kb/2/',
            ''
        );                      
                                      
        // $links['support'] = array(
        //     $this->msg['support_request_msg'],
        //     'https://www.kbpublisher.com/kb/contact/',
        //     ''
        // );    
                                     
        $links['support2'] = array(
            $this->msg['support_email_msg'],
            'support@kbpublisher.com',
            ''
        );                                        
        
        //$links['report_bug'] = array(
            // $this->msg['report_bug_msg'],
            // 'https://bugs.kbpublisher.com/',
            // ''
        // );        
        
        $links['check_updates'] = array(
            $this->msg['check_udates_msg'],
            $this->getLink('help', 'help', false, 'check_updates'),
            $this->msg['check_run_msg'],
            1
        );
        
        
        foreach($links as $k => $v) {
            $a['item_title'] = $v[0];
            $a['item_link'] = $v[1];
            $a['item_text'] = (!empty($v[2])) ? $v[2] : $v[1];
            $a['target'] = (empty($v[3])) ? '_blank' : '_self';
            $a['item_link'] = (strpos($v[1], '@') !== false) ? 'mailto:' . $v[1] : $v[1];
            $tpl->tplParse($a, 'row');
        }
        
    
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>