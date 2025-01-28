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


class EmailParserEntryView_list extends CommonTriggerEntryView
{
    
    var $tmpl = 'list.html';
    
    function __construct() {
        parent::__construct();
        $this->template_dir = APP_PLUGIN_DIR . 'automation/template/';
    }
    
    
    function _parseExtraBlocks($tpl, $manager) {
        
        $tpl->tplSetNeededGlobal('email_automation');
        
        $mailboxes = $manager->getMailboxSelectRange();
        $mailbox_id = (int) @$_GET['filter']['mid'];
        
        if (empty($mailbox_id)) { // redirecting
            if (count($mailboxes) > 0) {
                $first_mailbox_id = key($mailboxes);
                $url = $this->controller->getCommonLink() . '&filter[mid]=' . $first_mailbox_id;
                
                header("location:" . $this->controller->_replaceArgSeparator($url));
                exit();
            }
            
        } elseif ($mailbox_id != 'all') {
            
            $mailbox_str = 's:10:"mailbox_id";s:%d:"%s";';
            $mailbox_str = sprintf($mailbox_str, strlen($mailbox_id), $mailbox_id);
            
            $manager->setSqlParams(sprintf("AND options LIKE '%%%s%%'", $mailbox_str));
        }
        
        
        if (count($mailboxes) > 1) { // buttons
            $tabs = array();
            
            foreach ($mailboxes as $id => $title) {
                $more = array('filter[mid]' => $id);
                $tabs[$id] = array(
                    'link' => $this->getLink('this', 'this', 'this', false, $more),
                    'title'  => $title
                );
            }
            
            $options['equal_attrib'] = array('filter', 'mid');
            $tpl->tplAssign('mailbox_buttons', $this->getViewEntryTabs(false, $tabs, true, $options));
        }
    }
    
    
    function _getButtons($rows_by_group) {
        $button = array();
        
        $mailbox_id = (int) @$_GET['filter']['mid'];
        
        if ($mailbox_id) {
            $button[] = $this->getLink('this', 'this', 'this', 'insert', array('filter[mid]' => $mailbox_id));
            
        } else {
            $button[] = 'insert';
        }

        // more options
        if($this->priv->isPriv('update')) {
            
            $pmenu = array();
            
            $disabled = (count($rows_by_group[1]) < 2) || ($mailbox_id == 'all');
            $pmenu[] = array(
                'msg' => $this->msg['reorder_msg'], 
                'link' => 'javascript:xajax_getSortableList();void(0);',
                'disabled' => $disabled
                );

            $pmenu[] = array(
                'msg' => $this->msg['defaults_msg'], 
                'link' => 'javascript:resetToDefault();'
                );
                
            $pmenu[] = array(
                'msg' => $this->msg['email_boxes_msg'], 
                'link' => $this->getLink('tool', 'automation', 'email_box')
                );
                
            $button['...'] = $pmenu;
        }
        
        return $button;
    }
}
?>