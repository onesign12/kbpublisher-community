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


class SettingViewWizard extends AppView
{
    
    var $tmpl = 'form_wizard_page.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('start_wizard_msg.ini');
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $next_group = @$manager->wizard_groups[$manager->wizard_group_id + 1];
        if ($next_group) {
            $tpl->tplSetNeeded('/next_button');
            
            $more = array(
                'group' => $next_group, 
                'popup' => $this->controller->getMoreParam('popup')
            ); 
            $link = $this->getLink('this', 'this', false, false, $more);
            $link = $this->controller->_replaceArgSeparator($link);
            $tpl->tplAssign('next_group_link', $link);
            
        } else {
            $tpl->tplSetNeeded('/done_button');
			
			if (!$this->controller->getMoreParam('popup')) {
				$link = $this->getLink('this', 'this', false, false, array('done' => 1)); 
				$action = sprintf("document.location.href = '%s'", $link);
			} else {
                $link = $this->getLink('home', 'home', false, false, array('done' => 1)); 
			    $action = sprintf("finishWizard('%s');", $link);
			}
                
			$tpl->tplAssign('done_action', $action);
        }
        
        if ($manager->wizard_group_id == 1) {
            // $tpl->tplAssign('hint', AppMsg::hintBoxCommon('start_wizard'));
            
        } else {
            $tpl->tplSetNeeded('/prev_button');
            
            $prev_group = $manager->wizard_groups[$manager->wizard_group_id - 1];
            $more = array(
                'group' => $prev_group, 
                'popup' => $this->controller->getMoreParam('popup')
            ); 
                
            $link = $this->getLink('this', 'this', false, false, $more);
            $link = $this->controller->_replaceArgSeparator($link);
            $tpl->tplAssign('prev_group_link', $link);
            
            //xajax
            $ajax = &$this->getAjax($obj, $manager);
            $xajax = &$ajax->getAjax();
            
            $more = array('group' => $_GET['group']);
            $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more));
        }
        
        $group = @addslashes($_GET['group']);
        $view = self::factory($group);
        
        $content = $view->execute($obj, $manager);
        $tpl->tplAssign('content', $content);
        
        $this->parseProgressTracker($tpl, $manager, $this->msg);

        $tpl->tplAssign($this->msg);
        $tpl->tplParse();
        
        return $tpl->tplPrint(1);
    }


    static function factory($group) {
        
        $class = 'setting';
        if (in_array($group, array('view', 'test'))) {
            $class = $group;
        }
        
        $class = 'SettingViewWizard_' . $class;
        require_once $class . '.php';
        
        $view = new $class;
        return $view;
    }


    static function parseProgressTracker($tpl, $manager, $msg) {
        
        $group_id = $manager->wizard_group_id;
        $group = $manager->wizard_groups[$group_id];
        $width = ceil(100 / count($manager->wizard_groups));
        
        foreach ($manager->wizard_groups as $k => $g) {
            $v['width'] = $width . '%';
            $v['group_title'] = $msg['group_' . $g]['title'];
            $v['active_class'] = ($k == $group_id) ? 'class="active_group"' : '';
            
            $v['icon'] = ($k > $group_id) ? 'circle_grey' : 'circle_grey_filled'; 
            if ($k == $group_id) {
                $v['icon'] = 'circle';
            }
            
            $tpl->tplParse($v, 'group_tab');
        }
        
        $tpl->tplAssign('group_title', $msg['group_' . $group]['title']);
        $tpl->tplAssign('group_desc', $msg['group_' . $group]['desc']);
    }
}
?>