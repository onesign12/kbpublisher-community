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


$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);
$rp->setSkipKeys(array('title', 'body'));

$obj = new PageDesign;
$manager =& $obj->setManager(new PageDesignModel());
$manager->sm->module_id = 11;

switch ($controller->action) {
case 'setting': // ------------------------------
    $view = $controller->getView($obj, $manager, 'PageDesignView_setting_form');

    break;
    

case 'custom_block': // ------------------------------

    $b_obj = new PageDesignCustomBlock;
        
    if (isset($rp->submit)) {
        $is_error = $b_obj->validate($rp->vars);
        
        if($is_error) {
            $rp->stripVars(true);
            $b_obj->set($rp->vars);
        
        } else {
            $rp->stripVars();
            $b_obj->set($rp->vars);
            
            $b_obj->set('data_string', addslashes(serialize($rp->vars)));
            
            $block_id = $manager->save($b_obj);
            $block_id = (!empty($rq->id)) ? $rq->id : $block_id;
            $_GET['block_id'] = $block_id;
            
            $controller->setMoreParams('action');
            $controller->setMoreParams('block_id');
            
            $controller->go('success', true);
        }
    }

    if (!empty($rq->id)) {
        $data = $manager->getById($rq->id);

        //$rp->stripVarsValues($data, array('data_string'));
        $b_obj->set($data);
    }
    
    $view = $controller->getView($b_obj, $manager, 'PageDesignCustomBlockView_form');
    break;
    
    
case 'update': // ------------------------------

    if (isset($rp->set_default)) {
        
    	$setting_id = $manager->sm->getSettingIdByKey($rq->key);
        $manager->sm->setDefaultValues($setting_id);
		
        $html_setting_id = $manager->sm->getSettingIdByKey($rq->key . '_html');
        $manager->sm->setDefaultValues($html_setting_id);
        
        $menu_setting_id = $manager->sm->getSettingIdByKey($rq->key . '_menu');
        if (!is_null($menu_setting_id)) {
            $manager->sm->setDefaultValues($menu_setting_id);
        }
        
        // check for disabled blocks, update html
        $page_key = substr($rq->key, 12);
        $blocks = $manager->getDesign($page_key);
        
        foreach (array_keys($blocks) as $k) {
            $block = $blocks[$k];
            if (!empty($manager->block_to_setting[$block['id']])) {
                $setting = SettingModel::getQuick(0, 'module_' . $manager->block_to_setting[$block['id']]);
                if (!$setting) {
                    unset($blocks[$k]);
                }
            }
        }
        
        $blocks = array_values($blocks);
        $blocks = json_encode($blocks);
        
        $grid = PageDesignModel::getHtmlGrid($blocks, $manager);
        
        $settings = array(
            $html_setting_id => $grid
        );
        $manager->sm->setSettings($settings);
        
        
        $more = array('key' => $rq->key);
        if ($controller->getMoreParam('popup')) {
            $more['popup'] = 1;
        }
        
        $controller->goPage('this', 'this', 'this', 'this', $more);
    }
    
    $view = $controller->getView($obj, $manager, 'PageDesignView_form');
    break;
    
    
default: // ------------------------------------

    $view = $controller->getView($obj, $manager, 'PageDesignView_list');    
    
}

?>