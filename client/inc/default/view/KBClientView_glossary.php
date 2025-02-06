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


class KBClientView_glossary extends KBClientView_common
{

    var $display_letters = true;


    function &execute(&$manager) {
        
        $this->home_link = true;
        $this->parse_form = false;
        $this->meta_title = $this->msg['menu_glossary_msg'];
        $this->nav_title = $this->msg['menu_glossary_msg'];
        
        $data = &$this->getGlossaryList($manager);
        
        return $data;
    }
    
    
    function &getGlossaryList($manager) {
        
        $tpl = new tplTemplatez($this->getTemplate('glossary_list.html'));
        
        $limit = 30;
        
        $letters = array();
        $_letter = (isset($_GET['let'])) ? $_GET['let'] : false;
        $letter_param = ($this->controller->mod_rewrite) ? '?let=%s' : '&let=%s';
        
        $mysql = array();
        $sphinx = array();
        
        if($this->display_letters) {
            
            $result =& $manager->getGlossaryLettersResult();
            while($row = $result->FetchRow()) {
                $letter = _strtoupper(_substr($row['phrase'], 0, 1));
                $letters[$letter] = $letter;
            }
        
            if(count($letters) > 1 || ($_letter !== false)) {
            
                $tpl->tplSetNeededGlobal('letters');
                
                $popup_id = sprintf('chooseGlossaryLetter_%d', time());
                $tpl->tplAssign('popup_id', $popup_id);
                
                //SORT_LOCALE_STRING - compare items as strings, based on the current locale. 
                //Added in PHP 4.4.0 and 5.0.2. Before PHP 6, it uses the system locale, 
                // which can be changed using setlocale(). 
                //Since PHP 6, you must use the i18n_loc_set_default() function.
                sort($letters, SORT_LOCALE_STRING);
                
                foreach($letters as $letter) {
					$a = array();
                    $a['letter'] = $letter;
                    $a['letter_link'] = $this->getLink('glossary') . sprintf($letter_param, urlencode($letter));
                    
                    if ($_letter === $letter) {
						$a['letter_active'] = 'font-weight: bold; background-color: #DADADA;';
                    }
                    
                    $tpl->tplParse($a, 'letter');
                }
                
                $tpl->tplAssign('all_letter_link', $this->getLink('glossary'));
            }
            
            if($_letter !== false) {
                $l = addslashes(urldecode(_strtoupper($_letter)));
                $l2 = addslashes(urldecode(_strtolower($_letter)));
                
                $manager->setSqlParams("AND (phrase LIKE '$l%' OR phrase LIKE '$l2%')");
                
                $tpl->tplAssign('letter', $_letter);
            }
        }
        
        $bp_hidden = false;
        if(isset($_GET['qf'])) {
            $str = addslashes(stripslashes($_GET['qf']));
            $bp_hidden = array('qf' => $_GET['qf']);
            
            $mysql['where'] = "AND phrase LIKE '{$str}%'";
            $sphinx['match'] = $str;
        }
        
        $sort_param = 'ORDER BY phrase ASC';
        if (!empty($mysql)) {
            $q = (isset($str)) ? $str : $l;
            
            if(SphinxModel::isSphinxOnSearch($q)) {
                $options = array('index' => 'glossary', 'limit' => $limit, 'sort' => $sort_param);
                $params = KBClientSearchModel_sphinx::parseFilterSql($sphinx, $options);
                
                $manager->setSqlParams($params['where']);
            
                if (!empty($params['sort'])) {
                    $sort_param = $params['sort'];
                }
                
                if (!empty($params['count'])) {
                    $count = $params['count'];
                }
                
            } else {
                $manager->setSqlParams($mysql['where']);
            }
        }
        
        $manager->setSqlParamsOrder($sort_param);
        
        $action_link = $this->getLink('glossary');
        if($_letter !== false) {
            $action_link .= sprintf($letter_param, urlencode($_letter));
        }
        
        if (!isset($count)) {
            $count = $manager->getGlossarySql();
        }
        
        $by_page = $this->pageByPage($limit, $count, $action_link, false, $bp_hidden);
        $rows = $manager->getGlossary($by_page->limit, $by_page->offset);
        $rows = $this->stripVars($rows, array('definition'));

        foreach($rows as $k => $v) {
            DocumentParser::parseCurlyBracesSimple($v['definition']);
            $tpl->tplParse($v, 'row');
        }
        

        $link = $this->getLink('print-glossary', false, $by_page->offset);
        if($_letter) {
            $link .= sprintf($letter_param, urlencode($_letter));
        }
        
        $tpl->tplAssign('print_link', $link);
        
        $form_hidden = '';
        if(!$this->controller->mod_rewrite) {
            $arr = array($this->controller->getRequestKey('view') => 'glossary');
            $form_hidden = http_build_hidden($arr, true);
        }

        $tpl->tplAssign('hidden_search', $form_hidden);
        $tpl->tplAssign('form_search_action', $this->getLink('glossary'));
        
        if(isset($_GET['qf'])) {
            $tpl->tplAssign('qf', $this->stripVars(trim($_GET['qf']), array(), 'asdasdasda'));    
        }
        
        // by page
        if($by_page->num_pages > 1) {
            $tpl->tplAssign('page_by_page_bottom', $by_page->navigate());
            $tpl->tplSetNeeded('/by_page_bottom');            
        }
        
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);
    }
    
}
?>