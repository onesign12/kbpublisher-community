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


class KBClientAjax_validate extends KBClientAjax
{
    
    function ajaxValidateForm($values, $options = array()) {
        
        $objResponse = new xajaxResponse();
        if (!empty($values['_files'])) {
            $_FILES = $values['_files'];
        }
        
        $objResponse->script('$("select, input, textarea, password").removeClass("validationError");');
        $objResponse->script('$("#growls").empty();');
        
        $func = (empty($options['func'])) ? 'getValidate' : $options['func'];
        $ov = $this->view->$func($values);
        
        if($key = array_search('manager', $ov['options'])) {
            $ov['options'][$key] = $this->manager;
        }
        
		
        // $errors = false;// to disable ajax validate 
        $errors = call_user_func_array($ov['func'], $ov['options']);
        if ($errors) {
            $error_fields = array();
            foreach($errors as $type => $num) {
                foreach($num as $v) {
                    $msg = AppMsg::getErrorMsgs();
                    
                    $error_msg = ($type == 'custom') ? $v['msg'] : $msg[$v['msg']];
                    $error_msg = preg_replace("/\r\n|\r|\n/", '<br />', trim($error_msg));
                    
                    if (is_array($v['field'])) {
                        foreach ($v['field'] as $v1) {
                            $error_fields[$v1] = array(
                                'key' => $v['rule'],
                                'msg' => $error_msg
                            );
                        }
                        
                    } else {
                        $error_fields[$v['field']] = array(
                            'key' => $v['rule'],
                            'msg' => $error_msg
                        );
                    }
                    
                    if ($v['rule'] == 'captcha' && !empty($values['captcha'])) { // reloading
                        $captcha_src = sprintf('%s?%s', CaptchaManager::getCaptchaSrc(), mt_rand());
                        $objResponse->script(sprintf('$("#captcha_img").attr("src", "%s");', $captcha_src));
                    }
                }
            }
            
            foreach(array_keys($error_fields) as $k) {
                $error_fields[$k]['msg'] = RequestDataUtil::safeHtmlentitiesGrowl($error_fields[$k]['msg']);
            }
            
            $objResponse->call('ErrorTooltipster.highlight', $error_fields);
            
        } else {
            if (!empty($options['callback'])) {
                if ($options['callback'] != 'skip') {
                    $objResponse->call($options['callback']);
                }
                
            } else { // default behaviour
                $button_name = (empty($options['button_name'])) ? 'submit' : $options['button_name'];
                
				$script = '$("button[name=%s]").attr("data-title", $("button[name=%s]").text());';
				$script = sprintf($script, $button_name, $button_name);
				$objResponse->script($script);
				
                $script = '$("button[name=%s]").html("<img src=\'%sclient/images/ajax/ellipsis.gif\' />");';
                $script = sprintf($script, $button_name, $this->view->controller->kb_path);
                $objResponse->script($script);
                
                $script = sprintf('$("button[name=%s]").removeAttr("onClick").click();', $button_name);
                $objResponse->script($script);
            }
            
        }
        
        return $objResponse;   
    }

}
?>