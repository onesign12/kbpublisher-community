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

// to check and change duplicated email if any

class SetupView_update_email extends SetupView
{

    var $refresh_button = true;


    function &execute($manager) {
        
        $data = $this->getContent($manager);
        return $data;
    }
    
    
    function getContent($manager) {
        
        $this->msg['next_msg'] = $this->msg['save_msg'];
        
        $tpl = new tplTemplatez($this->template_dir . 'update_email.html');
        $tpl->tplAssign('user_msg', $this->getErrors());
        
        
        $email_field_error = array();
        if($this->errors) {
            foreach($this->errors['key'][0]['field'] as $k => $user_id) {
                $email_field_error[] = $user_id;
            }
        }
        
        // $msg['title'] = $this->msg['attention_msg'];
        $msg['body'] = $this->getPhraseMsg('db_email_upgrade');
        $tpl->tplAssign('user_do_msg', BoxMsg::factory('hint', $msg));   
        
        
        $i = 0;
        $emanager =& $this->emanager;
        $rows = $emanager->getDuplicatedEmail();
        $form_data = $this->getFormData();
        
        foreach($rows as $group) {
            foreach($group as $k => $v) {

                if(in_array($v['id'], $email_field_error)) {
                    $v['error_class'] = 'errorField';
                }
                 
                $v['current_email'] = $v['email'];
                if(isset($form_data['email'])) {
                    $v['email'] = $form_data['email'][$v['id']];
                }
                
                $tpl->tplParse($v, 'row_group/row_user');// parse nested
            }
 
            // do it nested
            $tpl->tplSetNested('row_group/row_user');
            $tpl->tplParse($a, 'row_group');
        }
        
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }        
}
?>