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
$rp->setSkipKeys(array('entry_obj'));


if($controller->page == 'kb_autosave' || $controller->page == 'kb_draft_autosave') {

    $entry_manager = new KBEntryModel;
    $detail_view = new KBEntryView_detail();
    
    if ($controller->page == 'kb_draft_autosave') {
        $detail_view->draft_view = true;
        $detail_view->page = 'kb_draft';
    }

} elseif($controller->page == 'news_autosave') {

    $entry_manager = new NewsEntryModel;
    $detail_view = new NewsEntryView_detail();

} else {
    // $controller->go();
}

// echo '<pre>', print_r($detail_view,1), '<pre>';
// exit;

$obj = new KBAutosave;
$manager = new KBAutosaveModel();
$manager->record_entry_type = ($controller->page == 'kb_draft_autosave') ? 7 : $entry_manager->entry_type;
// $manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {
case 'delete': // ------------------------------

    $manager->delete($rq->dkey);
    
    if(!$manager->getCountRecords()) {
        
        $map = array(
            'kb_autosave' => 'kb_entry',
            'kb_draft_autosave' => 'kb_draft',
            'news_autosave' => 'news_entry'
        );
        
        $page = $map[$controller->page];
        $link = $controller->getLink($controller->module, $page);
        $controller->setCustomPageToReturn($link, false);
    }
    
    $controller->go();

    break;


case 'detail': // ------------------------------

    $entry_data = $manager->getByIdKey($rq->dkey);
    $entry_data['dkey'] = $rq->dkey;
    $entry_obj = unserialize($entry_data['entry_obj']);
    
    $entry_obj->restore($entry_manager);

    $view = $detail_view->execute($entry_obj, $entry_manager, $entry_data);

    break;


case 'preview': // ------------------------------

    $entry_data = $manager->getByIdKey($rq->dkey);
    $entry_obj = unserialize($entry_data['entry_obj']);

    if ($controller->page == 'kb_autosave' || $controller->page == 'kb_draft_autosave') { // articles
        $view = new KBEntryView_preview;

    } else { // news
        $view = new NewsEntryView_preview;
    }

    $view = $view->execute($entry_obj, $manager);

    break;


default: // ------------------------------------

    $view = $controller->getView($obj, $manager, 'KBAutosaveView_list');
}
?>