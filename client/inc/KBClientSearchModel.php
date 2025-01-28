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


class KBClientSearchModel extends KBClientModel
{

    var $values = array();
    var $period = 'all';
    var $order;

    var $count_limit = 100;
    static $filter_limit = 1000; // need this to speedsearch, set 0 to disable


    function __construct($values, $manager) {
        parent::__construct();

        $this->cf_manager = new CommonCustomFieldModel($manager);

        $this->values = $values;
        if(!empty($values['period'])) {
            $this->period = $values['period'];
        }

        $in_vals = KBClientSearchHelper::getInValue($values, $manager);
        $this->values['in'] = $in_vals['in'][0];
        $this->values['by'] = $in_vals['by'];
        $this->values['qs'] = $in_vals['qs'];
    }


    function getTagIds($tags) {

        $tags = (is_array($tags)) ? $tags : array($tags);
        foreach($tags as $k => $tag) {
            $tags[$k] = trim($tag);
        }

        $tags = implode("','", $tags);

        $sql = "SELECT id, id AS id2 FROM {$this->tbl->tag} WHERE title IN ('{$tags}')";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function logUserSearch($values, $search_type, $exitcode, $user_id = NULL, $user_ip = NULL) {
        
        // no log if bp (by page)
        if(isset($values['bp'])) {
            return;
        }

        // getting rid of a button
        if (isset($values['sb'])) {
            unset($values['sb']);
        }

        $user_id = ($user_id === NULL) ? $this->user_id : $user_id;
        $user_id = ($user_id) ? $user_id : 0;
        $user_ip = ($user_ip === NULL) ? WebUtil::getIP() : $user_ip;

        $search_str = $values['q'];
        $search_str = RequestDataUtil::stripVars($search_str, array());

        $search_opt = RequestDataUtil::stripVars($values, array(), 'stripslashes');
        $search_opt = addslashes(serialize($search_opt));

        $sql = "INSERT {$this->tbl->log_search} SET
        user_id = '{$user_id}',
        search_type = '{$search_type}',
        search_option = '{$search_opt}',
        search_string = '{$search_str}',
        user_ip = IFNULL(INET_ATON('{$user_ip}'), 0),
        exitcode = '{$exitcode}'";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $this->db->Insert_ID();
    }


    function setFullTextParams($search_type = 1) {

        $in = $this->values['in'];
        $by = $this->values['by'];

        // keywords
        if(!empty($this->values['qs'])) {

            $str = trim($this->values['qs']);
            // if (empty($this->sphinx)) {
                // $str = addslashes(stripslashes($str));
                // $str = str_replace("&#039;", '', $str);
                $str = RequestDataUtil::stripVars($str, array(), 'addslashes');
            // }

            $arr_title = array('title');
            $arr_tag = array('keyword');
            $arr_ids = array('id');
            $arr_author_ids = array('author_id');

            // ids
            if (in_array($by, $arr_ids)) {
                $search = array('#[\.,:;]#', '[ +]');
                $replace = array(',', '');
                $str = preg_replace($search, $replace, $str);

                foreach(explode(',', $str) as $v) {
                    $ids[] = (int)$v;
                }

                $this->filterById($ids);

            // titles
            } elseif (in_array($by, $arr_title)) {
                $this->filterByTitle($str);

            // keywords
            } elseif (in_array($by, $arr_tag)) {

                $tags = str_replace(array('[',']'), '', $str);
                $tags = explode(',', $tags);
                foreach($tags as $k => $v) {
                    $tags[$k] = trim($v);
                }

                $tag_ids = $this->getTagIds($tags);
                if(count($tags) > count($tag_ids)) {
                    $tag_ids[] = 0; // add empty
                }

                $this->filterByTag($tag_ids);

            // user id (author_id)
            } elseif (in_array($by, $arr_author_ids)) {
                $search = array('#[\.,:;]#', '[ +]');
                $replace = array(',', '');
                $str = preg_replace($search, $replace, $str);

                foreach(explode(',', $str) as $v) {
                    $ids[] = (int)$v;
                }

                $this->filterByAuthor($ids);

            // attachment set manually in KBClientSearchEngine_mysql.php
            // } elseif($by == 'attachment_plus') {
                // $this->filterByArticleAttachmentsPlus($str);

            // attachment only, checked in form
            } elseif($by == 'attachment') {
                $this->filterByArticleAttachments($str);

            // filename
            } elseif($by == 'filename') {
                $this->filterByFilename($str);

            //article all
            } elseif($in == 'article') {
                $this->filterArticleByAllFields($str);

            // file all
            } elseif($in == 'file') {
                $this->filterFileByAllFields($str);

            // news all
            } elseif($in == 'news') {
                $this->filterNewsByAllFields($str);
            }

        } else {
            $this->filterEmpty();
        }
    }

    
    /*
    [search_period_range]
    all                = All time
    last_10_day        = Last 10 days
    last_30_day        = Last 30 days
    last_90_day        = Last 90 days
    last_1_year        = Last year
    custom            = Custom period
    */
    function setDateParams() {

        if($this->period == 'all') {
            return;
        }

        switch ($this->period) {
        case 'custom': // ------------------------------
            $this->filterByCustomDate();
            break;

        default:

            //last_10_day...
            $ret = preg_match("/last_(\d+)_(day|week|month|year)/", $this->period, $match);
            if($ret) {
                $this->filterByDate($match);
            }

            break;
        }
    }


    function setEntryTypeParams() {

        if(!empty($this->values['et'])) {
            $c = array();
            foreach($this->values['et'] as $k => $v) {
                $c[$k] = (int) $v;
            }

            if($c) {
                $this->filterByEntryType($c);
            }
        }
    }


    function setCategoryParams($categories) {

        if(isset($this->values['cf']) && $this->values['cf'] != 'all' && $this->values['cf'] != 'top') {
            if(empty($this->values['c'])) {
                $this->values['c'][] = $this->values['cf'];
            }
        }

        // categories
        if(!empty($this->values['c']) && $this->values['c'] != 'all') {
            $c = array();
            if(!is_array($this->values['c'])) {
                $this->values['c'] = array($this->values['c']);
            }

            foreach($this->values['c'] as $k => $v) {
                $c[$k] = (int) $v;
            }

            // all child
            if(!empty($this->values['cp'])) {

                $tree = new TreeHelper();
                foreach($categories as $k => $row) {
                    $tree->setTreeItem($row['id'], $row['parent_id']);
                }

                $child = array();
                foreach($c as $k => $v) {
                    $_child = $tree->getChildsById($v);

                    foreach($_child as $id) {
                        if(!$id) { continue; }
                        $child[] = $id;
                    }
                }

                $c = array_unique(array_merge($child, $c));
            }

            $this->filterByCategory($c);
        }
    }


    static function isSearchInAttachments($values) {
        $ret = false;
        
        if(empty($values['by']) || in_array($values['by'], array('all', 'title', 'attachment'))) {
            if(!empty($values['q']) && empty($values['c']) && (empty($values['period']) || $values['period'] == 'all')) {
                $ret = true;
            }
        }
        
        return $ret;
    }
    
    
    function _getEntryFilterInfo($manager, $select) {
        
        // DebugUtil::timestart('getFilterInfo');
        // $select = 'e.id, e.entry_type, e_to_cat.category_id';
        
        //16-05-2024 added news to custom filter on left 
        if($select === 'news') {
            
            // fix to get correct news custom table
            $news_model = new KBClientNewsModel;
            $this->cf_manager->etable = $news_model->tbl->custom_data;
            
            $sql = $this->_getNewsInfoSql($select, $manager);
        } else {
            $sql = $this->_getEntryInfoSql($select, $manager);
        }
    
        $result = $this->db->SelectLimit($sql, self::$filter_limit) or die(db_error($sql));
        $data = array('entry_type' => [], 'cat' => []);
        $entry_ids = array();
        while($row = $result->FetchRow()) {
            $entry_ids[] = $row['id'];
            if(isset($row['entry_type'])) {
                $this->setQuantity($data, 'entry_type', $row['entry_type']);
            } 
            if(isset($row['cat'])) {
                $this->setQuantity($data, 'cat', $row['category_id']);
            }
        }
        
        unset($data['entry_type'][0]);
        unset($data['cat'][0]);
        $data = array_filter($data);
        
        if($entry_ids && AppPlugin::isPlugin('fields')) {
            $custom = $this->cf_manager->getCustomFieldsToSearchFilter($entry_ids);
            if($custom) {
                $sort = $this->cf_manager->getCustomFieldsToSearchFilterSort();
                foreach($custom as $v) {
                    foreach(explode(',', $v['data']) as $v2) {
                        @$data['custom'][$v['field_id']][$v2] += 1;
                    }
                }
            
                uksort($data['custom'], function($key1, $key2) use ($sort) {
                    return $sort[$key1] > $sort[$key2];
                });
            }
        }
        
        // echo '<pre>' . print_r($data['custom'], 1) . '</pre>';
        //echo '<pre>' . print_r($data, 1) . '</pre>';
        // exit;
        
        // DebugUtil::timestop('getFilterInfo');
        return $data;
    }
    
    
    function getArticleFilterInfo($manager) {
        $select = 'e.id, e.entry_type, e_to_cat.category_id';
        return $this->_getEntryFilterInfo($manager, $select);        
    }
    
    
    function getFileFilterInfo($manager) {
        $select = 'e.id, e_to_cat.category_id';
        return $this->_getEntryFilterInfo($manager, $select);
    }

    
    function getNewsFilterInfo($manager) {
        $select = 'news'; // no matter
        return $this->_getEntryFilterInfo($manager, $select);
    }
    
    
    // to parse info data which displayed in filter on the left
    function setQuantity(&$arr, $field, $value) {
        if(!isset($arr[$field][$value])) { 
            $arr[$field][$value] = 1;
        } else {
            $arr[$field][$value]++;
        }
        
        return $arr;
    }
}
?>