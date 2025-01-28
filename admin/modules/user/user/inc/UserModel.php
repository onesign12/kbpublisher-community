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


class UserModel extends AppModel
{

    var $tables = array(
        'table'=>'user', 'user', 'user_company', 'priv', 'priv_name', 'priv_rule', 'user_to_sso',
        'role'=>'user_role', 'user_to_role', 'list_value',
        'country'=>'list_country', 'kb_entry', 'file_entry',
        'user_subscription', 'user_extra', 'user_temp', 'user_activity',
        'data_to_user_value', 'kb_category', 'trigger', 'entry_trash', 
        'entry_rule', 'entry_draft', 'entry_draft_workflow', 'entry_draft_workflow_to_assignee');

    var $user_id;
    var $use_priv;
    var $use_role;
    var $user_priv_id;
    var $user_priv_level;
    var $is_admin;

    var $use_old_pass = true;
    var $account_updateable = true; // by end user own account

    var $entry_type = 10;

    // these subscription types could be managed when adding/update user
    // news, all article categories, all files categories
    var $subscription_ids = array(3, 11, 12);

    // var $extra_rules = array(
    //     'api' => 1,
    //     'mfa' => 2
    // );
    // 
    // static $temp_rules = array(
    //     'reset_password' => 1,
    //     'api_session'    => 2,
    //     'reset_username' => 3,
    //     'old_password'   => 4, // keep users old passwords to not using old pass in rotation
    //     // 'old_email' => 5 // to keep previous emails in case if chenged ?
    //     // 'lock'           => 6, // new one
    //     // 'reset_lock'     => 7 // new one
    // );


    function __construct() {
        parent::__construct();
        $this->user_id = AuthPriv::getUserId();
        $this->user_priv_id = AuthPriv::getPrivId();
        $this->is_admin = AuthPriv::isAdmin();
        if($this->user_priv_id) {
            $this->user_priv_level = $this->getPrivLevelByPrivId($this->user_priv_id);
        }

        $this->company_manager = new CompanyModel();
        $this->role_manager = new RoleModel();
        $this->dv_manager = new DataToValueModel();
        
        $this->extra_manager = new UserModelExtra();
        $this->extra_rules = UserModelExtra::$extra_rules;
        //$this->temp_rules = UserModelExtra::$temp_rules;
    }


    function getByIdSql($record_id) {
        $sql = "SELECT * FROM {$this->tbl->table} u WHERE {$this->sql_params} AND u.id = %d";
        // $sql = "SELECT * FROM {$this->tbl->table} u WHERE u.id = %d";
        return sprintf($sql, $record_id);
    }
    

    // for home page
    function getStatRecords() {
        $sql = "SELECT u.active, COUNT(u.id) AS num FROM {$this->tbl->user} u
        WHERE $this->sql_params
        GROUP BY u.active";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        //echo $this->getExplainQuery($this->db, $result->sql);

        return $result->GetAssoc();
    }


    function getRecordsSql() {
        $sql = "
        SELECT
            u.*,
            c.title as 'company',
            pn.sort_order AS priv_level,
            UNIX_TIMESTAMP(date_registered) AS ts
        FROM
            ({$this->tbl->user} u {$this->sql_params_from})
        LEFT JOIN {$this->tbl->priv} p ON u.id = p.user_id
        LEFT JOIN {$this->tbl->priv_name} pn ON p.priv_name_id = pn.id
        LEFT JOIN {$this->tbl->user_to_role} ur ON ur.user_id = u.id
        LEFT JOIN {$this->tbl->user_company} c ON c.id = u.company_id

        WHERE 1
            AND {$this->sql_params}

        GROUP BY u.id
        {$this->sql_params_order}";

        //echo '<pre>', print_r($sql, 1), '</pre>';
        return $sql;
    }


    // for page by page
    function getCountRecordsSql() {
        $sql = "SELECT COUNT(DISTINCT(u.id))
        FROM
            ({$this->tbl->user} u {$this->sql_params_from})
        LEFT JOIN {$this->tbl->priv} p ON u.id = p.user_id
        LEFT JOIN {$this->tbl->user_to_role} ur ON ur.user_id = u.id

        WHERE 1
            AND {$this->sql_params}";

        //echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }


    function getCountrySelectRange() {
        $sql = "SELECT id, title FROM {$this->tbl->country} ORDER BY title";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    // get user priv level, return false if user does not have priv
    function getPrivLevelByUserId($user_id, $one = true) {
        $sql = "SELECT user_id, sort_order
        FROM {$this->tbl->priv} p, {$this->tbl->priv_name} pn
        WHERE p.user_id IN({$user_id})
        AND p.priv_name_id = pn.id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $ret = $result->GetAssoc();
        if($ret) {
            $ret = ($one) ? $ret[$user_id] : $ret;
        } else {
            $ret = false;
        }

        return $ret;
    }


    function getPrivLevelByPrivId($priv_id, $one = true) {
        $sql = "SELECT id, sort_order FROM {$this->tbl->priv_name} WHERE id IN({$priv_id})";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $res = $result->GetAssoc();
        return ($one) ? $res[$priv_id] : $res;
    }


    // is current user has bigger priv level to update user with $user_priv_level
    function isUpdateablePrivLevel($user_priv_level) {
        if(AuthPriv::isAdmin()) {
            return true;
        }

        if(!$user_priv_level) {
            return true;
        }

        if($this->user_priv_level < $user_priv_level) {
            return true;
        }

        return false;
    }


    function status($value, $id, $field = 'active', $keep_field = false) {
        parent::status($value, $id, $field, 'date_updated');
        
        // reset all if status not active
        $publish_status_ids = $this->getEntryStatusPublished();
        if(!in_array($status, $publish_status_ids)) {
            $record_id = $this->idToString($record_id);
            $this->resetApiSession($record_id); // reset api session
            $this->resetAuthSessionId($record_id); // looged out if concurent not allowed
        }
    }


    function save($obj, $grantor = 0) {

        $au = KBValidateLicense::validateUser($this);
        //$au = KBValidateLicense::getAllowedUserRest($this);

        // insert
        if(!$obj->get('id')) {

            if($au == false) {
                $this->use_priv = false;
            }

            $user_id = $this->add($obj);

            if($this->use_priv) {
                $this->addPriv($obj->getPriv(), $user_id, $grantor);
            }

            if($this->use_role) {
                $this->addRole($obj->getRole(), $user_id);
            }

            if($obj->getSubscription()) {
                $this->subscribe($obj->getSubscription(), $user_id);
            }

            // if($obj->getExtra()) {
            //     $this->addExtra($obj->getExtra(), $user_id);
            // }

            if($obj->getSso()) {
                $this->addSso($obj->getSso(), $user_id);
            }

        // update
        } else {

            $user_id = (int) $obj->get('id');

            // no priv if no allowed users and no current priv
            if($au == false) {
                if(!$this->getPrivById($user_id)) {
                    $this->use_priv = false;
                }
            }
            
            $this->update($obj);

            if($this->use_priv) {
                $this->deletePriv($user_id);
                $this->addPriv($obj->getPriv(), $user_id, $grantor);
            }

            if($this->use_role) {
                $this->deleteRole($user_id);
                $this->addRole($obj->getRole(), $user_id);
            }
            
            if($obj->getSso()) {
                $this->addSso($obj->getSso(), $user_id);
            }
            
            $this->resetApiSession($user_id); // reset api session
            $this->resetAuthSessionId($user_id); // looged out if concurent not allowed
        }

        return $user_id;
    }


    function updatePassword($password, $user_id, $changed) {
        $sql = "UPDATE {$this->tbl->user} SET password = '%s' %s WHERE id = %d";
        
        $params = '';
        if ($changed) {
            $params = ', lastpass = UNIX_TIMESTAMP(), date_updated = date_updated';
            AuthPriv::setPassExpired(0);
        }
        
        $sql = sprintf($sql, $password, $params, $user_id);
        return $this->db->Execute($sql) or die(db_error($sql));
    }


    function getPassword($user_id) {
        $sql = "SELECT password FROM {$this->tbl->user} WHERE id = %d";
        $sql = sprintf($sql, $user_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('password');
    }


    // return true if we already have such username false otherwise
    function isUsernameExists($username, $id = false) {

        $cond = ($id) ? "id != '$id'" : "1=1";

        $sql = "SELECT 1 FROM {$this->tbl->user} WHERE username = '$username' AND $cond";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return (bool) ($result->Fields(1));
    }


    // return true if we already have such email false otherwise
    function isEmailExists($email, $id = false) {

        $cond = ($id) ? "id != '$id'" : "1=1";

        $sql = "SELECT 1 FROM {$this->tbl->user} WHERE email = '$email' AND $cond";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return (bool) ($result->Fields(1));
    }


    // PRIV // -----------------

    function getUserPrivMsg() {

        $data = array();
        $priv_lang = AppMsg::getMsgs('privileges_msg.ini');

        $sql = "
        SELECT
            n.id,
            n.name,
            IFNULL(n.description, 'msg') AS description
        FROM
            {$this->tbl->priv_name} n
        ORDER BY sort_order";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        while($row = $result->FetchRow()) {

            if(!$row['name']) {
                $row['name'] = $priv_lang[$row['id']]['name'];
            }

            if($row['description'] == 'msg') {
                $row['description'] = $priv_lang[$row['id']]['description'];
            }

            $data[$row['id']] = array('name' => $row['name'],
                                      'description' => $row['description']);
        }

        //echo "<pre>"; print_r($data); echo "</pre>";
        return $data;
    }


    // return user permission array
    function getPrivById($record_id) {
        $sql = "SELECT priv_name_id AS id, priv_name_id FROM {$this->tbl->priv}
        WHERE user_id = '{$record_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    // in list records
    function &getPrivByIds($record_id) {
        $data = array();
        $sql = "SELECT p.user_id, p.priv_name_id
        FROM {$this->tbl->priv} p
        WHERE user_id IN ($record_id)";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        while($row = $result->FetchRow()){
            // $data[$row['user_id']][] = $row['priv_name_id'];
            $data[$row['user_id']] = $row['priv_name_id'];
        }

        return $data;
    }


    function getPrivSelectRange($add = true) {

        $data = array();
        $priv_lang = $this->getUserPrivMsg();

        $sql = "SELECT n.id, n.name FROM {$this->tbl->priv_name} n ORDER BY sort_order";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        while($row = $result->FetchRow()) {
            $row['name'] = $priv_lang[$row['id']]['name'];
            $data[$row['id']] = $row['name'];
        }

        if($add && $data) {
            $priv_ids = implode(',', array_keys($data));
            $levels = $this->getPrivLevelByPrivId($priv_ids, false);
            foreach($levels as $priv_id => $level) {
                if(!$this->isUpdateablePrivLevel($level)) {
                    unset($data[$priv_id]);
                }
            }
        }

        return $data;
    }


    function getPrivDescription($priv_ids = array()) {

        $data = array();
        $priv_lang = $this->getUserPrivMsg();
        $params = ($priv_ids) ? "id IN({$priv_ids})" : "1";

        $sql = "SELECT n.id, n.name FROM {$this->tbl->priv_name} n WHERE {$params} ORDER BY sort_order";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        while($row = $result->FetchRow()) {

            $row['name'] = $priv_lang[$row['id']]['name'];
            $row['description'] = $priv_lang[$row['id']]['description'];

            $data[$row['id']] = $row;
        }

        return $data;
    }


    function addPriv($priv, $user_id, $grantor_id = 0, $rest_allowed_license_users = 'skip') {

        $data = array();
        $priv = (is_array($priv)) ? $priv : array($priv);
        $user_id = (is_array($user_id)) ? $user_id : array($user_id);
        foreach($priv as $k => $_priv) {
            foreach($user_id as $_user_id) {

                if($rest_allowed_license_users !== 'skip') {
                    if($rest_allowed_license_users <= 0) {
                        continue;
                    }
                    $rest_allowed_license_users--;
                }

                $data[] = array($_priv, $_user_id);
            }
        }

        //echo '<pre>'; print_r($data); echo '</pre>';
        //echo '<pre>'; print_r($rest_allowed_license_users); echo '</pre>';
        //exit();

        if($data) {
            $sql = MultiInsert::get("INSERT {$this->tbl->priv} (priv_name_id, user_id, grantor, timestamp)
                                     VALUES ?", $data, array($grantor_id, 'NOW()'));

            return $this->db->Execute($sql) or die(db_error($sql));
        }
    }


    // ROLE // ----------------------------

    function getChildRoles($rows, $id) {
        return $this->role_manager->getChildRoles($rows, $id);
    }


    function getRoleRecords() {
        return $this->role_manager->getSelectRecords();
    }


    function getRoleSelectRange($arr = false) {
        return $this->role_manager->getSelectRange($arr);
    }


    function getRoleSelectRangeFolow($arr = false) {
        return $this->role_manager->getSelectRangeFolow($arr);
    }


    // return user permission array
    function getRoleById($record_id) {
        $sql = "SELECT role_id AS id, role_id FROM {$this->tbl->user_to_role}
        WHERE user_id = '{$record_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    // in list records
    function &getRoleByIds($record_id) {
        $data = array();
        $sql = "SELECT ur.user_id, ur.role_id, r.title
        FROM
            {$this->tbl->role} r,
            {$this->tbl->user_to_role} ur
        WHERE 1
            AND ur.user_id IN ($record_id)
            AND ur.role_id = r.id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        while($row = $result->FetchRow()){
            $data[$row['user_id']][$row['role_id']] = $row['title'];
        }

        return $data;
    }


    function addRole($role, $user_id) {

        $data = array();
        $role = (is_array($role)) ? $role : array($role);
        $user_id = (is_array($user_id)) ? $user_id : array($user_id);
        foreach($role as $k => $_role) {
            foreach($user_id as $_user_id) {
                $data[] = array($_role, $_user_id);
            }
        }

        //echo "<pre>"; print_r($role); echo "</pre>";
        //echo "<pre>"; print_r($data); echo "</pre>";
        //exit;

        if($data) {
            $sql = MultiInsert::get("INSERT IGNORE {$this->tbl->user_to_role} (role_id, user_id)
                                     VALUES ?", $data);

            return $this->db->Execute($sql) or die(db_error($sql));
        }
    }


    // STATUS, TYPE // ---------------------------------

    function getListSelectRange($active_only = true, $updated_entry_value = false) {
        return ListValueModel::getListSelectRange('user_status', $active_only, $updated_entry_value);
    }


    function getEntryStatusData() {
        foreach(ListValueModel::getListData('user_status') as $list_value => $v) {
            $data[$v['list_value']] = array('title' => $v['title'],
                                            'color' => $v['custom_1']
                                            );
        }

        return $data;
    }


    static function getEntryStatusPublished() {
        $data = array();
        foreach(ListValueModel::getListData('user_status') as $list_value => $v) {
            if($v['custom_3'] == 1) {
                $data[$v['list_value']] = $v['list_value'];
            }
        }

        return $data;
    }


    function getStatusKey($entry_id) {
        $sql = "SELECT l.list_key
        FROM
            {$this->tbl->user} e,
            {$this->tbl->list_value} l
        WHERE e.id = '{$entry_id}'
        AND l.list_id = 2
        AND l.list_value = e.active";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('list_key');
    }


    // COMPANY

    function getCompanySelectRange() {
        return $this->company_manager->getSelectRange();
    }


    // AUTHOR

    function getNumAuthor($user_ids, $type = 'article') {
        $table = ($type == 'article') ? $this->tbl->kb_entry : $this->tbl->file_entry;
        $sql = "SELECT author_id, COUNT(*) AS 'num'
        FROM {$table}
        WHERE author_id IN ($user_ids)
        GROUP BY author_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    // SUBSCRIPTION

    function subscribe($subscription, $user_id) {
        $m = new SubscriptionModel();
        $m->saveSubscription(array(0), $subscription, $user_id);
    }


    function getSubscriptionSelectRange($msg) {
        $data = array();
        $data[3] = $msg['news_subsc_msg'];
        $data[11] = $msg['articles_subsc_msg'];
        $data[12] = $msg['files_subsc_msg'];

        foreach($data as $k => $v) {
            if(!in_array($k, $this->subscription_ids)) {
                unset($data[$k]);
            }
        }

        return $data;
    }


    function getUserSubscription($user_ids) {
        $sql = "SELECT user_id, entry_type, COUNT(*) AS 'num', SUM(entry_id) AS 'sum'
        FROM {$this->tbl->user_subscription}
        WHERE user_id IN ($user_ids)
        GROUP BY user_id, entry_type";
        $result = $this->db->Execute($sql) or die(db_error($sql));

        $data = array();
        while($row = $result->FetchRow()) {
            $num = ($row['sum']) ? $row['num'] : 'all';
            $data[$row['user_id']][$row['entry_type']] = $num;
        }

        return $data;
    }

    // SSO // ----------------------

    function getSso($record_id) {
        $sql = "SELECT * FROM {$this->tbl->user_to_sso} WHERE user_id = '{$record_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetArray();
    }


    function addSso($sso_values, $user_id) {
        
        $data = array();
        foreach($sso_values as $provider_id => $v) {
            $data[] = array($user_id, $v['sso_user_id'], $v['sso_provider_id'],);
        }

        //echo "<pre>"; print_r($sso_values); echo "</pre>";
        //echo "<pre>"; print_r($data); echo "</pre>";
        //exit;

        if($data) {
            $sql = "REPLACE {$this->tbl->user_to_sso} (user_id, sso_user_id, sso_provider_id) VALUES ?";
            $sql = MultiInsert::get($sql, $data);
            
            return $this->db->Execute($sql) or die(db_error($sql));
        }
    }
    

    function addSsoRecord($sso_values, $user_id) {
        $sso[$sso_values['sso_provider_id']] = array(
            'sso_provider_id' => $sso_values['sso_provider_id'],
            'sso_user_id' => $sso_values['sso_user_id']
        );
        
        $this->addSso($sso, $user_id);
    }


    // EXTRA // ---------------------

    function getExtraById($user_id, $filters = array(), $table = 'user_extra') {
        return $this->extra_manager->getExtraById($user_id, $filters, $table);
    }


    // in list records
    function getExtraByIds($user_id) {
        return $this->extra_manager->getExtraByIds($user_id);
    }
    

    function saveExtra($values, $user_id) {
        $this->extra_manager->saveExtra($values, $user_id);
    }


    function updateExtra($values, $user_id) {
        $this->extra_manager->updateExtra($values, $user_id);
    }


    function addExtra($values, $user_id) {
        $this->extra_manager->addExtra($values, $user_id);
    }


    function deleteExtraRule($rule_id, $user_id, $table = 'user_extra') {
        return $this->extra_manager->deleteExtraRule($rule_id, $user_id);
    }
    
    
    // delete all for user by user_id
    function deleteExtra($record_id, $skip_ids = false) {
        return $this->extra_manager->deleteExtra($record_id, $skip_ids);
    }
    
    
    function deleteTemp($record_id, $skip_ids = false) {
        return $this->extra_manager->deleteTemp($record_id, $skip_ids);
    }


    function resetAuthSessionId($record_id) {
        $rule_id = UserModelExtra::$temp_rules['auth_id'];
        $this->extra_manager->deleteTempRule($rule_id, $record_id);
    }

    
    function resetRememberAuth($record_id) {
        $this->extra_manager->resetRememberAuth($record_id);
    }


    // API // -----------------------------

    static function generateApiKey() {
        $salt = str_shuffle(WebUtil::generatePassword(4,4));
        return md5(microtime() . $salt);
    }
    

    // we need delete session on update
    function resetApiSession($record_id) {
        $rule_id = UserModelExtra::$temp_rules['api_session'];
        $sql = "DELETE FROM {$this->tbl->user_temp}
        WHERE rule_id = '{$rule_id}' AND user_id IN ($record_id)";
        return $this->db->Execute($sql) or die(db_error($sql));
    }    


    // DELETE // ---------------------------
    
    function isAccountDeleteable() {
        $allow_delete = SettingModel::getQuick(1, 'allow_delete_account');
        $allow = (bool) $allow_delete;
        
        if($allow_delete) {
            if(AuthPriv::isAdmin()) {
                // $allow = 'none';
                $allow = false;
            } elseif($allow_delete == 2 && AuthPriv::getPrivId()) { // staff only
                $allow = false;
            }
        }
        
        return $allow;
    }


    function deleteSso($record_id) {
        $sql = "DELETE FROM {$this->tbl->user_to_sso} WHERE user_id IN ($record_id)";
        return $this->db->Execute($sql) or die(db_error($sql));
    }


    function deleteRole($record_id) {
        $sql = "DELETE FROM {$this->tbl->user_to_role} WHERE user_id IN ($record_id)";
        return $this->db->Execute($sql) or die(db_error($sql));
    }


    function deletePriv($record_id) {
        $sql = "DELETE FROM {$this->tbl->priv} WHERE user_id IN ($record_id)";
        return $this->db->Execute($sql) or die(db_error($sql));
    }


    function deleteSubscription($record_id) {
        $sql = "DELETE FROM {$this->tbl->user_subscription} WHERE user_id IN ($record_id)";
        return $this->db->Execute($sql) or die(db_error($sql));
    }


    function deleteSubscriptionSoft($record_id) {
        // delete news, categories
        $sql = "DELETE FROM {$this->tbl->user_subscription} 
        WHERE entry_type NOT IN (1,2) user_id IN ($record_id)";
        return $this->db->Execute($sql) or die(db_error($sql));
    
        $sql = "UPDATE {$this->tbl->user_subscription} 
        WHERE entry_type IN (1,2) user_id IN ($record_id)";
        return $this->db->Execute($sql) or die(db_error($sql));
    }


    function deleteSupervisor($record_id) {
        $rules_ids = implode(',', $this->dv_manager->getSupervisorRuleIds());
        $this->dv_manager->deleteDataByUserValue($record_id, $rules_ids);
    }


    function deleteUser($record_id) {
        $sql = "DELETE FROM {$this->tbl->user} WHERE id IN ($record_id)";
        return $this->db->Execute($sql) or die(db_error($sql));
    }


    function delete($record_id, $on_trash = false) {

        // convert to string 1,2,3... to use in IN()
        $record_id = $this->idToString($record_id);

        $this->deleteUser($record_id);
        $this->deletePriv($record_id);
        $this->deleteRole($record_id);
        $this->deleteSso($record_id);
        $this->deleteExtra($record_id);
        $this->deleteTemp($record_id);
        
        if(!$on_trash) {
            $this->deleteSubscription($record_id);
            $this->deleteSupervisor($record_id);
        }
    }

    
    // TRASH // ------------------------
    
    // do not delete subscription and supervisor
    function deleteOnTrash($record_id) {
        $this->delete($record_id, true);
    }
    
    
    function deleteMissedSubscription() {
        $sql = "DELETE s FROM {$this->tbl->user_subscription} s
        LEFT JOIN {$this->tbl->user} e ON e.id = s.user_id
        WHERE e.id IS NULL";

        return $this->db->_Execute($sql) or die(db_error($sql));
    }
    
    
    function deleteMissedSupervisor() {
        $rules_ids = implode(',', $this->dv_manager->getSupervisorRuleIds());
        $this->dv_manager->deleteDataMissed($rules_ids);
    }
    
    
    function deleteOnTrashEmpty() {
        $this->deleteMissedSubscription();
        $this->deleteMissedSupervisor();
    }

    
    function deleteOnTrashEntry($record_id) {
        $this->deleteSubscription($record_id);
        $this->deleteSupervisor($record_id);
    }


    // CHECK PRIV // --------------

    // if check priv is different for model so reassign
    function checkPriv(&$priv, $action, $record_id = false, $bulk_action = false, $user = array()) {

        $priv->setCustomAction('approve', 'update');
        $priv->setCustomAction('password', 'update');
        $priv->setCustomAction('api', 'update');
        $priv->setCustomAction('role', 'select');
        $priv->setCustomAction('activity', 'select');
        $priv->setCustomAction('export', 'select');
        $priv->setCustomAction('invite', 'insert');


        if($action == 'bulk') {
            $bulk_manager = new UserModelBulk();
            $allowed_actions = $bulk_manager->setActionsAllowed($this, $priv);

            if(!in_array($bulk_action, $allowed_actions)) {
                echo $priv->errorMsg();
                exit;
            }

        } else {

            // check priv level for actions update, detail, delete, status, login
            // user not allowed any actions for users with greater priv level
            if($record_id) {
                if($level = $this->getPrivLevelByUserId($record_id)) {
                    if(!$this->isUpdateablePrivLevel($level)) {
                        echo $priv->errorMsg();
                        exit;
                    }
                }
            }

            // check for correct priv level for new or updated user
            // user not allowed to set greater priv (add, update)
            if(!empty($user['priv'])) {
                $priv_id = (int) $user['priv'];
                $level = $this->getPrivLevelByPrivId($priv_id);
                if(!$this->isUpdateablePrivLevel($level)) {
                    echo $priv->errorMsg();
                    exit;
                }
            }

            // not allowed any actions with yourself
            if($record_id) {
                if($this->user_id == $record_id) {
                    echo $priv->errorMsg();
                    exit;
                }
            }
        }


        $sql = "SELECT 1 FROM {$this->tbl->table} u WHERE u.id = %d AND u.grantor_id = %d";
        $sql = sprintf($sql, $record_id, $priv->user_id);
        $priv->setOwnSql($sql);

        $priv->check($action);

        $priv->setOwnParam($this->getOwnParams($priv));
        $this->setSqlParams('AND ' . $priv->getOwnParam());
    }


    function getOwnParams($priv) {
        return sprintf("(u.grantor_id = %d OR u.id = %d)", $priv->user_id, $priv->user_id);
    }


    // MAIL // -----------------------------

    function _getUserMailInfo($vars) {
        $vars['link'] = APP_CLIENT_PATH;
        return $vars;
    }

    function sendUserApproved($vars) {
        $vars = $this->_getUserMailInfo($vars);
        $m = new AppMailSender();
        return $m->sendUserApproved($vars);
    }

    function sendUserInvited($vars) {
        $m = new AppMailSender();
        return $m->sendUserInvited($vars);
    }

    function sendUserAdded($vars) {
        $vars = $this->_getUserMailInfo($vars);
        $m = new AppMailSender();
        return $m->sendUserAdded($vars);
    }

    function sendUserUpdated($vars) {
        $vars = $this->_getUserMailInfo($vars);
        $m = new AppMailSender();
        return $m->sendUserUpdated($vars);
    }
    
    function sendUserDeleted($vars) {
        $vars = $this->_getUserMailInfo($vars);
        $m = new AppMailSender();
        return $m->sendUserDeleted($vars);
    }
    
    
    // this emails if changed in account
    
    function _getUserMailInfoAccount($user_id) {
        $vars = $this->getById($user_id);
        $vars['link'] = APP_CLIENT_PATH;
        $vars['kb_name'] = SettingModel::getQuick(2, 'header_title');
        $vars['ip'] = WebUtil::getIP();

        return $vars;
    }
    
    function sendPasswordChanged($user_id) {
        $vars = $this->_getUserMailInfoAccount($user_id);
        $m = new AppMailSender();
        return $m->sendPasswordChanged($vars);
    }
    
    function sendAccountChanged($user_id) {
        $vars = $this->_getUserMailInfoAccount($user_id);
        $m = new AppMailSender();
        return $m->sendAccountChanged($vars);
    }

    function sendRememberAuthSet($user_id, $vars) {
        $vars = array_merge($this->_getUserMailInfoAccount($user_id), $vars);
        $m = new AppMailSender();
        return $m->sendRememberAuthSet($vars);
    }

    // will send emails to user and to admin
    function sendAccountDeleteRequest($user_id, $vars) {
        $vars = array_merge($this->_getUserMailInfoAccount($user_id), $vars);

        $more = ['id' => $user_id];
        $vars['link'] = KBClientController::getAdminRefLink('users', 'user', false, 'delete', $more);

        $m = new AppMailSender();
        return $m->sendAccountDeleteRequest($vars);
    }

    // will send emails to user and to admin
    function sendAccountDeleted($user, $vars) {
        $vars = array_merge($this->_getUserMailInfoAccount(0), $user, $vars);
        
        $more = ['filter' => ['entry_type' => 10]];
        $vars['link'] = KBClientController::getAdminRefLink('trash','trash', false, false, $more);
        
        $m = new AppMailSender();
        $ret = $m->sendAccountDeletedUser($vars);
        return $m->sendAccountDeletedAdmin($vars);
    }


    // ACTIVITIES // -----------------------------

    function getUserActivity($user_id, $limit = -1, $offset = -1) {
        $sql = "SELECT *
            FROM {$this->tbl->user_activity}
            WHERE user_id = '{$user_id}'
                AND {$this->sql_params}
            ORDER BY date_action ASC";

        if($limit == -1) {
            $result = $this->db->Execute($sql);

        } else {
            $result = $this->db->SelectLimit($sql, $limit, $offset);
        }

        return $result->GetArray();
    }


    function getUserActivityCountSql($user_id) {
        $sql = "SELECT COUNT(*) AS num
            FROM {$this->tbl->user_activity}
            WHERE user_id = '{$user_id}'
                AND {$this->sql_params}";
        return $sql;
    }


    function getFirstUserAction($user_id) {
        $sql = "SELECT UNIX_TIMESTAMP(MIN(date_action)) as num
            FROM {$this->tbl->user_activity}
            WHERE user_id = '{$user_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');
    }
    
    
    function getAuthTitle($auth_type) {
        $msg = AppMsg::getMsg('ranges_msg.ini', false, 'auth_type');
        return $msg[AuthProvider::$providers[$auth_type]];
    }
    

    static function isAccountUpdateable() {
        $ret = true;
        
        if (AuthProvider::isRemoteAuth()) {
            AuthRemote::loadEnviroment();
            $ret = AuthRemote::isAccountUpdateable();

        } elseif (AuthProvider::isSamlAuth()) {
            AuthProvider::loadSaml();    
            $auth_setting = AuthProvider::getSettings();
            $ret = AuthSaml::isAccountUpdateable($auth_setting);
        }

        return $ret;
    }
        
}
?>