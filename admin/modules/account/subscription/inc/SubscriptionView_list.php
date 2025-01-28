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


class SubscriptionView_list extends AppView
{   
    
    function getPageByPage($manager) {
        
        $rq = new RequestData($_GET, array('id'));
        $type = (int) $rq->type;
        $user_id = AuthPriv::getUserId();
        
        if (array_key_exists($rq->type, $manager->types)) {
            $manager->setSqlParams("AND entry_type = '$type'");
            $manager->setSqlParams("AND user_id = '$user_id'");
        }
        
        $bp_options = [
            'class'=>'short',
            'limit_range' => [10]
        ];
        
        $bp =& $this->pageByPage($manager->limit, $manager->getRecordsSql(), $bp_options);
        
        return $bp;
    }
    
    
    static function getAll($manager) {
         
        $user_id = AuthPriv::getUserId();
        
        $manager->setSqlParams("AND entry_id = 0", null, true);
        $manager->setSqlParams("AND user_id = '$user_id'");
        
        $rows = $manager->getRecords();
        $types = array();
        
        foreach($rows as $row) {
            $types[] = $row['entry_type'];           
        }
        
        return $types;
    }
    

    static function getAllRowsCounts($manager) {
        
        $data = array();
        $rows = $manager->getRowsCount(AuthPriv::getUserId());

        foreach($manager->types as $type_id => $type_key) {
            $num = (isset($rows[$type_id])) ? $rows[$type_id] : 0;
            $data[$type_id] = $num;
        }
   
        return $data;
    }
    
    
    function getTitle($manager, $type_id = false) {
        $type_id = ($type_id == false) ? $_GET['type'] : $type_id;
        $key = $manager->types[$type_id];
        
        return sprintf('<span class="subsc_type_title">%s</span>', $this->msg[$key . '_subsc_msg']);
    }
    
    
    function &getClientController() {
        return $this->controller->getClientController();
    }
    
    
    // NEW // --------------------
    
    function getListActions($row, $links) {
        
        $actions = array(
            'delete' => array(
                'msg'  => $this->msg['unsubscribe_msg'],
                'confirm_msg' => $this->msg['sure_common_msg']
            )
        );
        
        return $actions;
    }
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        
        //$sort->setSortItem('subscribed_msg', 'date_subscribed', 'date_subscribed', $this->msg['subscribed_msg'], 2);
        $sort->setSortItem('title_msg',  'title', 'title',  $this->msg['title_msg']);
        
        return $sort;
    }
    
    
    // LIST // --------     
     
    function getListColumns() {
        
        $options = array(
            
            'date_subscribed' => array(
                'type' => 'text',
                'title' => 'subscribed_msg',
                'width' => 150,
                'params' => array(
                    'text' =>  'date_subscribed_formatted')
            ),
            
            'entry_id' => array(
                'type' => 'text',
                'width' => 80,
                'params' => array(
                    'text' => 'entry_id'
                )
            ),
            
            'title' => array(
                'type' => 'link_tooltip',
                'params' => array(
                    'link' => 'entry_link',
                    'options' => '%target="_blank" rel="noopener noreferrer"%',
                    'title' => 'title_title',
                    'text' => 'title_entry')
            )
        );
            
        return $options;
    }
    
}
?>