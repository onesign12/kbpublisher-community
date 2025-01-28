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

class CompanyView_list extends AppView
{
        
    var $template = 'list.html';
    var $template_popup = 'list_popup.html';
    var $columns = array('id','title','www','email','phone','user_num');
    var $columns_popup = array('id', 'title', 'www');
    
    
    function execute(&$obj, &$manager) {
    
        $this->addMsg('user_msg.ini');
        
        
        $list = new ListBuilder($this);
        $tmpl = $list->getTemplate();
        
                
        $tpl = new tplTemplatez($tmpl);
        
        // filter
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params);

        // header generate
        $bp =& $this->pageByPage($manager->limit, $manager->getCountRecordsSql());     
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager)));
        
        // sort generate
        $sort = $this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        // get records
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        $ids = $manager->getValuesString($rows, 'id');
        
        // $users_num = ($ids) ? $manager->getUsersNum($ids) : array();
        $countries = $manager->getCountries();
        $states = $manager->getStateSelectRange();
        
        foreach($rows as $row) {
            
            $obj->set($row);
            
            // $row['user_num'] = (isset($users_num[$row['id']])) ? $users_num[$row['id']] : '';
            $row['user_num'] = ($row['user_num']) ? $row['user_num'] : '';
            $row['country_iso'] = ($row['country']) ? $countries[$row['country']]['iso2'] : '';
            $row['country_title'] = ($row['country']) ? $countries[$row['country']]['title'] : '';
            $row['state'] = ($row['state']) ? $states[$row['state']] : '';
            
            $more = array('filter[comp]' => $obj->get('id'));
            $link = $this->getLink('users', 'user', false, false, $more);
            $row['user_link'] = $link;
            
            $row += $this->getTitleToList($row['title'], 100);
            $row['escaped_title'] = addslashes($row['title']);
            $row['mailto'] = ($row['email']) ? sprintf('mailto:%s', $row['email']) : '';
            
            if($row['url']) {
                $pref = (strpos($row['url'], 'https://') !== false) ? 'https://' : 'http://';
                $row['www_link'] = $pref . str_replace($pref, '', $row['url']);
                $row['www_title'] = str_replace(array('https://', 'http://'), '', $row['url']);
            }
            
            $row += $this->getViewListVarsJs($obj->get('id'),$obj->get('active'),1,array('update','delete'));
            
            $tpl->tplAssign('list_row', $list->getRow($row));
            $tpl->tplAssign($this->msg);
            $tpl->tplParse($row, 'row');
        }
        
        // close popup and assign company
        $popup = $this->controller->getMoreParam('popup');
        $new_id = (int) $this->controller->getMoreParam('nid');
        if ($popup && $new_id) {
            $ncompany = urldecode(urldecode($this->controller->getMoreParam('ncompany')));
            $tpl->tplAssign('new_id', $new_id);
            $tpl->tplAssign('new_company', $ncompany);
            $tpl->tplSetNeeded('/assign_company');
        }
        
        
        $tpl->tplAssign($list->getListVars($sort->toHtml(), $this->msg));
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        $sort->setCustomDefaultOrder('user_num', 2);
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        
        $sort->setSortItem('title_msg', 'title', 'c.title', $this->msg['title_msg'], 1);
        $sort->setSortItem('www_msg', 'www', 'c.url', $this->msg['www_msg']);
        $sort->setSortItem('users_msg','user_num', 'user_num', $this->msg['users_msg']);
        
        return $sort;
    }
    
    
    function getFilter($manager) {
        
        $values = $this->parseFilterVars(@$_GET['filter']);
        
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');

        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql($manager) {

        $arr = array();    
        
        @$values = $_GET['filter']; 
        
        // search str
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);
            $v = addslashes(stripslashes($v));
            $_v = str_replace('*', '%', $v);
            
            $sql_str = "%s LIKE '%%%s%%'";
            $f = array(
                'c.title', 'c.email', 'c.phone', 'c.phone2', 'c.fax', 'c.address', 
                'c.address2', 'c.city', 'c.zip', 'c.description', 'c.url');
            foreach($f as $field) {
                $sql[] = sprintf($sql_str, $field, $_v);
            }
            
            $arr[] = 'AND (' . implode(" OR \n", $sql) . ')';
        }

        $arr = implode(" \n", $arr);

        return $arr;
    }
    
    
    // define extra list fields
    // we can change any value for buit-in fields
    function getListColumns() {
        
        $options = array(
            
            'id',

            'title' => array(
                'type' => 'text_tooltip',
                'params' => array(
                    'title' => 'title_title', 
                    'text' => 'title_entry')
            ),

            'www' => array(
                'type' => 'link',
                'params' => array(
                    'text' => 'www_title',
                    'link' => 'www_link',
                    'options' => '%target="_blank"%')
            ),

            'email' => array(
                'type' => 'link',
                'params' => array(
                    'link' => 'mailto')
            ),

            'phone',

            'country' => array(
                'type' => 'text_tooltip',
                'width' => 100,
                'params' => array(
                    'text' => 'country_iso',
                    'title' => 'country_title')
            ),

            'city',
            'state',
            'zip',
            'user_num'
        );

        return $options;
    }
}
?>