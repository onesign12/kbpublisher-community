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


class PageDesignCustomBlockView_form extends AppView
{
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('page_design_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . 'custom_block_form.html');
        
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        $block_id = $this->controller->getMoreParam('block_id');        
        
        if (!empty($block_id)) { //just added 
            $block = $manager->getById($block_id);
            $options = unserialize($block['data_string']);
            
            $tpl->tplAssign('custom_block_id', 'custom_' . $block_id);
            
            $vars = array(
                'id' => $block_id,
                'update_msg' => $this->msg['update_msg'],
                'delete_msg' => $this->msg['delete_msg'],
            );
            
            $vars['title'] = $options['title'];
            if (empty($vars['title'])) {
                $vars['title'] = sprintf('<i>%s #%s</i>', $this->msg['custom_block_msg'], $block_id);
            }
            
            $vars['link'] = $this->getLink('this', 'this', 'this', 'custom_block', array('id' => $block_id));
            
            $custom_block_html = PageDesignView_form::getCustomBlock($vars);
            $custom_block_html = str_replace(array("\r", "\n"), '', addslashes($custom_block_html));
            $tpl->tplAssign('custom_block_html', $custom_block_html);
            
            $tpl->tplAssign('title', $vars['title']);
            $tpl->tplAssign('setting_popup', $this->getLink('this', 'this', 'this', 'custom_block', array('id' => $block_id)));
            
            $tpl->tplSetNeeded('/close_window');
        }
        
        $value = '';
        if (!empty($obj->get('data_string'))) {
            $options = unserialize($obj->get('data_string'));
            $tpl->tplAssign($options);
            
            $value = $options['body'];
        }
        
        $tpl->tplAssign('ckeditor', $this->getEditor($value, 'glossary'));
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign('action_title', ($obj->get('id')) ? $this->msg['update_msg'] : $this->msg['add_msg']);
        
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);
    }

}
?>