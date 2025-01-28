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
$rp->setHtmlValues('body'); // to skip $_GET['body'] not strip html

$obj = new LetterTemplate;
//$obj->setHtmlValues('body');

$manager =& $obj->setManager(new LetterTemplateModel());

$priv->setCustomAction('preview', 'select');
$priv->setCustomAction('default', 'update');
$manager->checkPriv($priv, $controller->action, @$rq->id);


// file last generated on 4 July, 2023
// includes all new emails for v8.0
/*$file = $controller->working_dir . 'inc/default.php';
$data  = serialize($manager->getDefaultRecords());
$data = sprintf('$data=\'%s\'', $data);
echo "<?php\n" . $data . "\n?>";
exit;*/


switch ($controller->action) {
case 'status': // ------------------------------
    
    $manager->status($rq->status, $rq->id);
    $controller->go();

    break;


case 'default': // ------------------------------
    
    require_once $controller->working_dir . 'inc/default.php';
    $data = unserialize($data);    
    $row = array();
    foreach(array_keys($data) as $k) {
        if($data[$k]['id'] == $rq->id) {
            $row = $data[$k];
            break;
        }
    }
    
    if($row) {
        $row = $rp->stripVarsValues($row, false);
        $obj->unsetProperties(array('title','description',
                                    'letter_key','group_id',
                                    'skip_field','extra_tags','skip_tags',
                                    'is_html','in_out','predifined','active','sort_order'));
        $obj->set($row);
        $obj->set('subject', '');
        $obj->set('body', NULL);
        
        // $manager->save($obj);
        $manager->update($obj);
        
        $link = $controller->getLink('this', 'this', 'this', 'update', array('id'=>$rq->id));
        $controller->setCustomPageToReturn($link, false);
        
        $controller->go();        
    }
    
    $controller->goPage('main');

    break;    
    

case 'preview': // ------------------------------
    
    $rp->stripVars(true);
    $obj->set($rp->vars);
    
    if($obj->get('predifined') && !$obj->get('body')) {
        $p = new AppMailParser;
        $obj->set('body', $p->getTemplate($obj->get('letter_key')));
    }

    $view = $controller->getView($obj, $manager, 'LetterTemplateView_preview'); 

    break;    
    
    
case 'update': // ------------------------------
case 'insert': // ------------------------------

    if(isset($rp->submit) || isset($rp->preview)) {
        
        $is_error = $obj->validate($rp->vars);
        
        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
        
            $data = $manager->getById($rq->id);
            $rp->stripVarsValues($data);
            $obj->set('title', $data['title']);
            $obj->set('description', $data['description']);
        
        } else {
        
            $rp->stripVars();
            $obj->set($rp->vars);
            
            $manager->save($obj);
            $controller->go();
        }
        
    
    } elseif($controller->action == 'update') {
        
        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data);
        $obj->set($data);
        
        if($obj->get('predifined') && !$obj->get('body')) {
            $p = new AppMailParser;
            $obj->set('body', $p->getTemplate($obj->get('letter_key')));
        }
    }

    $view = $controller->getView($obj, $manager, 'LetterTemplateView_form'); 

    break;


default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, 'LetterTemplateView_list');
}
?>