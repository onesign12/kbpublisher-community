<?php

// it works the same as KBClientAction

class KBApiCommon
{

    var $version = 2;
    var $format = 'json';
    var $cache_lifetime = 0; // do not cache, in seconds

    var $entry_id;
    var $category_id;
    var $limit = 10;
    // var $offset = 0;

    var $root_tag = 'result';
	var $root_attributes = array();

	var $replace_utf = false;

	var $map_fields = array();
	var $return_fields = array(); // only these returned if set
	var $remove_fields = array(); // will be removed from recponce
	var $remove_fields_more = array(); // will be added to remove_fields
	var $html_fields = array();


    var $allowed_requests = array(
        'get', 
        //'post'
    );

    var $requests_to_priv_actions_map = array(
        'post'      => 'insert',
        'put'       => 'update',
        'delete'    => 'delete'
    );


    function __construct() {

    }
    

    function setVars(&$controller) {

        $this->rq = $controller->rq;
        $this->rp = $controller->rp;

        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');

        // not implemented
        // $this->replace_utf = Utf8::badUtfLoad($conf['lang']['meta_charset']);

        $this->cc = &$reg->getEntry('controller');
        $this->cv = &$reg->getEntry('view');

        if(isset($this->rq->limit)) {
            $this->limit = (int) $this->rq->limit;
        }

        if(isset($this->rq->page)) {
            $this->rq->page = (int) $this->rq->page;
        }

        if(isset($this->rq->cache)) {
            // $this->cache_lifetime = (int) $this->rq->cache;
        }


        $this->view_id = $controller->call;
        $this->entry_id = $controller->entry_id;
        $this->category_id = $controller->category_id;

        // version
        $this->version = $conf['api_version'];

        // format
        $this->format = $conf['api_format'];

        // fields
        if($f = $controller->getRequestVar('fields')) {
            $this->return_fields = array_map('trim', explode(',', urldecode($f)));
        }

        if($this->remove_fields_more) {
            $this->remove_fields = array_merge($this->remove_fields, $this->remove_fields_more);
        }
    }


    static function factory($controller, $request, $class, $dir) {

        $suffix = '';

        // if($request == 'get') {
            $suffix = 'list';

            if($controller->method) {
                $suffix = ($controller->method == 'search') ? 'search' : 'method';            
            
            } elseif($controller->action) {
                $suffix = $controller->action;

            } elseif($controller->entry_id) {
                $suffix = 'entry';
            }
        // }

        $class = sprintf('%s_%s', $class, $suffix);
        $file = $class . '.php';

        require_once $dir . $file;
        return new $class;
    }


    function getReturnFields($row) {
        foreach($this->map_fields as $k => $v) {
            if(isset($row[$k])) {
                $row[$v] = $row[$k];
                unset($row[$k]);
            }
        }

        if($this->remove_fields) {
            $row = array_diff_key($row, array_flip($this->remove_fields));
        }

        $row = KBApiUtil::camelize($row);
        if($this->return_fields) {
            $row = array_intersect_key($row, array_flip($this->return_fields));
        }

        // remove all numeric keys, could be in search
        $row = array_intersect_key($row, array_flip(array_filter(array_keys($row), 'is_string')));

        return $row;
    }


    function setCategoryId(&$controller, $manager) {

        // goes from $controller->category_id, URL cid = 12
        $category_id = $this->category_id;

        $skip_views = array(
            'news',
            'articleCategories',
            'fileCategories'
            );

        if($this->entry_id && !in_array($this->view_id, $skip_views)) {

            // possible categories entry can belong to
            // not allowed categories here
            $category_ids = $manager->getCategoryIdsByEntryId($this->entry_id);

            // remove all not allowed categories
            $category_ids = array_intersect($category_ids, array_keys($manager->categories));

            // no any category for entry - no such entry
            if(!$category_ids) {
                KBApiError::error404();
            }

            $category_id = current($category_ids);
        }

        $this->category_id = $category_id;
    }



    // it seems we do not need to check private status
    // if user can't access to entry he get 404
    // these cases parsed on conrerte view and redirects to 404 if not access
    function checkPrivate($controller, $manager) {

        $private_views = array(
            'articles'
            );

        if(!in_array($this->view_id, $private_views)) {
            return;
        }

    }


    function validate($controller, $manager) {
        return false;
    }


    // function checkPriv($controller, $manager) {
    //     return false;
    // }


    function getResultAttributesFromBP($by_page) {
        $attributes = array(
            'page'    => $by_page->cur_page,
            'pages'   => $by_page->num_pages,
            'perPage' => $by_page->limit,
            'total'   => $by_page->num_records
        );

        return $attributes;
    }


    function getResultAttributes($cur_page, $num_pages, $limit, $num_records) {
        $attributes = array(
            'page'    => $cur_page,
            'pages'   => $num_pages,
            'perPage' => $limit,
            'total'   => $num_records
        );

        return $attributes;
    }

    
    function getResultAttributesFromAction($id, $status) {
        $attributes = array(
            'id'    => $id,
            'status'   => $status
        );

        return $attributes;
    }


    function setRootAttributes($attributes, $extra = array()) {
        $this->root_attributes = array_merge($attributes, $extra);
    }


    function getRootAttributes() {
        return $this->root_attributes;
    }


    function pageByPage($limit, $num) {

        $bp = PageByPage::factory('page', $limit);
        $bp->get_name = 'page';
        $bp->setGetVars();
        $bp->countAll($num);

        return $bp;
    }


    function getCustomDataApi($rows) {
        
        $data = array();
        if(!$rows) {
            return $data;
        }
        
        $custom_data = array();
        foreach($rows as $field_id => $v) {
            $custom_data[$field_id] = $v['data'];
        }

        $ch_value = array('on' => 1, 'off' => 0);
        $cf_manager = new CommonCustomFieldModel();
        $custom = CommonCustomFieldView::getCustomData($custom_data, $cf_manager, $ch_value);
        
        foreach ($custom as $field_id => $v) {
            $data[] = array(
                'title' => $v['title'], 
                'value' => $v['value'],
                'display' => array_search($rows[$field_id]['display'], CustomFieldModel::$display_option)
            );
        }

        return $data;
    }


    function parseImages($output, $baseUrl) {

        preg_match_all('/<img[^>]+>/i', $output, $match);

        if(!empty($match[0])) {
            
            $baseUrl = rtrim($baseUrl,"/"); // just in case 
            $initial_src = array();
            $new_src = array();
            
            foreach ($match[0] as $tag) {

                preg_match_all('/src="([^"]*)"/i', $tag, $image);

                $src = rawurldecode($image[1][0]);
                $initial_src[] = $src;
                $is_remote = (strpos($src, 'http://') !== false || strpos($src, 'https://') !== false);
                $is_embedded = (strpos($src, 'data:image') !== false);

                if($is_remote || $is_embedded) {
                    continue;
                }

                $new_src[] = $baseUrl . $src;
            }
            
            $initial_src = array_unique($initial_src);
            $new_src = array_unique($new_src);
            $output = str_replace($initial_src, $new_src, $output);
        }

        return $output;
    }
    
    
    function parseEntryUser($user) {
        $fields = array('first_name', 'last_name', 'middle_name');
        return array_intersect_key($user, array_flip($fields));
    }
}
?>