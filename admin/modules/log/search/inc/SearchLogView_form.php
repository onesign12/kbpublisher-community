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


class SearchLogView_form extends AppView
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

        $type = $manager->getSearchTypeSelectRange();
        // $tpl->tplAssign('search_type_str', $type[$obj->get('search_type')]);
        $_in = array_intersect_key($type, array_flip(explode(',', $obj->get('search_type'))));
        $tpl->tplAssign('search_type_str', implode(',', $_in));
        
        $tpl->tplAssign('returned_rows', ($obj->get('exitcode')  == 11) ? '> 10' : $obj->get('exitcode'));  
        
        $tpl->tplAssign('date_search_formatted', $this->getFormatedDate($data['date_search_ts'], 'datetime'));
        $tpl->tplAssign('date_search_interval', $this->getTimeInterval($data['date_search_ts']));
        
        // search link
        $search_link = $manager->getSearchLink($obj->get('search_option'));
        $tpl->tplAssign('search_link', $search_link);

        $tpl->tplAssign('search_option_data', print_r($obj->get('search_option'), 1)); 
        

        $tpl->tplAssign($this->setCommonFormVars($obj));
        //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>