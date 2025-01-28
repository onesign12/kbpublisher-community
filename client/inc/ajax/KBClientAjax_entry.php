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


class KBClientAjax_entry extends KBClientAjax
{

    function doRateResponse($rate) {

        $objResponse = new xajaxResponse();
        
        $rate = (int) $rate;
        if($rate) {
            if($this->manager->isUserVoted($this->entry_id) === false) {
                $this->manager->addVote($this->entry_id, $rate);
                // $this->manager->setUserVoted($this->entry_id); 

                //$objResponse->addAlert($str);
                $objResponse->addAssign('rateResponce', 'style.display', 'block');
                $objResponse->addAssign('rateQuery', 'style.display', 'none');
                $objResponse->addAssign('currentRating', 'style.display', 'none');

                if($this->manager->getSetting('allow_rating_comment')) {
                    $objResponse->addAssign('rateFeedbackForm', 'style.display', 'block');
                    $objResponse->call('showFeedbackFormPanel');
                    $objResponse->addAssign('rate_rating', 'value', $rate);

                    $div = ($rate < 5) ? 'comment_rate_neg' : 'comment_rate_pos';
                    $objResponse->addAssign($div, 'style.display', 'inline');
                    $objResponse->addAssign('comment_report', 'style.display', 'none');
                    // $objResponse->call('slideToTextarea');
                }
                
                $objResponse->addAssign('rateFeedbackForm', 'style.float', 'none');

            } else {
                $objResponse->addAlert('The entry was voted already!');
            }

        } else {
            $objResponse->addAlert('Error!');
        }

        return $objResponse;
    }


    // used for rate comments and reporn an issue
    function doRateFeedbackResponse($comment, $rate_value, $token) {

        $objResponse = new xajaxResponse();

        $msg = AppMsg::getMsgs('error_msg.ini'); 
        // $objResponse->addAlert($msg['csfr_msg']);    

        $comment = trim($comment);
        if($comment) {
            
            $ret = Auth::validateCsrfToken($token, false);
            if(!$ret) {
                $msg = AppMsg::getMsgs('error_msg.ini'); 
                // $objResponse->addAlert($msg['csfr_msg']);
                $growl_cmd = '$.growl.error({title: "", message: "%s", fixed: true});';
                $objResponse->AddScript(sprintf($growl_cmd, $msg['csfr_msg']));
                
            } else {
            
                $rating = (int) $rate_value;
                $comment = RequestDataUtil::stripVars($comment, array(), 'addslashes');            
                $rating_id = $this->manager->addVoteFeedback($this->entry_id, $comment, $rating);

                if($rating) {
                    $msg_key = ($this->manager->getSetting('rating_type') == 1) ? 'rating' : 'rating2';
                    $msg = AppMsg::getMsg('ranges_msg.ini', 'public', $msg_key);
                    $vars['rating'] = $msg[$rating];
                }

                $vars['user_id'] = $this->manager->user_id;
                $vars['message'] = RequestDataUtil::stripVars($comment, array(), 'stripslashes');
                $vars['entry_id'] = $this->entry_id;
                $vars['category_id'] = $this->category_id;
                $vars['title'] = $this->manager->getEntryTitle($this->entry_id);

                
                // $more = array('id'=>$rating_id);
                // $vars['link'] = $this->controller->getAdminRefLink('knowledgebase', 'kb_rate', false, 'update', $more);
                $more = array('entry_id'=>$this->entry_id, 'comment_id'=>$rating_id);
                $vars['link'] = $this->controller->getAdminRefLink('knowledgebase', 'kb_rate', false, 'entry', $more);
                $vars['link'] .= '#acomment_' . $rating_id;
                $vars['entry_link'] = $this->controller->getLink('entry', false, $this->entry_id);

                // hide popup form, hew, added March 18, 2021
                $objResponse->AddScript('closeReportPanel(1);');

                // growl
                $growl_cmd = '$.growl({title: "%s", message: "%s"});';
                $msg_key = ($rating) ? 'thanks_rate2_msg' : 'thanks_report_msg';
                $msg = AppMsg::getMsg('client_msg.ini', 'public');
                $objResponse->AddScript(sprintf($growl_cmd, '', $msg[$msg_key]));

                $ret = $this->manager->sendRatingNotification($vars);
                // $objResponse->addAlert('<pre>' . print_r($ret, 1) . '</pre>');   
            }
        }
        
        $objResponse->addAssign('rateFeedbackForm', 'style.display', 'none');
        
        return $objResponse;
    }


    function doSubscribeArticleResponse($value) {
        $this->entry_type = 1; // article
        return $this->_doSubscribeResponse($value, $this->entry_id, false);
    }


    function doSubscribeFileResponse($value, $entry_id, $id_suffics) {
        $this->entry_type = 2; // file
        return $this->_doSubscribeResponse($value, $entry_id, $id_suffics);
    }


    function doSubscribeArticleCatResponse($value) {
        $this->entry_type = 11; // article category
        return $this->_doSubscribeResponse($value, $this->category_id, false);
    }


    function doSubscribeFileCatResponse($value) {
        $this->entry_type = 12; // file category
        return $this->_doSubscribeResponse($value, $this->category_id, false);
    }


    function _doSubscribeResponse($value, $entry_id, $id_suffics, $objResponse = false) {

        if (!$objResponse) {
            $objResponse = new xajaxResponse();
        }

        if(!$this->manager->is_registered) {
            $more = array('t'=>$this->entry_type);
            $link = $this->controller->getLink('login', false, $entry_id, 'subscribe', $more);
            $link = $this->controller->_replaceArgSeparator($link);
            
            $objResponse->addRedirect($link);
            return $objResponse;
        }

        $value = (int) $value;
        $entry_id = (int) $entry_id;
        $user_id = (int) $this->manager->user_id;
        $type = $this->entry_type;

        
        $manager = new SubscriptionModel();
        
        $visible_display = 'inline';

        if($value) {
            
            // for articles and files, could be saved to list without subscription 
            if($this->entry_type == 1 || $this->entry_type == 2) {
                $is_email = (int) $this->manager->isSubscribtionAllowed();
                $manager->saveSubscriptionEntry($entry_id, $type, $user_id, $is_email);
                
            } else {
                $manager->saveSubscription($entry_id, $type, $user_id);
            }
            
            // this for old, bottom actions 
            $objResponse->addAssign('div_subscribe_yes' . $id_suffics, 'style.display', 'none');
            $objResponse->addAssign('div_subscribe_no' . $id_suffics, 'style.display', $visible_display);

        } else {
            $manager->deleteSubscription($entry_id, $type, $user_id);
            
            // this for old, bottom actions 
            $objResponse->addAssign('div_subscribe_yes' . $id_suffics, 'style.display', $visible_display);
            $objResponse->addAssign('div_subscribe_no' . $id_suffics, 'style.display', 'none');
        }

        $script = "$('#save_panel_item div').css('background-image', 'url(\"%sclient/images/icons/article_panel/bookmark.svg\")');";
        $script = sprintf($script, $this->view->controller->kb_path);
        $objResponse->script($script);
        
        //$objResponse->addAlert(123);
        return $objResponse;
    }


    function loadNextEntries($offset) {

        $objResponse = new xajaxResponse();

        $limit = $this->view->dynamic_limit;
        $offset = (int) $offset;
        
        list($rows, $title) = $this->view->getRows($this->manager, $limit, $offset);
        

        if (empty($rows)) {
            $objResponse->call('DynamicEntriesScrollLoader.insert', '', 1);
            return $objResponse;
        }

        $end_reached = (int) (count($rows) <= $this->view->dynamic_limit);
        if (!$end_reached) {
            array_pop($rows);
        }

        $sname = sprintf($this->view->dynamic_sname, $this->view->dynamic_type);
        $value = $offset + count($rows);
        setcookie($sname, $value, time()+(3600*1), '/');
        $_COOKIE = $value;


        $files = ($this->view->view_id == 'files');
        $func = ($files) ? '_parseFileList' : '_parseArticleList';

        // replace bad utf
        $encoding = $this->encoding;
        $replace_utf = Utf8::badUtfLoad($this->encoding);
        if($replace_utf) {
            if($files) {
                foreach(array_keys($rows) as $k) {
                    $rows[$k]['title'] = Utf8::stripBadUtf($rows[$k]['title'], $encoding);
                    $rows[$k]['filename'] = Utf8::stripBadUtf($rows[$k]['filename'], $encoding);
                    $rows[$k]['description'] = Utf8::stripBadUtf($rows[$k]['description'], $encoding);
                }

            } else {

                $summary_limit = $this->manager->getSetting('preview_article_limit');
                foreach(array_keys($rows) as $k) {
                    $rows[$k]['title'] = Utf8::stripBadUtf($rows[$k]['title'], $encoding);
                    $rows[$k]['body'] = DocumentParser::getSummary($rows[$k]['body'], $summary_limit);
                    $rows[$k]['body'] = Utf8::stripBadUtf($rows[$k]['body'], $encoding);
                }
            }
        }
        
        $rows = $this->view->stripVars($rows);
        $tpl = $this->view->$func($this->manager, $rows, '');

        if ($tpl instanceof tplTemplatez) {
		    $data = $tpl->parsed['row'];

            // $objResponse->alert($offset);
            // $objResponse->addAlert('<pre>' . print_r($this->manager->sql_params_order, 1) . '</pre>');
            
            $objResponse->call('DynamicEntriesScrollLoader.insert', $data, $end_reached);
            $objResponse->call('DynamicEntriesScrollLoader.resetLoader');
        }

        return $objResponse;
    }
    
    
    // used when user confirm mustread and show next alert if exixts 
    function confirmMustreadEntry($mustread_id) {

        $user_id = (int) $this->manager->user_id;

        $objResponse = new xajaxResponse();
        $objResponse->addAssign('mustread_block', 'style.display', 'none');
        
        $m = new MustreadModel();
        $m->markAsConfirmed((int) $mustread_id, $user_id);
        MustreadPlugin::removeFromSession($this->manager->entry_type, $this->controller->entry_id);        
        
        // growl
        $growl_cmd = '$.growl({title: "%s", message: "%s"});';
        $msg = AppMsg::getMsg('after_action_msg.ini', 'public');
        $objResponse->AddScript(sprintf($growl_cmd, '', $msg['confirmed']['body']));
        
        $mustreads = MustreadPlugin::getSessionMustreads();
        
        if(!empty($mustreads[1])) { // forced first 
            list($entry_type, $entry_id) = explode('::', reset($mustreads[1])); 
        } elseif(!empty($mustreads[0])) {
            list($entry_type, $entry_id) = explode('::', reset($mustreads[0])); 
        }
            
        if($mustreads) {
            $objResponse->addConfirmCommands(1, $this->view->msg['mustread_next_msg']);
                 
            $view = BaseModel::$entry_type_to_view[$entry_type];       
            $link = $this->view->controller->getLink($view, false, $entry_id);
            $link = $this->controller->_replaceArgSeparator($link);
            $objResponse->addRedirect($link);
        }
        
        return $objResponse;
    }

}
?>