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
        

class KBClientView_entry_add extends KBClientView_entry_emode
{

    function &execute(&$manager) {
        
        $this->nav_title = sprintf('[%s]', $this->msg['menu_add_article_here_msg']);
        $this->meta_title = $this->stripVars($manager->getCategoryTitle($this->category_id));
        $this->meta_title .= ' ' . $this->nav_title;
        
        $data = array();
        
        if ($this->emanager->isAutosaved(0, '2011-01-01')) {
            $more = array('referer'=>'client');
            $vars['link'] = $this->controller->getAdminRefLink('knowledgebase', 'kb_autosave', false, false, $more);
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);
            $msg['title'] = '';
            $msg['body'] = $msgs['note_entry_draft'];
        
            $data[] = BoxMsg::factory('hint', $msg, $vars);
        }
        
        $manager->setting['entry_published'] = 1;
        
        $data[] = $this->getEntryForUpdate($manager);
        $data = implode('', $data);
        
        return $data;
    }
    
    
    function getEntryForUpdate(&$manager) {
        
        $tpl = new tplTemplatez($this->getTemplate('article_bb_emode.html'));
        
        $tpl->tplAssign('action_link', $this->getLink('all'));
        
        $hidden = array('body' => '', 'title' => '');
        $hidden += self::getCsrfTokenArray(true);
        $tpl->tplAssign('hidden_fields', http_build_hidden($hidden));
        
        $ajax = &$this->getAjax('emode');
        $ajax->view = &$this;
        $xajax = &$ajax->getAjax($manager);
        
        $xajax->registerFunction(array('validateEntry', $ajax, 'validateEntry'));
        $xajax->registerFunction(array('cancelHandler', $ajax, 'cancelHandler'));
        
        $categories = $this->emanager->getCategoryRecords();
        $custom_fields = $this->emanager->cf_manager->getCustomField($categories, array($this->category_id));
        
        $custom_rows = array();
        foreach ($custom_fields as $custom_id => $row) {
            if (strlen($row['default_value']) && !($row['input_id'] == 5 && $row['default_value'] == 0)) {
                $custom_rows[$custom_id] = $row;
                $custom_rows[$custom_id]['data'] = $row['default_value'];
            }
        }
        $custom_data = $this->getCustomData($custom_rows);
        
        if (!empty($custom_data)) {
            $tpl->tplAssign('custom_tmpl_top', $this->parseCustomData($custom_data[1], 1));
            $tpl->tplAssign('custom_tmpl_bottom', $this->parseCustomData($custom_data[2], 2));
        }
        
        $admin_path = $this->controller->getAdminJsLink();
        $tpl->tplAssign('config_path', $admin_path . 'tools/ckeditor_custom/ckconfig_article.js');
        
        $setting = SettingModel::getQuick(1);
        if ($setting['entry_autosave']) {
            $xajax->registerFunction(array('autoSave', $ajax, 'autoSave'));
            $xajax->registerFunction(array('deleteAutoSave', $ajax, 'deleteAutoSave'));
            
            // draft key
            if(!empty($_POST['id_key'])) {
                $id_key = htmlentities($_POST['id_key'], ENT_QUOTES);
            } else {
                $id_key = array($manager->user_id, 1, null, 'insert', time()); 
                $id_key = md5(serialize($id_key));
            }
            
            $tpl->tplAssign('autosave_key', $id_key);
            
            $autosave_period = 60000 * $setting['entry_autosave']; 
            $tpl->tplAssign('autosave_period', $autosave_period);
            
            $tpl->tplSetNeeded('/auto_save');
        } 
        
        $link = $this->controller->getAdminRefLink('knowledgebase', 'kb_entry', false, 'insert', array('emode' => 1, 'popup' => 1));
        $tpl->tplAssign('more_options_link', $link);
        
        $tpl->tplAssign('cancel_link', $this->getLink('entry', $this->category_id, $this->entry_id));
        
        $tpl->tplAssign('category_id', $this->category_id);
        $tpl->tplAssign('cancel_link', $this->getLink('index', $this->category_id));
        
        $tpl->tplAssign('save_button_class', 'button');
        $tpl->tplAssign('save_button_attr', '');
        $tpl->tplAssign('save_button_text', $this->msg['publish_msg']);
        
        $tpl->tplSetNeeded('/button_text');
        
        
		$items = array(
            'save' => array(
                'text' => $this->msg['publish_msg'],
                'action' => 'submitFormAjax()',
                'name' => 'save_button'
            )
		);
    
        $options = array(
            // 'button' => true, 
            'class' => 'primary',
            // 'class' => 'button buttonDisabled', 
            // 'attribute' => 'disabled="true"'
        );
        $tpl->tplAssign('split_button', AppView::getSplitButton($items, $options));
        
        
        
        $tpl->tplAssign('body', sprintf('<div style="color: #777777;">%s</div>', $this->msg['article_here_msg']));
        
        if (!empty($_GET['CategoryIDs'])) {
            $cats = array($this->category_id);
            $this->eobj->setCategory(array_merge($cats, $_GET['CategoryIDs']));
        }
        
        $entry_blocks = array();
        $entry_blocks[] = $this->getEntryListCustomField($custom_data[3]);
        $entry_blocks[] = $this->getEditableTagBlock($manager);
        $entry_blocks[] = $this->getEditableAttachmentBlock($manager);
        $entry_blocks[] = $this->getEditableRelatedBlock($manager);
        $entry_blocks[] = $this->getEditableCategoryBlock($manager);
        $entry_blocks[] = $this->getEditableCustomFieldBlock($custom_rows, $custom_fields);
        $entry_blocks[] = $this->getEditableAdvancedBlock($manager, array());
        
        $entry_blocks = implode('', $entry_blocks);
        $tpl->tplAssign('entry_blocks', $entry_blocks);
        
        $tpl->tplAssign('sure_leave_msg', RequestDataUtil::stripVars($this->msg['sure_leave_msg']));
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
}
?>