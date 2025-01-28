<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KPublisher - web based knowledgebase publishing tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2010 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+

namespace SphinxSetting;

use Validator;
use BaseModel;
use SphinxModel;
use AppMsg;

class SettingValidator
{
     
    function validate($values) {
             
        $required = array(
            'sphinx_host',
            'sphinx_port',
            'sphinx_data_path',
            'sphinx_lang');
			
		if(BaseModel::isCloud()) {
	        $required = array('sphinx_lang');
		}
		
        
        $v = new Validator($values, true);

        $v->required('required_msg', $required);        
        if($v->getErrors()) {
            return $v->getErrors();
        }
        
        if(isset($values['sphinx_data_path'])) {
	        if(!is_dir(dirname($values['sphinx_data_path']))) {
	            $v->setError('dir_not_exists_msg', 'sphinx_data_path');           
	        }	
        
	        // sphinx doesn't understand spaces in names in the "stopwords" directive (ver 2.2.11)
	        if (strpos($values['sphinx_data_path'], ' ')) {
	            $v->setError('dir_spaces_msg', 'sphinx_data_path');
	        }
		}
        
        return $v->getErrors();
    }


    static function validateConnection($values) {

        $sphinx = SphinxModel::connect(false, $values);

        $ret = ($sphinx) ? true : AppMsg::getMsgs('error_msg.ini', 'sphinx_setting', 'sphinx_connect');
        
        return $ret;
    }
	
}
?>