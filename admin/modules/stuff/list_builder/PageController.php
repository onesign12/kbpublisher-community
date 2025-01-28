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


$obj = new AppObj();
$manager = new ListBuilderModel();


switch ($controller->action) {
    
case 'customize_list': // ------------------------------
    
    $view = $controller->getView($obj, $manager, 'ListBuilderView_customize');
    
    break;
        
case 'customize_export': // ------------------------------
    
    $view = $controller->getView($obj, $manager, 'ListBuilderView_customize_export');
    
    break;

    
default: // ------------------------------------
    
    exit;
}
?>