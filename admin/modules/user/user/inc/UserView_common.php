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


class UserView_common
{

    static function getEntryMenu($obj, $manager, $view) {

        $tabs = array('update', 'detail');

        $entry = $obj->get();
        $obj->setFullName();
        $entry['title'] = $obj->getFullName();

        $own_record = ($entry['grantor_id'] == $manager->user_id);
        $record_id = $obj->get('id');

        if(AppPlugin::isPlugin('report')) {
            $tabs['activity'] = array(
                'link' => $view->getLink('this', 'this', false, 'activity', array('id' => $record_id)),
                'title' => $view->msg['activity_log_msg']
            );
        }

        // password, api
        if($view->priv->isPriv('update')) {

            $tabs['api'] = array(
                'link' => $view->getActionLink('api', $record_id),
                'title' => $view->msg['api_update_msg']
            );

            $tabs['password'] = array(
                'link' => $view->getActionLink('password', $record_id),
                'title' => $view->msg['update_password_msg']
            );

            // self
            if($view->priv->isSelfPriv('update') && !$own_record) {
                unset($tabs['api']);
                unset($tabs['password']);
            }
        }

        // menu
        $options = array();

        if($view->priv->isPriv('login')) {
            $options['more']['login'] = array(
                'link' => $view->getActionLink('login', $record_id),
                'title'  => $view->msg['login_as_user_msg']
            );

            // self
            if($view->priv->isSelfPriv('login') && !$own_record) {
                unset($options['more']['login']);
            }
        }

        // priv will be checked in  getViewEntryTabs
        $options['more']['delete'] = array(
            'title'  => $view->msg['trash_msg'],
            'confirm_msg' => false
        );

        // check priv level
        // user not allowed any actions for users with greater priv level
        $priv_level = $manager->getPrivLevelByUserId($obj->get('id'));
        if(!$manager->isUpdateablePrivLevel($priv_level)) {
            $tabs = array();
            $options = array();
        }

        return $view->getViewEntryTabs($entry, $tabs, $own_record, $options);
    }

}
?>