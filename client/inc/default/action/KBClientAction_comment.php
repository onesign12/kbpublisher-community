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

class KBClientAction_comment extends KBClientAction_common
{

    function &execute($controller, $manager) {

        
        $action = $controller->msg_id;

        $message_id = $controller->getRequestVar('message_id');
        if ($message_id) {
            $num_per_page = $manager->setting['num_comments_per_page'];
            $position = $manager->getCommentPosition($this->entry_id, $message_id);

            $more = array();
            $page_num = ceil($position / $num_per_page);
            if ($page_num > 1) {
                $more['bp'] = $page_num;
            }

            $url = $controller->getLink('comment', false, $this->entry_id, false, $more);
            $anchor = '#c' . $message_id;
            $url .= $anchor;

            $url = $controller->_replaceArgSeparator($url);
            header("Location: " . $url);
            exit;
        }

        if($action == 'post') {
            $view = &$this->add($controller, $manager);

        } elseif($action == 'update') {
            $view = &$this->update($controller, $manager);

        } elseif($action == 'delete') {
            $view = &$this->delete($controller, $manager);

        } else {
            $view = &$controller->getView('comment_list');
        }

        return $view;
    }


    function &add($controller, $manager) {

        // if not allowed
        if(!$manager->getSetting('allow_comments')) {
            $controller->go('entry', $this->category_id, $this->entry_id);
        }

        // need to login
        if(!$manager->is_registered && $manager->getSetting('allow_comments') == 2) {
            $controller->go('login', $this->category_id, $this->entry_id, 'comment');
        }

	    // bot detection
	    if(!empty($this->rp->vars['captcha2'])) {
            $controller->go('success_go', $this->category_id, $this->entry_id, 'comment_posted');
	    }


        $obj = new KBComment;
        $manager2 = new KBCommentModel();
        $manager_subs = new SubscriptionModel();

        $view = &$controller->getView('comment_form');
        $view->form_title = $view->msg['add_comment_msg'];

        if(isset($this->rp->submit)) {

            $errors = $view->validate($this->rp->vars, $manager);
            //$flood = $manager->isFlood();

            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->setFormData($this->rp->vars);

            } elseif($view->isSpam($this->rp->vars)) {
                $controller->go('success_go', $this->category_id, $this->entry_id, 'comment_wait');

            //} elseif($flood) {
            //    $this->rp->stripVars(true);
            //    $view->setFormData($this->rp->vars);
            //    $view->msg_id = 'flood_comment';

            } else {

                if($manager->is_registered) {
                    $msg = ($manager->getSetting('comment_policy') != 2) ? 'comment_posted' : 'comment_wait';
                    $active = ($manager->getSetting('comment_policy') != 2) ? 1 : 0;
                } else {
                    $msg = ($manager->getSetting('comment_policy') == 1) ? 'comment_posted' : 'comment_wait';
                    $active = ($manager->getSetting('comment_policy') == 1) ? 1 : 0;
                }

                // for user who has priv for comment, no comment_policy in use
                $allowed = AuthPriv::getPrivAllowed('kb_comment');
                if(in_array('insert', $allowed)) {
                    $active = 1;
                    $msg = 'comment_posted';
                }

                $this->rp->setSubstrValues(['name' => 50, 'email' => 50]);
                $this->rp->stripVars();
                
                $obj->set($this->rp->vars);
                $obj->set('id', NULL);
                $obj->set('entry_id', $this->entry_id);
                $obj->set('user_id', $manager->user_id);
                $obj->set('active', $active);

                $commment_id = $manager2->add($obj);

                $more = array();
                if(!$active) {
                    $this->rp->stripVars('stripslashes');
                    $sent = $manager->sendApproveCommentAdmin($commment_id,
                                                              $this->entry_id,
                                                              $this->category_id,
                                                              $this->rp->vars);
                } else {
                    $more = array('message_id' => $commment_id);
                    $manager2->updateCommentDateForEntry($this->entry_id);
                }

                if($manager->isSubscribtionAllowed('comment')) {
                    $entry_id = (int) $this->entry_id;
                    $user_id = (int) $manager->user_id;
                    $type = 31;

                    if(isset($this->rp->subscribe)) {
                        $manager_subs->saveSubscription($entry_id, $type, $user_id);

                        // set lastsent to NOW(), to not sent notifications to this user
                        $manager_subs->updateDateLastsent($entry_id, $type, $user_id);
                    } else {
                        $manager_subs->deleteSubscription($entry_id, $type, $user_id);
                    }
                }
                
                $controller->go('success_go', $this->category_id, $this->entry_id, $msg, $more);
            }
        }

        return $view;
    }


    function &update($controller, $manager) {

        $allowed = AuthPriv::getPrivAllowed('kb_comment');
        if(!in_array('update', $allowed)) {
            // check self priv
            $entry = $manager->getEntryData($this->entry_id);
            if($entry['author_id'] != $manager->user_id  || !in_array('self_update', $allowed)) {
                $controller->go('entry', $this->category_id, $this->entry_id);
            }
        }
        

        $obj = new KBComment;
        $manager2 = new KBCommentModel();

        $comment_id = (int) $_GET['id'];

        $view = &$controller->getView('comment_form');
        $view->form_title = $view->msg['update_comment_msg'];

        if(isset($this->rp->submit)) {

            $errors = $view->validate($this->rp->vars, $manager);
            //$flood = $manager->isFlood();

            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->setFormData($this->rp->vars);

            } else {

                $this->rp->stripVars();
                $obj->set($this->rp->vars);

                // saved with all old data except for message
                $data = $manager2->getById($obj->get('id'));
                $data = RequestDataUtil::stripVars($data, array(), 'addslashes');
                $obj->set('user_id', $data['user_id']);
                $obj->set('email', $data['email']);
                $obj->set('name', $data['name']);

                $manager2->save($obj);

                $controller->go('success_go', $this->category_id, $this->entry_id, 'comment_updated');
            }

        } else {
            $data = $manager2->getById($comment_id);
            $this->rp->stripVarsValues($data);
            $view->setFormData($data);
        }

        return $view;
    }


    function &delete($controller, $manager) {

        $allowed = AuthPriv::getPrivAllowed('kb_comment');
        if(!in_array('delete', $allowed)) {
            // check self priv
            $entry = $manager->getEntryData($this->entry_id);
            if($entry['author_id'] != $manager->user_id  || !in_array('self_delete', $allowed)) {
                $controller->go('entry', $this->category_id, $this->entry_id);
            }
        }

        $comment_id = (int) $_GET['id'];

        $manager2 = new KBCommentModel();
        $manager2->delete($comment_id);
        $manager2->updateCommentDateForEntry($this->entry_id);
        $controller->go('success_go', $this->category_id, $this->entry_id, 'comment_deleted');
    }
}
?>