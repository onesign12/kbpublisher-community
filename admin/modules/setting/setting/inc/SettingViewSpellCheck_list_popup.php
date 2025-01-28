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



class SettingViewSpellCheck_list_popup extends AppView
{
        
    var $tmpl = 'list_spell_check.html';
    
    
    function execute(&$obj, &$manager) {
    
        $this->addMsg('common_msg.ini', 'public_setting');
        
    
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $spell_checker = $obj->get('search_spell_suggest');
        
        // error
        if($spell_checker) {
            $checkers = array('pspell', 'enchant'); 
            if(in_array($spell_checker, $checkers)) {
                $method = sprintf('validate%s', ucwords($spell_checker));
                $ret = PublicSetting\SettingValidator::$method($obj->get());
                if(is_array($ret)) {
                    $msg = ucwords($spell_checker) . ' - ' . $ret['code_message'];
                    $msg_vars = array('body' => $msg);
                    $tpl->tplAssign('error_msg', BoxMsg::factory('error', $msg_vars));    
                }
            }
        }
        
        if(!empty($_GET['bad_url'])) {
            $msg_vars = array('body' => $this->msg['bing_bad_url_msg']);
            $tpl->tplAssign('error_msg', BoxMsg::factory('error', $msg_vars));
        }
        
        if(!empty($_GET['saved']) && !empty($_GET['tkey'])) {
            $tpl->tplAssign('test_key', $_GET['tkey']);
            $tpl->tplSetNeeded('/close_window');
        }
        
        $links = array(
            'enchant' => 'http://php.net/manual/en/book.enchant.php',
            'pspell' => 'http://php.net/manual/en/book.pspell.php',
            'bing' => 'https://azure.microsoft.com/en-us/services/cognitive-services/'
        );
        
        $sources = SpellSuggest::getSources();
        
        foreach($sources as $source) {
            
            $row = array();
            $row['title'] = $this->msg[$source . '_title_msg'];
            
            $desc = $this->msg[$source . '_desc_msg'];
            $link = sprintf('<a href="%s" target="_blank"> &#x21E7; </a>', $links[$source]);
            $row['desc'] = str_replace('{link}', $link, $desc);
            
            $more = array('popup' => 'search_spell_suggest', 'source' => $source);
            if ($this->controller->getMoreParam('tkey')) {
                $more['tkey'] = $this->controller->getMoreParam('tkey');
            }
            
            $link = $this->getLink('setting', 'public_setting', 'kbc_setting', false, $more);
            $row['settings_link'] = $link;
            
            $vars = $this->getViewListVarsJs();
            
            $primary_img = ($source == $spell_checker) ? 'active_1' : 'active_0';
            $vars['active_img'] = $this->getImgLink(false, $primary_img, false);
            
            $tpl->tplAssign($vars);
            
            $tpl->tplParse(array_merge($row, $this->msg), 'row');
        }
        
        $tpl->tplAssign('popup_title', $this->msg['spell_suggest_title_msg']);
        $tpl->tplAssign($this->msg);
        
        if(!empty($_GET['saved']) && !$obj->errors) {
            $tpl->tplSetNeeded('/set_status');
            
            $status = 'true';
            
            if (!$obj->get('search_spell_suggest')) {
                $status = 'false';
            }
            
            $tpl->tplAssign('status', $status);
        }
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>