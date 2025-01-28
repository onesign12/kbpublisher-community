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

class SetupLoader
{

    function &getManager($controller) {
        $c = new SetupModel();
        return $c;
    }
    

    function &getView($controller, $manager) {
        
        $suffics = $controller->getStepKey($controller->view_id, 'index');
        
        $class = 'SetupAction_' . $suffics;
        $file  = 'SetupAction_' . $suffics . '.php';
        // $file = $controller->working_dir . 'inc/action/' . $file;
        
        // require_once($file);
        
        $action = new $class;
        $action->setVars($controller, $manager);
        return $action->execute($controller, $manager);
     }
}
?>