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


class EmailParserEntry extends AppObj
{
    
    var $properties = array('id'             => NULL,
                            'entry_type'     => 1, // articles
                            'trigger_type'   => 0,
                            'trigger_key'    => '',
                            'title'          => '',
                            'options'        => '',
                            'cond_match'     => 2,
                            'cond'           => '',
                            'action'         => '',
                            'schedule'       => '',
                            'active'         => 1
                            );
    
    
    var $hidden = array('id', 'entry_type', 'trigger_type', 'trigger_key');
    var $reset_on_clone = array('id', 'title', 'trigger_key', 'active');
    
    var $condition = array();
    var $action = array();
    

    function _callBack($property, $val) {
        if($property == 'active' && $val === NULL) { // for reset_on_clone
            $val = 1;
        }
        
        return $val;
    }    
    
    
    function validate($values, $manager) {
        
        $required = array('title', 'mailbox_id', 'cond', 'action');
        
        $v = new Validator($values, false);
        $v->csrf();

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
        
        
        if (!empty($_GET['id'])) {
            $data = $manager->getById($_GET['id']);
            $saved_actions = TriggerParser::unpack($data['action']);
        }

        // conditions
        foreach ($values['cond'] as $num => $cond) {
            $non_empty_conditions = array('from', 'to', 'cc', 'subject', 'body');
            if (in_array($cond['item'], $non_empty_conditions) && strlen($cond['rule'][1]) == 0) {
                $v->setError('empty_expression_msg', 'more_condition_' . $num);
            }
        }
        
        // actions
        foreach ($values['action'] as $num => $action) {
            if ($action['item'] == 'stop') {
                continue;
            }
            
            $type = substr($action['item'], 7);
            
            if (empty($_SESSION['email_rule_'][$values['id_key']][$num][$type])) {
                
                if (empty($_GET['id'])) {
                    $v->setError('entry_not_set_msg', 'more_action_' . $num);
                    $this->errors =& $v->getErrors();
                    return true;
                
                } elseif (empty($saved_actions[$num]) || ($saved_actions[$num]['item'] != 'create_' . $type)) {
                    $v->setError('entry_not_set_msg', 'more_action_' . $num);
                    $this->errors =& $v->getErrors();
                    return true;
                }
            }
        }
        
        if($v->getErrors()) {
			$this->errors =& $v->getErrors();
			return true;
		}
    }
    
}
?>