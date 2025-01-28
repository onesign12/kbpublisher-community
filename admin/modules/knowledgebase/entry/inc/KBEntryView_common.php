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


class KBEntryView_common
{
    
    static function getEntryMenu($obj, $manager, $view) {

        $tabs = array('update', 'detail');

        $entry = $obj->get();
        $status = $obj->get('active');
        $record_id = $obj->get('id');
        $own_record = ($obj->get('author_id') == $manager->user_id);

        // history
        $hnum = $manager->getHistoryNum($record_id);
        if (!empty($hnum)) {
            $tabs['history'] = array(
                'link' => $view->getActionLink('history', $record_id),
                'title' => sprintf('%s (%d)', $view->msg['history_msg'], $hnum[$record_id]),
                'highlight' => array('diff')
            );
        }

        // comment
        if($view->priv->isPriv('select', 'kb_comment')) {
            $tabs['comment'] = array(
                'link' => $view->getActionLink('kb_comment', $record_id),
                'title'  => $view->msg['comments_msg']
            );

            // self
            if($view->priv->isSelfPriv('select', 'kb_comment') && !$own_record) {
                unset($tabs['comment']);
            }
        }

        // rating
        if($view->priv->isPriv('select', 'kb_rate')) {
            $tabs['rate'] = array(
                'link' => $view->getActionLink('kb_rate', $record_id),
                'title'  => $view->msg['rating_comment_num_msg']
            );

            // self
            if($view->priv->isSelfPriv('select', 'kb_rate') && !$own_record) {
                unset($tabs['rate']);
            }
        }

        // preview
        $link = $view->getActionLink('preview', $record_id);
        $tabs['preview'] = array(
            'link' => sprintf("javascript:PopupManager.create('%s', 'r', 'r', 2);", $link),
            'title'  => $view->msg['preview_msg']
        );

        // public
        $cats = $manager->getCategoryByIds($record_id)[$record_id];
        $publish_status_ids = $manager->getEntryStatusPublished('article_status');
        $published = CommonEntryView::isEntryPublished($obj->get(), $cats, $publish_status_ids);

        if($published) {
            $client_controller = &$view->controller->getClientController();
            $tabs['public'] = array(
                'link' => $view->controller->getPublicLink('entry', $obj->get()),
                'title'  => $view->msg['entry_public_link_msg'],
                'options'  => array('target' => '_blank')
            );
        }

        // approval
        if(AppPlugin::isPlugin('draft')) {
            $approval_log = $manager->isApprovalLogAvailable($record_id);
            if (!empty($approval_log)) {
                $tabs['approval'] = array(
                    'link' => $view->getActionLink('approval_log', $record_id),
                    'title'  => $view->msg['workflow_log_msg']
                );
            }
        }
    
        // back button
        $options = array();
        $back_link = $view->controller->getCommonLink();
        if($referer = @$_GET['referer']) {
            $client_link = array('entry', false, $record_id);
            $back_link = $view->getRefererLink($referer, $client_link);
        }

        $options['back_link'] = $back_link;
        if(in_array($view->controller->action, array('update'))) {
            $back_link = urlencode($back_link);
            $options['back_link'] = sprintf("javascript:cancelHandler('%s');", $back_link);
        }

        // to list 
        //xajax
        $ajax = &AppAjax::factory();
        $xajax = &$ajax->getAjax();
        
        $entry_id = (int) $obj->get('id');
        $reg = &Registry::instance();
        $reg->setEntry('entry_id', $entry_id);
        
        $xajax->registerFunction(array('saveToList', 'KBEntryView_common', 'ajaxSaveToList'));

        // menu
        $options['more'] = KBEntryView_common::getMoreMenu($obj, $manager, $view, 'kb_draft');
       
        // right block - saved list etc.
        $options['right'] = KBEntryView_common::getRightMenu($obj, $manager, $view);

        // if some of categories is private
        // and user do not have this role so he can't access to some actions
        $has_private = $manager->isCategoryNotInUserRole($obj->getCategory());
        if($has_private) {
            unset($tabs[array_search('update', $tabs)]);
            unset($tabs['history']);
            unset($tabs['approval']);
            $options['more'] = array();
        }

        $pluginable = AppPlugin::getPluginsFilteredOff('tabs', 1);
        $tabs = array_diff_key($tabs, $pluginable);

        return $view->getViewEntryTabs($entry, $tabs, $own_record, $options);
    }


    static function getMoreMenu($obj, $manager, $view, $page) {

        $options = array('clone');
        $record_id = $obj->get('id');
        $own_record = ($obj->get('author_id') == $manager->user_id);

        // $rlink = $view->controller->getLink('all');
        $rlink = $view->controller->getLink('all',0,0,0,['id'=>$record_id]);
        $referer = WebUtil::serialize_url($rlink);

        // copy to clipboard
        $view_id = (strpos($page, 'file') === false) ? 'entry' : 'file';
        $link = $view->controller->getPublicLink($view_id, $obj->get());
        $options['public'] = array(
            'link' => '#clipboard',
            'title' => $view->msg['copy_clipboard_pub_msg'],
            'options' => "class=\"clipboard\" data-clipboard-text=\"{$link}\""
        );


        $draft_id = $manager->isEntryDrafted($record_id);
        if ($draft_id) {
            $more = array('id' => $draft_id, 'referer' => $referer);
            $options['draft'] = array(
                'link' => $view->getLink('this', $page, false, 'detail', $more),
                'title' => $view->msg['view_draft_msg']
            );

        } else {
            if($view->priv->isPriv('insert', $page)) {
                $options['draft'] = array(
                    'link' => $view->getActionLink('edit_as_draft', $record_id),
                    'title'  => $view->msg['update_as_draft_msg']
                );

                if($view->priv->isPriv('delete')) {
                    $more = array('referer' => $referer);
                    $options['move_to_draft'] = array(
                        'link' => $view->getActionLink('move_to_draft', $record_id, $more),
                        'title' => $view->msg['move_to_drafts_msg'],
                        'confirm_msg' => $view->msg['move_to_drafts_note_msg']
                    );

                    // self
                    if($view->priv->isSelfPriv('delete') && !$own_record) {
                        unset($options['move_to_draft']);
                    }
                }
            }

            $options['delete'] = array(
                'title'  => $view->msg['trash_msg']
            );
        }
        
        // duplicate
        if($view->priv->isPrivOptional('insert', 'draft')) {
            unset($options[array_search('clone', $options)]);
        }
        
        $pluginable = AppPlugin::getPluginsFilteredOff('tabs', 1);
        $options = array_diff_key($options, $pluginable);

        return $options;
    }


    static function getDraftsMessage($view, $page) {
        $vars['link'] = $view->getLink('this', $page, false, false);
        $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
        $msgs = AppMsg::parseMsgsMultiIni($file);
        $msg['title'] = ''; //$msgs['title_entry_autosave'];
        $msg['body'] = $msgs['note_entry_draft'];
        return BoxMsg::factory('hint', $msg, $vars);
    }
    
    
    static function getRightMenu($obj, $manager, $view) {
        
        $subscribed = $manager->isEntrySubscribedByUser($obj->get('id'));
        $status = ($subscribed) ? 0 : 1;
        $class = ($subscribed) ? 'subscribed' : 'unsubscribed';
        $msg = ($subscribed) ? $view->msg['remove_from_list_msg'] : $view->msg['save_to_list_msg'];
        
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="%s"><path d="M19 24l-7-6-7 6v-24h14v24z"/></svg>';
        $svg = sprintf($svg, $class);
        
        $html = '<a href="#list" id="user_list_toggle" class="_tooltip" title="%s" onclick="javascript:xajax_saveToList(%d);">%s</a>';
        $html = sprintf($html, $msg, $status, $svg);
        
        return $html;
    }
    
    
    static function ajaxSaveToList($value) {
        return self::_ajaxSaveToList(1, $value);
    }
    
    
    static function _ajaxSaveToList($entry_type, $value) {

        $objResponse = new xajaxResponse();
        
        $manager = new SubscriptionModel;
        $reg = &Registry::instance();
        
        $msg = AppMsg::getMsgs('common_msg.ini', 'knowledgebase');
        
        $entry_id = (int) $reg->getEntry('entry_id');
        $user_id = (int) AuthPriv::getUserId();
        $value = (int) $value;

        if($value) {
            $manager->saveSubscription(array($entry_id), $entry_type, $user_id);
        } else {
            $manager->deleteSubscription(array($entry_id), $entry_type, $user_id);
        }
        
        $old_class = ($value) ? 'unsubscribed' : 'subscribed';
        $new_class = ($value) ? 'subscribed' : 'unsubscribed';
        $inverted_status = ($value) ? 0 : 1;
        $title = ($value) ? $msg['remove_from_list_msg'] : $msg['save_to_list_msg'];
        
        $js = '$("#user_list_toggle svg").removeClass("%s").addClass("%s")';
        $script = sprintf($js, $old_class, $new_class);
        $objResponse->script($script);
        
        $js = '$("#user_list_toggle").attr("onclick", "javascript:xajax_saveToList(%d);")';
        $script = sprintf($js, $inverted_status);
        $objResponse->script($script);
        
        $js = '$("#user_list_toggle").tooltipster("content", "%s");';
        $script = sprintf($js, $title);
        $objResponse->script($script);
        
        //AppSphinxModel::updateAttributes('subscriber', $user_id, $entry_id, $entry_type);
        
        // growl
        $key = ($value) ? 'saved_simple' : 'removed_simple';
        $msg = AppMsg::getMsgs('after_action_msg.ini', false, $key);
        $growl_cmd = '$.growl({title: "%s", message: "%s"});';
        $growl_cmd = sprintf($growl_cmd, '', $msg['body']);
        $objResponse->script($growl_cmd);
        
        return $objResponse;
    }
    
}
?>