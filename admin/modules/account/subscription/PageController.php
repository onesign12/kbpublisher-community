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

$obj = new Subscription;
$manager =& $obj->setManager(new SubscriptionModel());

// skip check priv for account
// add this to not strip  actions/buttons and allow bulk
$priv->skip_check_priv = true;

$controller->setMoreParams('type');
$uid = AuthPriv::getUserId();

// type of subscription
$list_type = true;
if (isset($rq->type)) {
    if ($manager->types[$rq->type]) {
        $list_type = $manager->types[$rq->type];
    }
}


switch ($controller->action) {
case 'delete': // ------------------------------
    $entry_id = (int) $rq->id;
    $entry_type = (int) $rq->type;

    $manager->deleteSubscription($entry_id, $entry_type, $uid);

    $controller->go();

    break;


case 'bulk': // ------------------------------

    if(isset($rp->submit) && !empty($rp->id)) {
    
        $rp->stripVars();
    
        $ids = array_map('intval', $rp->id);
        $type = addslashes($rq->type);
        $action = $rp->bulk_action;

        $bulk_manager = new SubscriptionModelBulk();
        $bulk_manager->setManager($manager);
        
        if($bulk_manager->validate($rp->vars)) {
            $controller->go('csrf');
        }

        switch ($action) {
        case 'unsubscribe': // ------------------------------
            $manager->deleteSubscription($ids, $type, $uid);
            break;
        }

        $controller->go();
    }

    $controller->goPage('main');

    break;
    

case 'category': // ------------------------------

    $view = new KBEntryView_category;
    
    $user_id = AuthPriv::getUserId();
    $type = $controller->getMoreParam('type');
    $cat_ids = $manager->getSubscription($type, $user_id);
    
    $msg = AppMsg::getMsgs('user_msg.ini');
    
    $options = array(
        'all' => true,
        'sortable' => false,
        'creation' => false,
        'secondary_block' => false,
        'status_icon' => false,
        'cancel_button' => true,
        'mode' => 'subscription',
        'popup_title' => $msg['subscribe_cat_msg'],
        'main_title' => $msg['selected_subscription_msg'],
        'non_active_state' => 'hidden',
        'disabled_ids' => $cat_ids,
        'top_button' => true,
        'related' => 'children'
    );
    
    $emanager = ($type == 11) ? $manager->getArticleManager() : $manager->getFileManager();
    $categories = $emanager->getCategoryRecordsUser();  // private removed
    
    $view = $view->parseCategoryPopup($emanager->cat_manager, $categories, $options);
    break;


case 'update': // ------------------------------
case 'insert': // ------------------------------

    // news
    if ($rq->type == 3) {
        $is_subsc = (int) $rq->subsc;

        if ($is_subsc) {
            $manager->saveSubscription(array(0), 3, $uid);
        } else {
            $manager->deleteSubscription(0, 3, $uid);
        }

        $controller->removeMoreParams('type');
        $controller->go();


    // category
    } else {

        if(isset($rp->filter)) {

            $is_error = $obj->validate($rp->vars, array('subscriptions'));

            if($is_error) {
                $rp->stripVars(true);
                $obj->set($rp->vars);

            } else {

                //$rp->stripVars();
                //$obj->set($rp->vars);

                // all
                if(in_array('0', $rp->subscriptions)) {
                    $manager->deleteByEntryType($rq->type, $uid);
                    $manager->saveSubscription(array(0), $rq->type, $uid);

                // selected
                } else {
                    $subs = $manager->getSubscription($rq->type, $uid);
                    $subs = array_merge($subs, $rp->subscriptions);
                    $subs = $manager->parseCategories($subs, $rq->type);

                    if($subs['remove']) {
                        $manager->deleteSubscription($subs['remove'], $rq->type, $uid);
                    }

                    $manager->deleteSubscription(0, $rq->type, $uid);
                    $manager->saveSubscription($subs['add'], $rq->type, $uid);
                }

                $controller->go();
            }
        }
    }

    $subsc = 'SubscriptionView_form_' . $list_type;
    $view = $controller->getView($obj, $manager, $subsc);

    break;


default: // ------------------------------------
    
    if($list_type !== true) {
        $view = 'SubscriptionView_list_' . $list_type;
        $view = $controller->getView($obj, $manager, $view);
    } else {
         $view = $controller->getView($obj, $manager, 'SubscriptionView_types');
    }
}
?>