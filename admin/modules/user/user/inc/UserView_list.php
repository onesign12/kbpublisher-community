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


class UserView_list extends AppView
{
    
    var $template = 'list.html';
    var $template_popup = array(
        1 => 'list_popup.html',
        2 => 'list_popup2.html',
        3 => 'list_popup_trigger.html',
        'text' => 'list_popup.html',
        4 => 'list_popup_4.html',
        5 => 'list_popup_5.html'
    );
    
    var $columns = array(
        'id','date_registered','date_logged','username',
        'role','priv','api','status');
    var $columns_popup = array('shortname', 'username', 'role', 'priv', 'status');
    var $columns_export = array('id','username','first_name','last_name','middle_name',
        'email','phone', 'company', 'priv', 'role', 'api', 
        'date_registered','date_updated','date_logged','status');
    var $export_filename = 'user';
    
    var $extra_msg = ['user_msg.ini', 'random_msg.ini'];
    
    function execute(&$obj, &$manager) {
        
        // $this->addMsg('user_msg.ini');
        // $this->addMsg('random_msg.ini');
        
        $roles = $manager->getRoleRecords();
        $this->role_range = $manager->getRoleSelectRange($roles);
        
        $list = new ListBuilder($this);
        $tmpl = $list->getTemplate();
        
        $tpl = new tplTemplatez($tmpl);
        
        $show_msg2 = $this->getShowMsg2();
        $tpl->tplAssign('msg', $show_msg2);
        
        // check 
        $add_button = true;
        $update_allowed = true;
        $bulk_allowed = array();        
        $au = KBValidateLicense::getAllowedUserRest($manager);
        if($au !== true && $au < 0) {
            $tpl->tplAssign('msg', AppMsg::licenseBox('license_exceed_users'));
            
            $update_allowed = false;
            $bulk_allowed = array('delete', 'priv');
            $add_button = false;    
        }        
        
        // popup 
        $popup = $this->controller->getMoreParam('popup');
        if($popup) {
            $max_allowed = ($this->controller->getMoreParam('limit')) ? 1 : 0;
            $tpl->tplAssign('max_allowed', $max_allowed);
        
            $close = ($this->controller->getMoreParam('close')) ? 1 : 0;
            $tpl->tplAssign('close_on_action', $close);
            
            $field_name = $this->controller->getMoreParam('field_name');
            if ($field_name == 'r') {
                $field_name = 'admin_user';
            }    
            $tpl->tplAssign('field_name', $field_name);   
                     
            $field_id = $this->controller->getMoreParam('field_id');
            $tpl->tplAssign('field_id', $field_id);
               
            $readroot = 'readroot';
            $writeroot = 'writeroot';
            if(strpos($field_name, 'mustread') !== false) {
                $readroot = 'readroot_user';
                $writeroot = 'writeroot_user';
            }
            $tpl->tplAssign('readroot', $readroot);
            $tpl->tplAssign('writeroot', $writeroot);
            
            if ($popup == 'text') {
                $add_button = false;
            }
        }
        
        // bulk
        $manager->bulk_manager = new UserModelBulk();
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv, $bulk_allowed)) {
            $tpl->tplSetNeededGlobal('bulk');
            $bulk = $this->controller->getView($obj, $manager, 'UserView_bulk');
            $tpl->tplAssign('footer', CommonBulkView::parseBulkBlock($manager, $bulk));
        }
        
        // status_msg
        $status = $manager->getEntryStatusData();
        $status_range = $manager->getListSelectRange('user_status', true);
        // $status_allowed = $this->priv->getPrivStatusSet($status_range, 'status');
        // $status_range = array_intersect($status_range, $status_allowed);
        
        
        // filter sql        
        $params = $this->getFilterSql($manager, $roles);
        $manager->setSqlParams($params['where']);
        $manager->setSqlParamsFrom($params['from']);
        
        
        $button = array('insert');
        if($add_button) {
            if($this->priv->isPriv('insert') && $this->priv->isPriv('insert', 'import_user')) {
                $button['...'][] = array(
                    'msg' => $this->msg['import_msg'],
                    'link' => $this->getLink('import', 'import_user')
                );
            }
            
            $button['...'][] = array(
                'msg'  => $this->msg['export_msg'],
                'link' => sprintf("javascript:showExportPopup('%s')", $this->msg['export_msg'])
            );
        }
        
        // header generate
        $count = (isset($params['count'])) ? $params['count'] : $manager->getCountRecords();
        $bp = &$this->pageByPage($manager->limit, $count);
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilterForm($manager, $roles), $button));
        
        // sort generate
        $sort = $this->getSort();
        $psort = (isset($params['sort'])) ? $params['sort'] : $sort->getSql();
        $manager->setSqlParamsOrder($psort);
        
        // get records
        $offset = (isset($params['offset'])) ? $params['offset'] : $bp->offset;
        $users = $this->stripVars($manager->getRecords($bp->limit, $offset));
        $ids = $manager->getValuesString($users, 'id');
        
        // get user priv / role / extra
        $user_priv = array();
        $user_role = array();
        $user_extra = array();
        if($ids) {
            $user_priv = &$manager->getPrivByIds($ids);
            $priv_msg = $this->stripVars($manager->getUserPrivMsg());
            
            $subscription = $manager->getUserSubscription($ids);
            $user_extra = $manager->getExtraByIds($ids);
        
            $user_role = &$manager->getRoleByIds($ids);
            $user_role = $this->stripVars($user_role);
            
            $full_roles = &$manager->role_manager->getSelectRangeFolow($roles);
            $full_roles = $this->stripVars($full_roles);
        }
        
        // author&updater
        if($ids) {
            $author_article = $manager->getNumAuthor($ids, 'article');
            $author_file = $manager->getNumAuthor($ids, 'file');
        }
        
        // subscriptions
        $s_manager = new SubscriptionModel;
        $subs_types = $s_manager->types;
        
        // list records
        foreach($users as $row) {
            $obj->set($row);
            $obj->setFullName();
            $obj->setShortName();
            
            // priv            
            $privilege = $this->getPrivToList($obj->get('id'), $user_priv, $priv_msg);
            // $row['privilege'] = implode(', ', $privilege);
            $row['privilege'] = $privilege;
            
            // role
            $uroles = $this->getRoleToList($obj->get('id'), $user_role, $full_roles);
            $row['role'] = implode('<br />',  $uroles['role']);
            $row['full_role'] = implode('<br />',  $uroles['full_role']);
                            
            // subscription
            if(isset($subscription[$obj->get('id')])) {
                $subsc_hint = array();
                foreach($subscription[$obj->get('id')] as $entry_type => $num) {
                    $subs_msg_key = $subs_types[$entry_type] . '_subsc_msg';
                    $type_msg = $this->msg[$subs_msg_key];
                    $num_msg = ($num == 'all') ? $this->msg['all_msg'] : $num;
                    $subsc_hint[] = sprintf('%s: %s', $type_msg, $num_msg);
                }

                $subsc_hint = implode('<br/>', $subsc_hint);

                $row['subsc'] = (!empty($subsc_hint));
                $row['subsc_title'] = $subsc_hint;
            }
            
            // author&updater
            if(isset($author_article[$obj->get('id')]) || isset($author_file[$obj->get('id')])) {
                $str = '<a href="%s" style="{style}" class="_tooltip" title="%s">%s</a>';
                $more = array('filter[q]'=>'author_id:' . $obj->get('id'));

                if(isset($author_article[$obj->get('id')])) {
                    $link = $this->getLink('knowledgebase', 'kb_entry', false, false, $more);
                    $article_num = sprintf($str, $link, $this->msg['author_num_article_msg'],
                                                    $author_article[$obj->get('id')]);
                }

                if(isset($author_file[$obj->get('id')])) {
                    $link = $this->getLink('file', 'file_entry', false, false, $more);
                    $file_num = sprintf($str, $link, $this->msg['author_num_file_msg'],
                                                $author_file[$obj->get('id')]);
                }
            }

            $row['article_num'] = (!empty($article_num)) ? $article_num : '--';
            $row['file_num'] = (!empty($file_num)) ? $file_num : '--';
            
            
            // last auth
            if($row['lastauth']) {
                $row['date_lastauth_formatted'] = $this->getTimeInterval($row['lastauth']);            
                $row['date_lastauth_formatted_full'] = $this->getFormatedDate($row['lastauth'], 'datetime');
            }
            
            // other
            $row['date_formatted'] = $this->getFormatedDate($row['ts']);
            $row['date_formatted_full'] = $this->getFormatedDate($row['ts'], 'datetime');
            
            $row['date_updated_formatted'] = $this->getFormatedDate($row['date_updated']);
            $row['date_updated_formatted_full'] = $this->getFormatedDate($row['date_updated'], 'datetime');
            
            $row['status'] = $status[$row['active']]['title'];
            $row['color'] = $status[$row['active']]['color'];            
            $row['api'] = (!empty($user_extra[$obj->get('id')][$manager->extra_rules['api']]['value1']));
            
            $row['name'] = $obj->getFullName();
            $row['mailto'] = sprintf('mailto:%s', $row['email']);
            $row['mailto_tag'] = sprintf('<a href="mailto:%s">%s</a>', $row['email'], $row['email']);
            $row['user_title'] = sprintf('<b>%s</b><br>%s', $row['name'], htmlspecialchars($row['mailto_tag']));
            $row['shortname'] = $obj->getShortName();
            $row['escaped_name'] = addslashes($row['name']);
            
            // popup
            if ($popup == 2) {
                $val = addslashes($obj->getFullName());
                if($this->controller->getMoreParam('field_name') == 1) {
                    $val = $obj->get('id');
                }
                
                $row['assigned_value'] = $val;
            }
            
            
            $row += $this->getViewListVarsCustomJs($obj->get('id'), $obj->get('active'),
                                                   $row, $manager, $privilege,
                                                   $update_allowed, $status_range);
                                                        
            
            $tpl->tplAssign('list_row', $list->getRow($row));
            $tpl->tplAssign($this->msg);
            $tpl->tplParse($row, 'row');
        }
        
        if ($popup == 3) {
            $select_id = $this->controller->getMoreParam('field_id');
            $tpl->tplAssign('select_id', $select_id);
        }
        
        // create an empty box for a message block
        if ($popup) {
            $field_suffix = '';
            if($this->controller->getMoreParam('field_id') != 'r') { // suffix
                $field_suffix = $this->controller->getMoreParam('field_id');
            }
            
            $tpl->tplAssign('readroot_id', ($field_suffix) ? 'readroot_' . $field_suffix : 'readroot');
            $tpl->tplAssign('writeroot_id', ($field_suffix) ? 'writeroot_' . $field_suffix : 'writeroot');
            $tpl->tplAssign('id_pref', ($field_suffix) ? $field_suffix . '_more_html_' : 'more_html_');
            $tpl->tplAssign('field', $field_suffix);
            
            $msg = BoxMsg::factory('success');
            $tpl->tplAssign('after_action_message_block', $msg->get());
        }
        
        // export
        $export = CommonExportView::getListExportVars($this);
        
        $tpl->tplAssign($list->getListVars($sort->toHtml(), $this->msg));
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        // return $tpl->tplPrint(1);
        return $tpl->tplPrintIn($export['tmpl'], $export['func']);
    }
    
    
    
    function getExportRecords($obj, $manager) {
        
        $roles = $manager->getRoleRecords();
        $status = $manager->getEntryStatusData();        
        $priv_msg = $manager->getUserPrivMsg();        
                                    
        // filter sql        
        $params = $this->getFilterSql($manager, $roles);
        $manager->setSqlParams($params['where']);
        $manager->setSqlParamsFrom($params['from']);
    
        $count = $manager->getCountRecords();
    
        // sort generate
        $manager->setSqlParamsOrder($this->getSort()->getSql());
        
        
        $limit = 200;
        $data = [];
        for ($i=0; $i<=$count; $i+=$limit) {
            
            $rows = $manager->getRecords($limit, $i);
            $ids = $manager->getValuesString($rows, 'id');

            // get user priv / role / extra
            $user_priv = array();
            $user_role = array();
            if($ids) {
                $user_priv = &$manager->getPrivByIds($ids);
                $user_role = &$manager->getRoleByIds($ids);
                $full_roles = &$manager->role_manager->getSelectRangeFolow($roles);
                $user_extra = $manager->getExtraByIds($ids);
            }
                        
            foreach(array_keys($rows) as $k) {
                $id = $rows[$k]['id'];
                $data[$id] = $rows[$k];
                
                $data[$id]['date_logged'] = date('Y-m-d H:i:s', $rows[$k]['lastauth']);
                $data[$id]['api'] = (!empty($user_extra[$id][$manager->extra_rules['api']]['value1'])) ? '1' : '0';
                $data[$id]['status'] = $status[$rows[$k]['active']]['title'];
                $data[$id]['priv'] = $this->getPrivToList($id, $user_priv, $priv_msg);
                
                // role
                $uroles = $this->getRoleToList($id, $user_role, $full_roles);
                // $data[$id]['role'] = implode(', ',  $uroles['role']);
                $data[$id]['role'] = implode(' | ',  $uroles['full_role']);
            }
        }

        return $data;
    }
    
    
    function getPrivToList($user_id, $user_priv, $priv_msg) {
        $privilege = false;
        if(isset($user_priv[$user_id])) {
            $privilege = $priv_msg[$user_priv[$user_id]]['name'];
            // foreach($user_priv[$user_id] as $k => $v) {
                // $privilege[] = $priv_msg[$v]['name'];
            // }
        }
        return $privilege;
    }
    
    
    function getRoleToList($user_id, $user_role, $full_roles) {
        $roles = ['role' => [], 'full_role' => []];
        if(isset($user_role[$user_id])) {
            $roles['role'] = $user_role[$user_id];
            foreach($user_role[$user_id] as $role_id => $v) {
                $roles['full_role'][] = $full_roles[$role_id];
            }
        }
        
        return $roles;
    }
    
    
    function &getSort() {
    
        //$sort = new TwoWaySort();    
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        $sort->setCustomDefaultOrder('dr', 2);    
        $sort->setCustomDefaultOrder('da', 2);
        $sort->setDefaultSortItem('dr', 2);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        
        $sort->setSortItem('id_msg',       'id',         'id',        $this->msg['id_msg']);
        $sort->setSortItem('name_msg',     'last_name',  'last_name', $this->msg['name_msg']);
        $sort->setSortItem('email_msg',    'email',      'email',     $this->msg['email_msg']);
        $sort->setSortItem('phone_msg',    'phone',      'phone',     $this->msg['phone_msg']);
        $sort->setSortItem('username_msg', 'username',   'username',  $this->msg['username_msg']);
        $sort->setSortItem('signing_date_msg','dr',      'date_registered',   $this->msg['signing_date_msg']);
        $sort->setSortItem('last_logged_msg', 'da',      'lastauth',  $this->msg['last_logged_msg']);
        $sort->setSortItem('updated_msg',     'du',      'date_updated',  $this->msg['updated_msg']);
        
        return $sort;
    }


    function getViewListVarsCustomJs($record_id, $active, $data, $manager, $priv, $update_allowed, $status_range) {
        
        $actions = array(
            'detail' => true, 
            'update' => true, 
            'trash' => array(
                // 'msg' => sprintf('%s (%s)', $this->msg['trash_msg'], $this->msg['delete_account_msg']),
                'confirm_msg' => false
            )
        );
        
        $own_record = ($data['grantor_id'] == $manager->user_id);
        $bulk_ids_ch_option = '';

        if(AppPlugin::isPlugin('report')) {
            $actions['activity'] = array(
                'link' => $this->getLink('this', 'this', false, 'activity', array('id' => $record_id)),
                'msg' => $this->msg['activities_msg']
            );
        }

        // login as user
        if($this->priv->isPriv('login')) {
            $actions['login'] = array(
                'link' => $this->getActionLink('login', $record_id),
                'msg'  => $this->msg['login_as_user_msg']
            );          
        }
        
        // self login
        if($this->priv->isSelfPriv('login') && !$own_record) {
             $actions['login'] = false;
        }        
        
        // status
        foreach ($status_range as $k => $v) {
            $actions['status'][] = array(
                'msg' => $v,
                'value' => $k
            );
        }
        
        // priv level
        if(!$manager->isUpdateablePrivLevel($data['priv_level'])) {
            $actions['login'] = false;
            $actions['detail'] = false; // no detail button for bigger priv level
            $actions['activity'] = false;
            $actions['update'] = false;
            // $actions['delete'] = false;
            $actions['trash'] = false;
            $actions['status'] = false;
            $bulk_ids_ch_option = 'disabled';
        }

        // yourself    
        if($manager->user_id == $record_id) {
            $actions['login'] = false;
			$actions['activity'] = false;
            $actions['update'] = false;
            // $actions['delete'] = false;
            $actions['status'] = false;
            $actions['trash'] = false;
            $bulk_ids_ch_option = 'disabled';

            $actions['detail'] = array(
                'link' => $this->getLink('account', 'account_user', false, false),
                'msg'  => $this->msg['user_account_msg']
            );
        }
        
        // for licensing
        if(!$priv) {
            if($update_allowed == false) {
                $actions['update'] = false;
                $actions['status'] = false;
            }            
        }
        
        $row = $this->getViewListVarsJs($record_id, $active, $own_record, $actions);
        $row['bulk_ids_ch_option'] = $bulk_ids_ch_option;
        if(!$actions['update']) {
            $row['update_link'] = $this->controller->getCurrentLink();
        }       
        
        return $row;
    }
    
    
    function getFilterForm($manager) {

        $values = $this->parseFilterVars(@$_GET['filter']);    
    
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
    
        $select = new FormSelect();
        $select->select_tag = false;
        
        // priv
        $v = (!empty($values['priv'])) ? $values['priv'] : 'all';
        $extra_range = array('all'  => '___', 
                             'none' => $this->msg['none_priv_msg'],
                             'any' => $this->msg['any_priv_msg']);
        $select->setRange($manager->getPrivSelectRange(false), $extra_range);
        $tpl->tplAssign('priv_select', $select->select($v));    
        
        // roles
        $range = array(
            'none' => $this->msg['none_role_msg'],
            'any' => $this->msg['any_role_msg']
        );
        $roles = $manager->getRoleSelectRangeFolow();
        $range = $range + $roles;
        
        if(!empty($values['role'])) {
            $role_id = (int) $values['role'];
            $role_name = $this->stripVars($range[$role_id]);
            $tpl->tplAssign('role_name', $role_name);
            
        } else {
            $role_id = 0;
        }

        $tpl->tplAssign('role_id', $role_id);
        
        $js_hash = array();
        $str = '{label: "%s", value: "%s"}';
                
        
        foreach(array_keys($range) as $k) {
            $js_hash[] = sprintf($str, addslashes($range[$k]), $k);
        }

        $js_hash = implode(",\n", $js_hash);
        $tpl->tplAssign('roles', $js_hash);
        
        
        $tpl->tplAssign('ch_checked', $this->getChecked((!empty($values['ch']))));
        
        // company
        $v = (!empty($values['comp'])) ? $values['comp'] : 'all';
        $extra_range = array('all'  => '___', 
                             'none' => $this->msg['none_company_msg'],
                             'any' => $this->msg['any_company_msg']);
        $select->setRange($manager->getCompanySelectRange(), $extra_range);
        $tpl->tplAssign('company_select', $select->select($v));
        
        // status
        @$v = $values['s'];
        $extra_range = array('all'=>'__');
        $select->setRange($manager->getListSelectRange(false), $extra_range);
        $tpl->tplAssign('status_select', $select->select($v));
        
        
        // by
        //SEARCH
        $range = array(
            'all' => '__', 
            'id' => $this->msg['id_msg'],
            'last_name' => $this->msg['last_name_msg'], 
            'first_name' => $this->msg['first_name_msg'], 
            'username' => $this->msg['username_msg'],
            'email' => $this->msg['email_msg'],
            'phone' => $this->msg['phone_msg'],
           //'address'=>'Address', 'city'=>'City', 'state'=>'State', 'zip'=>'Zip', 
           //'day_phone'=>'Day Phone', 'evening_phone'=>'Evening Phone', 
           //'mobile_phone'=>'Mobile Phone', 'fax'=>'Fax'
           );
        
        unset($range['all']);
        $msg = sprintf('%s: %s', $this->msg['search_msg'], implode(', ', $range));
        $tpl->tplAssign('search_infield', $msg);            
        
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql($manager, $roles) {
        
        // filter
        $mysql = array();
        $sphinx = array();
        @$values = $_GET['filter'];
        
        // priv
        @$v = $values['priv'];
        if($v == 'none') {
            $mysql['where'][] = "AND p.priv_name_id IS NULL";
            $sphinx['where'][] = "AND priv_name_id = 0";
        
        } elseif($v == 'any') {
            $mysql['where'][] = "AND p.priv_name_id IS NOT NULL";
            $sphinx['where'][] = "AND priv_name_id != 0";
        
        } elseif($v != 'all' && !empty($v)) {
            $priv_id = intval($v);
            $mysql['where'][] = "AND p.priv_name_id = '{$priv_id}'";
            $sphinx['where'][] = "AND priv_name_id = {$priv_id}";
        }    
        
        
        // role
        @$v = $values['role'];
        if($v == 'none') {
            $mysql['where'][] = "AND ur.role_id IS NULL";
            
            $sphinx['select'][] = 'LENGTH(role_ids) as _role_ids';
            $sphinx['where'][] = 'AND _role_ids = 0';
        
        } elseif($v == 'any') {
            $mysql['where'][] = "AND ur.role_id IS NOT NULL";
            
            $sphinx['select'][] = 'LENGTH(role_ids) as _role_ids';
            $sphinx['where'][] = 'AND _role_ids != 0';
        
        } elseif($v != 'all' && !empty($v)) {
            
            $role_id = (int) $v;            
            if(!empty($_GET['filter']['ch'])) {
                $child = $manager->getChildRoles($roles, $role_id);
                $child[] = $role_id;
                $child = implode(',', $child);    
                $mysql['where'][] = "AND ur.role_id IN($child)";
                $sphinx['where'][] = "AND role_ids IN ($child)";
                
            } else {
                $mysql['where'][] = "AND ur.role_id = $role_id";
                $sphinx['where'][] = "AND role_ids = $role_id";
            }
        }
        
        
        // company
        @$v = $values['comp'];
        if($v == 'none') {
            $mysql['where'][] = "AND u.company_id = 0";
            $sphinx['where'][] = "AND company_id = 0";
        
        } elseif($v == 'any') {
            $mysql['where'][] = "AND u.company_id != 0";
            $sphinx['where'][] = "AND company_id != 0";
        
        }  elseif($v != 'all' && !empty($v)) {
            $company_id = intval($v);
            $mysql['where'][] = "AND u.company_id = '{$company_id}'";
            $sphinx['where'][] = "AND company_id = $company_id";
        }
        
        
        // status
        @$v = $values['s'];
        if($v != 'all' && isset($_GET['filter']['s'])) {
            $v = (int) $v;
            $mysql['where'][] = "AND u.active = '$v'";
            $sphinx['where'][] = "AND active = $v";
        }                        
        
        
        // by
        @$v = $values['q'];
        @$by = $values['by'];
        
        if(!empty($v)) {
            $v = trim($v);
            
            if($ret = $this->isSpecialSearch($v)) {
                $sql = $this->parseSpecialSearchSql($manager, $ret, $v, 'u.id');
                $mysql = array_merge_recursive($mysql, $sql);
                            
            } else {
                
                $v = addslashes(stripslashes($v));
                $_v = str_replace('*', '%', $v);
                $sql_str = "%s LIKE '%%%s%%'";
                $f = array('u.first_name', 'u.last_name', 'u.username', 'u.email', 'u.username', 'u.phone');
                foreach($f as $field) {
                    $sql[] = sprintf($sql_str, $field, $_v);
                }
                
                $mysql['where'][] = 'AND (' . implode(" OR \n", $sql) . ')';
                
                $sphinx['match'][] = $v;
            }    
        }
        
        $options = array('index' => 'user', 'id_field' => 'u.id');
        $arr = $this->parseFilterSql($manager, $v, $mysql, $sphinx, $options);
        // echo '<pre>', print_r($arr, 1), '</pre>';
        
        return $arr;
    }
    
    
    function getSpecialSearch() {
        $search = array('id');
        
        $search['subscription'] = array(
            'search' => '#^(?:subs|subscriber)(?:-news|-article_cat|-file_cat|-article|-file|-all)?:(\d+(?:,\s?\d+)*)?$#',
            'prompt' => 'subscriber[-news | -article | -file | -article_cat | -file_cat | -all]:[{entry_id},{entry_id2,...}]',
            'insert' => 'subscriber:',
            'filter' => 'ids'
        );
        
        $search['logged'] = array(
            'search' => '#^(?:logged):\s?((?:(?:ago|last)\s+)?\d+\s+(minutes?|hours?|days?|weeks?|months?|years?)(\s+(ago|last))?)$#',
            'prompt' => 'logged: [last] {number} {minutes | hours | days | weeks | months | years} [ago]#',
            'insert' => 'logged: last 10 days',
        ); 

        $search['signed'] = array(
            'search' => '#^(?:signed):\s?((?:(?:ago|last)\s+)?\d+\s+(minutes?|hours?|days?|weeks?|months?|years?)(\s+(ago|last))?)$#',
            'prompt' => 'signed: [last] {number} {minutes | hours | days | weeks | months | years} [ago]#',
            'insert' => 'signed: last 10 days',
        ); 
        
        return $search;
    }
    
    
    function getSpecialSearchSql($manager, $ret, $string) {
        $mysql = array();

        if($ret['rule'] == 'subscription') {
            
            $m = new SubscriptionModel();
        
            $type_sql = 1;
            $stype = false;
            foreach($m->types as $snum => $stype) {
                $stype = ($stype != 'news') ? str_replace('s', '', $stype) : $stype;
                if(strpos($string, $stype . ':') !== false) {
                    $type_sql = "us.entry_type = '{$snum}'";
                    break;
                } 
            }
        
            $entry_sql = 1;
            if($ret['val'] != '' && $type_sql != 1) {
                $val = explode(',', $ret['val']);
                array_walk($val, function(&$string) { $string = (int) $string; } );
                $val = implode(',', $val);
                $entry_sql = "us.entry_id IN($val)";
            }
        
            $mysql['from'] = ", {$manager->tbl->user_subscription} us";
            $mysql['where'] = "AND us.user_id = u.id AND {$type_sql} AND {$entry_sql}";
    
        } elseif($ret['rule'] == 'logged') {
        
            // example: logged: last 10 minutes = 10 minutes, 2 years ago
            list($sign, $interval) = $this->parseSpecialSearchInterval($ret);
            $mysql['where'] = "AND FROM_UNIXTIME(u.lastauth) {$sign} DATE_SUB(NOW(), INTERVAL {$interval})";
        
        } elseif($ret['rule'] == 'signed') {
        
            // example: signed: last 10 minutes = 10 minutes, 2 years ago
            list($sign, $interval) = $this->parseSpecialSearchInterval($ret);
            $mysql['where'] = "AND u.date_registered {$sign} DATE_SUB(NOW(), INTERVAL {$interval})";
        }
        
        // echo '<pre>' . print_r($mysql, 1) . '</pre>';
        return $mysql;
    }
    
    
    function parseSpecialSearchInterval($ret) {
        $sign = (strpos($ret['val'], 'ago') !== false) ? '<' : '>'; 
        $interval = trim(str_replace(array('ago', 'last'), '', $ret['val']));
        $interval = strtr($interval, array('minutes' => 'minute','hours' => 'hour','days' => 'day',
                                           'weeks' => 'week','months' => 'month','years' => 'year'));
                                           
        return [$sign, $interval];
    }
    
    
    
    function getShowMsg2() {
        @$key = $_GET['show_msg2'];
        if ($key == 'note_remove_user_bulk') {
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);
            $msg['title'] = $msgs['title_remove_user_bulk'];
            $msg['body'] = $msgs['note_remove_user_bulk'];
            return BoxMsg::factory('error', $msg);
        }
    }
    
    
    // define list fields 
    // all available fields should be defined here
    function getListColumns() {
        
        $options = array(
            
            'id' => array(),
            
            'date_registered' => array(
                'type' => 'text_tooltip',
                'title' => 'signing_date_msg',
                'width' => 90,
                'params' => array(
                    'text' => 'date_formatted',
                    'title' => 'date_formatted_full')
            ),
            
            'date_updated' => array(
                'type' => 'text_tooltip',
                'title' => 'updated_msg',
                'width' => 90,
                'params' => array(
                    'text' => 'date_updated_formatted',
                    'title' => 'date_updated_formatted_full')
            ),
            
            'date_logged' => array(
                'type' => 'text_tooltip',
                'title' => 'last_logged_msg',
                'width' => 120,
                'params' => array(
                    'text' => 'date_lastauth_formatted',
                    'title' => 'date_lastauth_formatted_full')
            ),
        
            'username' => array(
                'type' => 'text_tooltip',
                'params' => array(
                    'text' => 'username',
                    'title' => 'user_title')
            ),    
                
            'shortname' => array(
                'type' => 'text_tooltip',
                'title' => 'name_msg',
                'params' => array(
                    'text' => 'shortname',
                    'title' => 'user_title')
            ),

            'phone' => array(
                'type' => 'text'
            ),
            
            'email' => array(
                'type' => 'link',
                'params' => array(
                    'link' => 'mailto')
            ),
        
            'role' => array(
                'type' => 'text_tooltip',
                'params' => array(
                    'text' => 'role',
                    'title' => 'full_role')
            ),
        
            'priv' => array(
                'type' => 'text',
                'params' => array(
                    'text' => 'privilege')
            ),
            
            'company' => array(
                'type' => 'text'
            ),
              
            'api' => array(
                'type' => 'bullet',
                'width' => 1,
                'options' => 'text-align: center;'
            ), 
                            
            'subsc' => array(
                'type' => 'bullet',
                'title' => 'subscription_msg',
                'shorten_title' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><path fill="#fff" d="M0 3v18h24v-18h-24zm6.623 7.929l-4.623 5.712v-9.458l4.623 3.746zm-4.141-5.929h19.035l-9.517 7.713-9.518-7.713zm5.694 7.188l3.824 3.099 3.83-3.104 5.612 6.817h-18.779l5.513-6.812zm9.208-1.264l4.616-3.741v9.348l-4.616-5.607z"/></svg>',
                'width' => 'min',
                'align' => 'center',
                'params' => array(
                    'text' => 'subsc',
                    'title' => 'subsc_title')
            ),
                                        
            'article_num' => array(
                'type' => 'text',
                'title' => 'author_num_article_msg',
                'shorten_title' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><path fill="#fff" d="M11.362 2c4.156 0 2.638 6 2.638 6s6-1.65 6 2.457v11.543h-16v-20h7.362zm.827-2h-10.189v24h20v-14.386c0-2.391-6.648-9.614-9.811-9.614zm4.811 13h-10v-1h10v1zm0 2h-10v1h10v-1zm0 3h-10v1h10v-1z"/></svg>',
                'width' => 'min',
                'align' => 'center',
                'params' => array(
                    'text' => 'article_num')
            ),
                                                    
            'file_num' => array(
                'type' => 'text',
                'title' => 'author_num_file_msg',
                'shorten_title' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><path fill="#fff" d="M8.847 17.875c.191-.211.548-.431.801-.559-.152.3-.366.619-.544.792-.36.353-.556.095-.257-.233zm7.062-12.098c2.047-.478 4.805.28 6.091 1.179-1.494-1.997-5.23-5.708-7.432-6.882 1.156 1.168 1.563 4.235 1.341 5.703zm-4.769 10.21c.328-.109 1.036-.274 1.213-.315-.02-.021-.528-.544-.695-.832-.134.335-.509 1.127-.518 1.147zm.64-4.008c-.057-.278-.263-.299-.326.024-.057.296.029.771.129 1.061.113-.237.255-.805.197-1.085zm10.22-.979v13h-20v-24h8.409c4.857 0 3.335 8 3.335 8 3.009-.745 8.256-.42 8.256 3zm-6.98 4.413c-.526-.077-1.272.009-1.797.093-.385-.325-.866-.817-1.233-1.472.253-.652.415-1.168.483-1.536.354-1.919-1.979-2.072-1.729-.012.066.549.222 1.082.464 1.588-.286.709-.651 1.508-1.018 2.232-.811.307-1.396.627-1.742.954-1.212 1.143.295 2.661 1.438 1.014.274-.395.581-.955.811-1.396.717-.253 1.551-.475 2.33-.618.509.39 1.322.896 1.972.896 1.239.001 1.417-1.538.021-1.743zm-.104.704c-.2-.03-.488-.03-.829-.002.235.158.558.323.911.33.412.008.377-.26-.082-.328z"/></svg>',
                'width' => 'min',
                'align' => 'center',
                'params' => array(
                    'text' => 'file_num')
            ),
            
            'status' => array()
            
        );
            
        return $options;
    }
    
    
}
?>