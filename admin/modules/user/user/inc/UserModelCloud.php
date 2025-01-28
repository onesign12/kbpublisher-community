<?php

// connect to cloud instance not implemented

class UserModelCloud
{
    var $conf;
    var $db;
    var $tbl_pref;
    
    
    function __construct($config_file) {
        
        require_once $config_file;
        $reg = &Registry::instance();
        $reg->setEntry('conf', $conf);
        
        $this->conf = $conf;
        $this->tbl_pref = $this->conf['tbl_pref'];
    }
    
    
    function connect() {
        return $this->db = &DBUtil::connect($this->conf, false);
    }
    
    
    function getList($priv_id = 1) {
        $sql = "SELECT u.*
            FROM (%suser u, %spriv p)
            WHERE u.id = p.user_id  
            AND p.priv_name_id = %d";
        $sql = sprintf($sql, $this->tbl_pref, $this->tbl_pref, $priv_id);
        
        $result = $this->db->Execute($sql);
        
        if(!$result) {
            return false;
        }
        
        return $result->GetArray();
    }
    
    
    function addUser($username, $password, $email) {
        
        $password = HashPassword::getHash($password);
        
        $sql = "INSERT %suser (username, password, email, date_registered)
            VALUES ('%s', '%s', '%s', NOW());";
        $sql = sprintf($sql, $this->tbl_pref, $username, $password, $email);
        $result = $this->db->Execute($sql);
        
        if(!$result) {
            return false;
        }
        
        return $this->db->Insert_ID(); 
    }
    
    
    function addPriv($user_id, $priv_id = 1) {
        
        $sql = "INSERT {%spriv} (priv_name_id, user_id, grantor, timestamp) 
            VALUES ('%s', '%s', '%s', NOW());";
        $sql = sprintf($sql, $this->tbl_pref, $username, $user_id, 0, NOW());
        $result = $this->db->Execute($sql);
        
        if(!$result) {
            return false;
        }
        
        return true;
    }
    
    
    function create($username, $password, $email, $priv_id = 1) {
        
        $ret = false;
        $user_id = $this->addUser($username, $password, $email);
        if($user_id) {
            $ret = $this->addPriv($user_id, $priv_id);
        }
        
        return $ret;
    }
    
    
    function delete($id) {
        
        $sql = "DELETE FROM %suser WHERE id = '%d'";
        $sql = sprintf($sql, $this->tbl_pref, $id);
        $result = $this->db->Execute($sql);
        
        if(!$result) {
            return false;
        }
        
        return true;
    }
    
    
    function changePassword($id, $password) {
        
        $password = HashPassword::getHash($password);
        
        $sql = "UPDATE %suser SET password = '%s' WHERE id = '%d'";
        $sql = sprintf($sql, $this->tbl_pref, $password, $id);
        $result = $this->db->Execute($sql);
        
        if(!$result) {
            return false;
        }
        
        return true;
    }
}

?>