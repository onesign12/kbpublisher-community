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


class FileEntryView_common extends KBEntryView_common
{
    
    static function getEntryMenu($obj, $manager, $view) {

        $tabs = array('update', 'detail');

        $entry = $obj->get();
        $status = $obj->get('active');
        $record_id = $obj->get('id');
        $own_record = ($entry['author_id'] == $manager->user_id);
        
        $entry['title'] = $obj->get('filename'); // for title in form
        
        // history
        $hnum = $manager->getHistoryNum($record_id);
        if (!empty($hnum)) {
            $tabs['history'] = array(
                'link' => $view->getActionLink('history', $record_id),
                'title' => sprintf('%s (%d)', $view->msg['history_msg'], $hnum[$record_id]),
                'highlight' => array('diff')
            );
        }        
        
        $tabs['fopen'] = array(
            'link' => $view->getActionLink('fopen', $record_id),
            'title'  => $view->msg['open_msg'],
            'options'  => array('target' => '_blank')
        );
            
        $tabs['download'] = array(
            'link' => $view->getActionLink('file', $record_id),
            'title'  => $view->msg['download_msg']
        );
        
        if($view->isEntryUpdateable($record_id, $status, $own_record)) {
            if(!$view->priv->isPrivOptional('update', 'draft')) {
                $tabs['filetext'] = array(
                    'link' => $view->getActionLink('text', $record_id),
                    'title'  => $view->msg['filetext_msg']
                );
            }
        }
        
        // approval
        if(AppPlugin::isPlugin('draft')) {
            $approval_log = $manager->isApprovalLogAvailable($obj->get('id'));
            if (!empty($approval_log)) {
                $tabs['approval'] = array(
                    'link' => $view->getActionLink('approval_log', $record_id),
                    'title'  => $view->msg['workflow_log_msg']
                );
            }
        }
        
        
        $options = array();
        if($referer = @$_GET['referer']) {
            $options['back_link'] = $view->getRefererLink($referer);
        }
        
        // to list
        //xajax
        $ajax = &AppAjax::factory();
        $xajax = &$ajax->getAjax();
        
        $entry_id = (int) $obj->get('id');
        $reg = &Registry::instance();
        $reg->setEntry('entry_id', $entry_id);
        
        $xajax->registerFunction(array('saveToList', 'FileEntryView_common', 'ajaxSaveToList'));
        
        // menu
        $options['more'] = KBEntryView_common::getMoreMenu($obj, $manager, $view, 'file_draft');
        if($options['more']) {
            $options['more']['delete'] = array(
                'title'  => $view->msg['trash_msg']
            );
        }
        
        // right block - saved list etc.
        $options['right'] = KBEntryView_common::getRightMenu($obj, $manager, $view);
        
        // if some of categories is private
        // and user do not have this role so he can't access to some actions
        $has_private = $manager->isCategoryNotInUserRole($obj->getCategory());
        if($has_private) {
            unset($tabs[array_search('update', $tabs)]);
            unset($tabs['approval']);
            unset($tabs['filetext']);
            $options['more'] = array();
        }
        
        // check if file exists
        // $error_msg = '';
        // if($obj->get('id')) {
        //     $file_dir = $manager->getSetting('file_dir');
        //     if(!FileEntryUtil::getFileDir($obj->get(), $file_dir)) {
        //         $error_msg = AppMsg::afterActionBox('inaccessible_file');
        //     }
        // }
            
        return $view->getViewEntryTabs($entry, $tabs, $own_record, $options);
    }
    
    
    static function ajaxSaveToList($value) {
        return KBEntryView_common::_ajaxSaveToList(2, $value);
    }
}
?>