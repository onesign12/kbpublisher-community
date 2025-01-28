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

class BulkFileEntry extends FileEntry
{
    
    function validate($values, $action, $manager) {
        
        $v = new Validator($values, true);
        $v->csrf();

        if(empty($values['files'])) {
            $v->setError('no_files_selected_msg', 'files');
        }

        $as_draft = isset($values['submit_draft']);

        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;

        } elseif (!$as_draft) {
            // return $this->validate($values, 'local', $manager);
            return parent::validate($values, 'local', $manager);
        }
        
    }
    
}
?>