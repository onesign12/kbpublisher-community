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


class KBClientAction
{

    var $rq;
    var $rp;
    
    var $entry_id;
    var $category_id;
    var $view_id;
    var $msg_id;    


    function __construct() {
        
    }
    
    
    function setVars(&$controller) {

        $this->rq = new RequestData($_GET);
        $this->rp = new RequestData($_POST);
        
        $this->entry_id    = &$controller->entry_id;
        $this->category_id = &$controller->category_id;
        $this->view_id     = &$controller->view_id;
        $this->msg_id      = &$controller->msg_id;
    }    
        
        
    function setCategoryId(&$controller, $manager) {
        
        // category id
        if(!$this->entry_id) {
            $_SESSION['kb_category_id_'] = false;
        }
        
        // set category session, probably this category will be browsing
        // here category id goes from QUERY STRING
        if($this->category_id) {
            $_SESSION['kb_category_id_'] = $this->category_id;
        }        
        
        $skip_views = array(
            'login', 'confirm', 'mfa', 'subscribe', 'news', 
            'print-glossary', 'print-news', 'print-step',
            'success_go', 'account'
        );
        
        if($this->entry_id && !in_array($this->view_id, $skip_views)) {
            
            // possible categories entry can belong to
            // 24 Jul 2013, not inactive categories here, changed  getCategoryIdsByEntryId
            // not allowed here, we should keep it to be able to show login screen if private category
            $category_ids = $manager->getCategoryIdsByEntryId($this->entry_id);
            // echo '<pre>', print_r($category_ids, 1), '</pre>';
            
            // user logged, remove all not allowed categories
            if($manager->is_registered) {
                $category_ids = array_intersect($category_ids, array_keys($manager->categories));
            
            // user not logged and article belogs more than one category
            // trying to find public category for article
            // we do not modify category_ids if we can't find any public category
            } elseif(count($category_ids) > 1) {
                $public_categories = array_filter($manager->categories, array($this, 'filterNotPrivateCategories'));
                $cats = array_intersect($category_ids, array_keys($public_categories));
                if($cats) {
                    $category_ids = $cats;
                }
            }
            
            // no any category for entry - no such entry
            if(!$category_ids) {
                $controller->goStatusHeader('404');
            }
            
            // previos page
            $prev_category_id = (!empty($_SESSION['kb_category_id_'])) ? $_SESSION['kb_category_id_'] : false;
            // echo '<pre>prev_category_id: ', print_r($prev_category_id, 1), '</pre>';
            
            
            // 17 Jul 2014, clicked on entry in menu or in search, category_id in cookie
            if(!empty($_COOKIE['kb_category_id_'])) {
                $_SESSION['kb_category_id_'] = $_COOKIE['kb_category_id_'];
                unset($_COOKIE['kb_category_id_']);
                setcookie('kb_category_id_', null, -1, '/');
            }
            
            if(empty($_SESSION['kb_category_id_'])) {
                $_SESSION['kb_category_id_'] = current($category_ids);
                //echo "<pre>"; print_r('Set Category: empty session'); echo "</pre>";
                
            } elseif(!in_array($_SESSION['kb_category_id_'], $category_ids)) {
                $_SESSION['kb_category_id_'] = current($category_ids);
                //echo "<pre>"; print_r('Set Category: not in arary'); echo "</pre>";
            }
            
            // reset category)id to previous page category id
            // we may need it in when browsing book type category
            // it will allow to stay in the same category 
            if($prev_category_id && $manager->getCategoryType($prev_category_id) == 'book') {
                if($_SESSION['kb_category_id_'] != $prev_category_id) {
                    if(in_array($prev_category_id, $category_ids)) {
                        $_SESSION['kb_category_id_'] = $prev_category_id;
                    }
                }
            }
            
        }
        
        // if viewing where category_id is a must but 
        // by reason of deleted entry or private categories 
        // category_id could be empty
        // $this->category_id = $_SESSION['kb_category_id_'];
        $this->category_id = isset($_SESSION['kb_category_id_']) ? $_SESSION['kb_category_id_'] : 0; 
    }
    

    function checkPrivate($controller, $manager) {

        // check private
        // afile/224/12/  = 224 is article id, 12 file id (AttachID)
        $private_views = array(
            'index', 'entry', 'comment', 'afile',
            'print', 'print-cat',
            'recent', 'popular', 'featured',
            'news', 'print-news',
            'files', 'file', 'download',
            'pdf', 'pdf-cat'
            );

        if(!in_array($this->view_id, $private_views)) {
            return;
        }    
        
        // do not check private if registered 
        // all private entries for registered are hidden so normally 
        // user never will see this links and wee do not need to redirect it to login screen
        // these cases parsed on conrerte view and redirects to 404 if not access
        if($manager->is_registered) {
            return;
        }
        
        if(!AppPlugin::isPlugin('private')) {
            return;
        }
        
        $auth_ended = $controller->auth_ended;

        $cat_private = false;
        if($this->category_id) {
            // false, hidden or display, if catefory does not exists = false
            $cat_private = $manager->isPrivateCategory($this->category_id);
        }

        // //private is hidden even if do not have this category at all
        // echo "<pre>Private: "; print_r($cat_private); echo "</pre>";
        // echo "<pre>Category ID: "; print_r($this->category_id); echo "</pre>";
        // echo "<pre>Role Skip Categories: "; print_r($manager->role_skip_categories); echo "</pre>";
        // echo '<pre>', print_r($manager->setting, 1), '</pre>';
        // echo '<pre>', print_r($manager->categories, 1), '</pre>';
        // exit;

        
        // if access concrete private article, we show login link
        // does not matter for role or not
        if($this->entry_id) {
            
            // if category private or entry private (read), no roles here 
            if($cat_private || $manager->isPrivateEntry($this->entry_id)) {                                                
                $login_msg = ($auth_ended) ? 'authtime_' . $this->view_id : $this->view_id;
                // for afile (attached files) redirect to entry after login
                $login_msg = str_replace('afile', 'entry', $login_msg); 
                $controller->go('login', $this->category_id, $this->entry_id, $login_msg);
            }

        // if access concrete private category, we show login link
        // does not matter for role or not
        } elseif($this->category_id && $cat_private) {
            $login_msg = ($auth_ended) ? 'authtime_' . $this->view_id : $this->view_id;
            $controller->go('login', $this->category_id, $this->category_id, $login_msg);
        }
    }
    
    
    function filterNotPrivateCategories($var) {
        return ($var['private'] == 0 || $var['private'] == 2); // 0 = public and 2 = private write
    }
}
?>