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

class EmailBoxView_list extends AppView
{
        
    var $template = 'list.html';
    var $template_popup = 'list_popup.html';
    
    
    function execute(&$obj, &$manager) {
    
        $this->addMsg('trigger_msg.ini');
        
        $tmpl = ($this->controller->getMoreParam('popup')) ? $this->template_popup :  $this->template;
        $tpl = new tplTemplatez($this->template_dir . $tmpl);
        
        
        if($mailbox_id = (int) $this->controller->getMoreParam(array('filter', 'mid'))) {
            $data = $manager->getById($mailbox_id);
            $options = unserialize($data['data_string']);
            $title = (!empty($options['title'])) ? $options['title'] : $options['host'];
            
            $tpl->tplAssign('id', $mailbox_id);
            $tpl->tplAssign('title', addslashes($title));
            
            $tpl->tplSetNeeded('/assign_mailbox');
        }
        
        $manager->setSqlParams(sprintf('AND data_key = "%s"', $obj->get('data_key')));
        
        // header generate
        $bp =& $this->pageByPage($manager->limit, $manager->getRecordsSql());     
        $tpl->tplAssign('header', $this->titleHeaderList($bp->nav, $this->msg['email_boxes_msg']));
        
        // get records
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset), array('data_string'));
        $ids = $manager->getValuesString($rows, 'id');
        
        $tooltip_host_map = array(
            'hostname_msg' => 'host',
            'port_msg' => 'port',
            'mailbox_msg' => 'mailbox',
            'user_msg' => 'user',
        );
        
        foreach($rows as $row) {
            $obj->set($row);
            
            $options = unserialize($row['data_string']);
            unset($options['password']);
            
            if (empty($options['title'])) {
                $options['title'] = $options['host'];
            }
            
            $tooltip_host = array();
            $tooltip_host[] = sprintf('<strong>%s</strong>', $options['host']);
            $tooltip_host[] = sprintf('%s: %s', $this->msg['port_msg'], $options['port']);
            $tooltip_host[] = sprintf('%s: %s', $this->msg['mailbox_msg'], $options['mailbox']);
            
            $tooltip_host = implode("<br/>", $tooltip_host);
            $tpl->tplAssign('tooltip_host', addslashes($tooltip_host));
            
            $tpl->tplAssign('escaped_name', addslashes(htmlspecialchars($options['title'])));
            
            $tpl->tplAssign($this->getViewListVarsJs($obj->get('id'),1,1,array('update','delete')));
            $tpl->tplParse(array_merge($obj->get(), $options, $this->msg), 'row');
        }
        
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>