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


class LoginLogView_form extends AppView
{
    
    var $template = 'form.html';
    
    
    function execute(&$obj, &$manager, $data) {
        
        $this->addMsg('log_msg.ini');        
        $this->addMsg('user_msg.ini');
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);

        $user_id = $obj->get('user_id');
        if($user_id) {
            $user = $manager->getUserByIds($user_id); 
            $username = $user[$user_id]['username'];
    
            if($user_id == AuthPriv::getUserId()) {
                $userlink = $this->getLink('account', 'account_user');
            } else {
                $more = array('id' => $user_id);
                $userlink = $this->getLink('users', 'user', false, 'update', $more);
            }

            $tpl->tplAssign('userlink', $userlink);
            $tpl->tplAssign('username', $username);
            $tpl->tplSetNeeded('/user');
        }

        $type = $manager->getLoginTypeSelectRange($this->msg);
        $tpl->tplAssign('login_type_message', $type[$obj->get('login_type')]);
        
        $status = $manager->getLoginStatusSelectRange($this->msg);
        $tpl->tplAssign('status', $status[$obj->get('exitcode')]);
        
        $tpl->tplAssign('date_login_formatted', $this->getFormatedDate($data['date_login_ts'], 'datetime'));
        $tpl->tplAssign('date_login_interval', $this->getTimeInterval($data['date_login_ts']));

        $tpl->tplAssign($this->setCommonFormVars($obj));
        //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>