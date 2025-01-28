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


class SetupAction_success extends SetupAction
{

    function &execute($controller, $manager) {
        
        // reset config checking
        $manager->setSetupData(array('config_file_check' => 0));
        
        $view = $controller->getView();
        return $view;
    }
}
?>