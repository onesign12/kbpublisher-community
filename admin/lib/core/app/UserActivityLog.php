<?php

class UserActivityLog
{
    
    static $module = array(
        'knowledgebase' => array(
            'kb_entry' => 1,
            'kb_draft' => 6
        ),
            
        'file' => array(
            'file_entry' => 2,
            'file_draft' => 7
        ),
            
        'users' => array(
            'user' => 3
        ),
            
        'news' => array(
            'news_entry' => 4
        )
    );
    
    static $action = array(
        'view'   => 1,
        'create' => 2,
        'update' => 3,
        'delete' => 4,
        'login'  => 5,
        'publish'       => 7,
        'bulk_update'   => 8,
        'trash'         => 9,
    );
    
    
    static $entry = array(
        'article'     => 1,
        'file'        => 2,
        'user'        => 3,
        'news'        => 4,
        'article_draft' => 6,
        'file_draft'    => 7
    );
    
    
    static function add($entry_type, $action_type, $entry_id = 0, $extra_data = false, $user_id = false) {
        
        $entry_type = (is_int($entry_type)) ? $entry_type : (int) self::$entry[$entry_type];
        $action_type = (is_int($action_type)) ? $action_type : (int) self::$action[$action_type];
        $user_id = ($user_id) ? (int) $user_id : AuthPriv::getUserId();
        $entry_id = ($entry_id) ? (int) $entry_id : 0;
        $extra_data = ($extra_data) ? serialize($extra_data) : '';
        
        if (!is_array($entry_id)) {
            $entry_id = array($entry_id);
        }

        if($user_id) {
            $file_date = date('Y-m-d');
            $filename = sprintf('%s/user_activity_%s_%s.log', APP_CACHE_DIR, $file_date, $user_id);
            $filename = str_replace('//', '/', $filename);
            
            foreach ($entry_id as $v) {
                $_data = array(
                    date('Y-m-d H:i:s'),
                    $entry_type,
                    $action_type, 
                    $v,
                    $user_id,
                    $extra_data,
                    WebUtil::getIP(),
                    date('Ym')
                );
                
                $data[] = implode('|', $_data);
            }
            
            $data = implode("\n", $data) . "\n";
            return FileUtil::write($filename, $data, false);
        }   
    }
    
    
    static function addAction($module, $page, $action, $entry_id, $extra_data = false) {
        $entry_type = @self::$module[$module][$page];
		
        if ($entry_type) {
            if ($action == 'insert') {
                $action = 'create';
            }
            
            if ($action == 'status') {
                $action = 'update';
            }
        
            $action_type = @self::$action[$action];
            if($action_type) {
                self::add($entry_type, $action_type, $entry_id, $extra_data);
            }
        }
    }

}
?>