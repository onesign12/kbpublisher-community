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

class SetupAction_index extends SetupAction
{

    function &execute($controller, $manager) {
        
        $view = $controller->getView();
        
        if(isset($this->rp->setup)) {
            if(isset($this->rp->lang)) {
                $manager->setSetupData(array('lang'=>$this->rp->lang));
            }
            
            $controller->go($controller->getNextStep());
        }
        
        return $view;
    }
}
?>