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

// NOT USED but we may implement this 



class SettingView_popup extends SettingView_form
{
    
    
    function execute(&$obj, &$manager, $extra_options = array()) {
        
        $parser = &$manager->getParser();
        $setting_msg = $parser->getSettingMsg($manager->module_name);
        
        $form_data = $this->parseMultiIni($this->template_dir . 'form.ini');
        $r = new Replacer();
        
        
        $tpl = new tplTemplatez($this->template_dir . 'form.html');
        
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors, $manager->module_name));
        // $tpl->tplAssign('js_error', $this->getErrorJs($obj->errors));

        $rows = &$manager->getRecords();
        // echo '<pre>', print_r($rows,1), '</pre>';
        
        $fname = $this->controller->getMoreParam('popup');
        $fid = $this->controller->getMoreParam('field_name');
        

        
        if(!empty($_GET['saved']) && !$obj->errors) {
            $tpl->tplSetNeeded('/close_window');

        }
        
        $vars = $this->setCommonFormVars($obj);
        $tpl->tplAssign($vars);
        $tpl->tplAssign($this->msg);       
        
        $tpl->tplParse();
        
        return $tpl->tplPrint(1);
    }

}
?>