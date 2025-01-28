<?php
function processNewsSubscription() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new SubscriptionNewsModel;
    $sender = new AppMailSender();
    

    $settings = SettingModel::getQuickCron(2);
    if ($settings === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    //0 = not allowed, 2 = registered only, 3 - with any priv only
    $allow_subscription = $settings['allow_subscribe_news'];
    if(!$allow_subscription) {
        $cron->logNotify('News subscription disabled for all.');
        $cron->logNotify('%d message(s) processed.', 0);
        return $exitcode;
    }
    
    $interval = $settings['subscribe_news_interval'];
    if($interval == 'daily') {
        $mailing_hour = $settings['subscribe_news_time'];
        
        $start = mktime(intval($mailing_hour), 0, 0); // start of hour  
        $end = mktime(intval($mailing_hour), 0, 0) + 3599; // end of hour
        $timestamp = time();

        // echo 'start: ', print_r(date('H:i:s', $start), 1), "\n";
        // echo 'end: ', print_r(date('H:i:s', $end), 1), "\n";
        // echo 'timestamp: ', print_r(date('H:i:s', $timestamp), 1), "\n";
        
        $skip = ($timestamp >= $start && $timestamp <= $end) ? false : true;
        if($skip) {
            $conf =& $reg->getEntry('conf');
            $format = $conf['lang']['time_format'];
            $hour = _strftime($format, mktime(intval($mailing_hour), 0));
        
            $cron->logNotify('Skipped. The next daily newsletters scheduled at %s.', $hour);
            $cron->logNotify('%d message(s) processed.', 0);
            return $exitcode;
        }
    }

    $latest_date  = $model->getLatestEntryDate();
    if ($latest_date === false) {
        $exitcode = 0;
        return $exitcode;
    }

    $latest_date = ($latest_date) ? $latest_date : date('Y-m-d H:i:s');
    $active_status = implode(',', $manager->getUserActiveStatus());    
        
    $sent = 0;
    $skip_priv = 0;
    $subs =& $model->getSubscribers($active_status, $latest_date);
    // echo 'News Latest Date: ', $latest_date, "\n";
    // echo 'Subscribers: ', print_r($subs->GetArray(),1);
    // exit;
    
    if ($subs === false) {
        $exitcode = 0;
        $cron->logCritical('Cannot get news subscriptions.');
    
    } else {
        
        while ($su = $subs->FetchRow()) {
            
            $user_id = $su['user_id'];
            $user['user_id'] = $su['user_id'];
            
            $user['priv_id'] = $manager->getUserPrivId($user_id);
            if($user['priv_id'] === false) {
                $exitcode = 0;
                return $exitcode;
            }
            
            // not allowed for users without priv
            if(!$user['priv_id'] && $allow_subscription == 3) {
                $skip_priv++;
                continue;
            }
            
            $user['role_id'] = $manager->getUserRoleId($user_id);
            if($user['role_id'] === false) {
                $exitcode = 0;
                return $exitcode;
            }            
            
            $news =& $model->getRecentEntriesForUser($user, $su['date_lastsent']);
            if ($news === false) {
                $exitcode = 0;
                $cron->logCritical('Cannot get recent news.');
                $news = array();
            }
        
            // echo 'News: ', print_r($news, 1), "\n";
            // continue;
        
            if (count($news) > 0) {
                if ($pool_id = $sender->sendNewsSubscription($user_id, $news)) {
                    if (!$model->updateSubscription($user_id)) {
                        $exitcode = 0;
                        $cron->logCritical('Cannot update news subscription status for the user: %d.', $user_id);
                        $sender->model->deletePoolById($pool_id);  // remove pool if not updated
                    
                    } else {
                        $sent += 1;
                    }
                    
                } else {
                    $exitcode = 0;
                    $cron->logCritical('Cannot add news subscription into pool for the user: %d.', $user_id);
                }
            }
        }
        
    }
    
    if($skip_priv) {
        $cron->logNotify('%d message(s) skipped, allowed for users with any priv only.', $skip_priv);
    }    
    
    $cron->logNotify('%d message(s) processed.', $sent);

    return $exitcode;
}


function processEntriesSubscription() {
    $exitcode = 1;    

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new SubscriptionEntryModel;
    $sender = new AppMailSender();
    
    $settings = SettingModel::getQuickCron(2);
    if ($settings === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    //0 = not allowed, 2 = registered only, 3 - with any priv only
    $allow_subscription = $settings['allow_subscribe_entry'];
    if(!$allow_subscription) {
        $cron->logNotify('Content subscription disabled for all.');
        $cron->logNotify('%d message(s) processed.', 0);
        return $exitcode;
    }  
    
    $interval = $settings['subscribe_entry_interval'];
    $mailing_hour = $settings['subscribe_entry_time'];
    $mailing_weekday = $settings['subscribe_entry_weekday']; // if weekly
    $mailing_day = $settings['subscribe_entry_day']; // if monthly
    
    // echo date("D M j G:i:s T Y (N - l)"), "\n";
    
    if($interval != 'hourly') {
        
        $skip = false;
        if($interval == 'weekly') {
            $weekdays = [
                1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
                4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday' 
            ];
            
            $skip = (date('N') != $mailing_weekday);  
            $when = 'weekly, on every ' . $weekdays[$mailing_weekday];
            
        } elseif($interval == 'monthly') {
            $now_day = date('j');
            $last_day = date('t');
            
            $skip = ($now_day != $mailing_day);
            if($skip) { // check $mailing_day is more than number of days in the month
                $skip = ($now_day == $last_day && $mailing_day > $last_day) ? false : true;
            }
            
            $when = sprintf('monthly, on the %s of every month', $mailing_day);

        } else {
            $when = 'daily';
        }
        
        // check hour to sent
        if(!$skip) {
            $start = mktime(intval($mailing_hour), 0, 0); // start of hour  
            $end = mktime(intval($mailing_hour), 0, 0) + 3599; // end of hour
            $timestamp = time();
        
            $skip = ($timestamp >= $start && $timestamp <= $end) ? false : true;
        }
        
        // $skip = false; // to test without time 
        if($skip) {
            $conf =& $reg->getEntry('conf');
            $format = $conf['lang']['time_format'];
            $hour = _strftime($format, mktime(intval($mailing_hour), 0));
            
            $cron->logNotify('Skipped. The subscription scheduled %s at %s.', $when, $hour);
            $cron->logNotify('%d message(s) processed.', 0);
            return $exitcode;
        }
    }    
    
    // simple check for new/updated/commented entries
    $latest_dates = array();
    $latest_dates['article'] = $model->getLatestEntryDate('article');
    $latest_dates['file'] = $model->getLatestEntryDate('file');
    $latest_dates['comment'] = $model->getLatestEntryDate('comment');
    
    foreach($latest_dates as $v) {
        if ($v === false) {
            $exitcode = 0;
            return $exitcode;
        }
    }
    
    sort($latest_dates);
    $latest_date = ($latest_dates[2]) ? $latest_dates[2] : date('Y-m-d H:i:s');
    // echo 'latest_dates: ' . print_r($latest_dates, 1) . "\n";
    // echo 'latest_date: ' . print_r($latest_date, 1) . "\n";
    
    $allow_comments = SettingModel::getQuickCron(100, 'allow_comments');
    if ($allow_comments === false) {
        $exitcode = 0;
        return $exitcode;
    }
    $active_status = implode(',', $manager->getUserActiveStatus());
    
    $su_map = array(1=>'updated_article', 2=>'updated_file', 11=>'new_article', 12=>'new_file');
    $su_map_entry = array(1,2);
    $su_map_comment = array(1,11);


    $sent = 0;
    $skip_priv = 0;
    $subs =& $model->getSubscribers($active_status, $latest_date);
    
    if ($subs === false) {
        $exitcode = 0;
        $cron->logCritical('Cannot get entry subscribers.');

    } else {
        
        while ($su = $subs->FetchRow()) {

            $recent = array(
                'new_article'       => array(), 
                'updated_article'   => array(), 
                'commented_article' => array(), 
                'new_file'          => array(), 
                'updated_file'      => array()
                );        

            $user_id = $su['user_id'];
            $user['user_id'] = $su['user_id'];
            
            $user['priv_id'] = $manager->getUserPrivId($user['user_id']);
            if($user['priv_id'] === false) {
                $exitcode = 0;
                return $exitcode;
            }
            
            // not allowed for users without priv
            if(!$user['priv_id'] && $allow_subscription == 3) {
                $skip_priv++;
                continue;
            }
            
            
            $user['role_id'] = $manager->getUserRoleId($user['user_id']);
            if($user['role_id'] === false) {
                $exitcode = 0;
                return $exitcode;
            }        
            
            $subscription = &$model->getUserSubscriptions($user_id);
            if($subscription === false) {
                $exitcode = 0;
                return $exitcode;
            }            

            foreach($subscription as $entry_type) {

                $data_type_commented = 'commented_article';
                $data_type = $su_map[$entry_type];
                $emanager = &$model->getEntryManager($user, $entry_type);

                if(empty($ps[$entry_type])) {
                    $ps[$entry_type] = $emanager->getEntryStatusPublishedConcrete();
                    $ps[$entry_type] = implode(',', $ps[$entry_type]);                    
                }
                
                // entry
                if(in_array($entry_type, $su_map_entry)) {
                    
                    // updates
                    $res = &$model->getUpdatedEntries($user, $entry_type, $emanager, $ps[$entry_type]);                        
                    if($res) {
                        $recent[$data_type] += $res;
                    }

                    if ($res === false) {
                        $exitcode = 0;
                        $cron->logCritical('Cannot get updated entries.');
                        $recent[$data_type] = array();
                        break;
                    }
                
                    // comments
                    if($allow_comments && in_array($entry_type, $su_map_comment)) {
                            
                        $res = &$model->getCommentedEntries($user, $entry_type, $emanager, $ps[$entry_type]);                        
                        if($res) {
                            $recent[$data_type_commented] += $res;
                        }

                        if ($res === false) {
                            $exitcode = 0;
                            $cron->logCritical('Cannot get commented entries.');
                            $recent[$data_type_commented] = array();
                            break;
                        }
                    }
                    
                
                // category
                } else {

                    $cats = $model->getUserSubscribedCategories($user_id, $entry_type);

                    // subscribed to all categories
                    if(count($cats) == 1 && key($cats) === 0) {

                        $last_sent = $cats[0];
                        $subs_ = &$model->getAllEntries($last_sent, $emanager, $ps[$entry_type]);
                        
                        // comments
                        if($subs_ !== false) {
                            if($allow_comments && in_array($entry_type, $su_map_comment)) {
                                $subs_comment_ = &$model->getAllCommentedEntries($last_sent, $emanager, $ps[$entry_type]);
                                if($subs_comment_ === false) {
                                    $subs_ = false;
                                } else {
                                    $subs_ += $subs_comment_;     
                                }
                            }
                        }


                    // for concrete category
                    } else {

                        if(empty($categories[$entry_type])) {
                            $categories[$entry_type] = &$emanager->getCategoryRecords();                    
                        }

                        $subs_ = array(0=>array(), 1=>array());
                        foreach($cats as $cat_id => $last_sent) {
                            $child = $emanager->getChilds($categories[$entry_type], $cat_id);
                            $child = array_merge($child, array($cat_id));
                            $child = implode(',', $child);

                            $t_ = &$model->getCategoryEntries($last_sent, $emanager, $ps[$entry_type], $child);
                            if ($t_ === false) {
                                $subs_ = false;
                                break;
                            }

                            foreach($t_ as $type_ => $v_) {
                                if($v_) {
                                    $subs_[$type_] += $v_;
                                }
                            }
                            
                            
                            // comments
                            if($subs_ !== false) {
                                if($allow_comments && in_array($entry_type, $su_map_comment)) {
                                    $t_ = &$model->getCommentedCategoryEntries($last_sent, $emanager, $ps[$entry_type], $child);
                                    if ($t_ === false) {
                                        $subs_ = false;
                                        break;
                                    }
                                    
                                    foreach($t_ as $type_ => $v_) {
                                        if($v_) {
                                            $subs_[$type_] += $v_;
                                        }
                                    }
                                }
                            } // -> сomments
                            
                        }
                    }

                    if($subs_ === false) {
                        $exitcode = 0;
                        $cron->logCritical('Cannot get category subscription.');
                        break;
                    }

                    // new
                    if(isset($subs_[1])) {
                        $k = $su_map[$entry_type];
                        $recent[$k] += $subs_[1];
                    }

                    // updated
                    if(isset($subs_[0])) {
                        $k = $su_map[$entry_type-10];
                        $recent[$k] += $subs_[0];
                    }
                    
                    // commented
                    if(isset($subs_[2])) {
                        $k = $data_type_commented;
                        $recent[$k] += $subs_[2];
                    }                    
                }
            
            } // -> foreach($subscription as $entry_type) {


            // remove duplicates, it is possiblle if subscribed to category and to entry in this category
            // remove from new if exists in updated, concrete entry priority
            if(is_array($recent['new_article']) && is_array($recent['updated_article'])) {
                $inter = array_intersect(array_keys($recent['new_article']), 
                                         array_keys($recent['updated_article']));
                foreach($inter as $entry_id) {
                    unset($recent['new_article'][$entry_id]);
                }
            }

            if(is_array($recent['new_file']) && is_array($recent['updated_file'])) {
                $inter = array_intersect(array_keys($recent['new_file']), 
                                         array_keys($recent['updated_file']));
                foreach($inter as $entry_id) {
                    unset($recent['new_file'][$entry_id]);
                }
            }

            
            // to know if there is at least one item to send
            $recent_items = false;
            foreach(array_keys($recent) as $type) {
                if(is_array($recent[$type]) && count($recent[$type]) > 0) {
                    $recent_items = true;
                    break;                
                }
            }
            
            // echo 'User ID: ', $user_id, "\n";
            // echo '$subs: ', print_r($subs_, 1);
            // echo '$recent: ', print_r($recent, 1);
            // echo "\n===========\n";
            // continue;
            
            // have item(s) to send and no errors
            if ($recent_items && $exitcode == 1) {

                if ($pool_id = $sender->sendEntrySubscription($user_id, $recent)) {
                    if (!$model->updateSubscription($user_id)) {
                        $exitcode = 0;
                        $cron->logCritical('Cannot update subscription status for the user: %d.', $user_id);
                        $sender->model->deletePoolById($pool_id);  // remove pool if not updated
                    } else {
                        $sent += 1;
                    }
                    
                } else {
                    $exitcode = 0;
                    $cron->logCritical('Cannot add subscription into pool for the user: %d.', $user_id);
                }
            }
            
        } // -> while ($su = $subs->FetchRow()), all subscribers
        
    } // -> else
    
    
    if($skip_priv) {
        $cron->logNotify('%d message(s) skipped, allowed for users with any priv only.', $skip_priv);
    }
    
    $cron->logNotify('%d message(s) processed.', $sent);

    return $exitcode;
}


function processCommentSubscription() {
    $exitcode = 1;    

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new SubscriptionCommentModel;
    $sender = new AppMailSender();

    //0 = not allowed, 2 = registered only, 3 - with any priv only
    $allow_subscription = SettingModel::getQuickCron(100, 'allow_subscribe_comment');
    if ($allow_subscription === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    if(!$allow_subscription) {
        $cron->logNotify('Comment subscription disabled for all.');
        $cron->logNotify('%d message(s) processed.', 0);
        return $exitcode;
    }
    
    $latest_date  = $model->getLatestEntryDate();
    if ($latest_date === false) {
        $exitcode = 0;
        return $exitcode;
    }    
    
    $latest_date = ($latest_date) ? $latest_date : date('Y-m-d H:i:s');
    $active_status = implode(',', $manager->getUserActiveStatus());
    // echo 'Latest Date: ', $latest_date, "\n";


    $sent = 0;
    $skip_priv = 0;
    $subs =& $model->getSubscribers($active_status, $latest_date);

    if ($subs === false) {
        $exitcode = 0;
        $cron->logCritical('Cannot get comment subscribers.');

    } else {
        
        while ($su = $subs->FetchRow()) {    

            $user_id = $su['user_id'];
            $user['user_id'] = $su['user_id'];
            
            $user['priv_id'] = $manager->getUserPrivId($user['user_id']);
            if($user['priv_id'] === false) {
                $exitcode = 0;
                return $exitcode;
            }
            
            // not allowed for users without priv
            if(!$user['priv_id'] && $allow_subscription == 3) {
                $skip_priv++;
                continue;
            }
            
            $user['role_id'] = $manager->getUserRoleId($user['user_id']);
            if($user['role_id'] === false) {
                $exitcode = 0;
                return $exitcode;
            }
                    
            $comments =& $model->getRecentEntriesForUser($user);
            
            // echo 'Subscribers: ', print_r($su, 1), "\n";
            // echo 'Comments: ', print_r($comments, 1), "\n";
            // continue;
            
            if ($comments === false) {
                $exitcode = 0;
                $cron->logCritical('Cannot get recent comments.');
                $comments = array();
            }

            if (count($comments) > 0) {
                if ($pool_id = $sender->sendCommentSubscription($user_id, $comments)) {
                    if (!$model->updateSubscription($user_id)) {
                        $exitcode = 0;
                        $cron->logCritical('Cannot update comment subscription status for user: %d.', $user_id);
                        $sender->model->deletePoolById($pool_id);  // remove pool if not updated
                    
                    } else {
                        $sent += 1;
                    }
                    
                } else {
                    $exitcode = 0;
                    $cron->logCritical('Cannot add comment subscription into pool for the user: %d.', $user_id);
                }
            }
            
        } // -> while ($su = $subs->FetchRow()), all subscribers
        
    } // -> else
    
    
    if($skip_priv) {
        $cron->logNotify('%d message(s) skipped, allowed for users with any priv only.', $skip_priv);
    }
    
    $cron->logNotify('%d message(s) processed.', $sent);

    return $exitcode;
}


// remove child categories if user subscribed to parent
function normalizeCategorySubscription() {
    $exitcode = 1;
    
    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $model = new SubscriptionEntryModel;
    
    $deleted = 0;
    $entry_types = array('11', '12');
    
    foreach ($entry_types as $entry_type) {
    
        $subs =& $model->getAllSubscribers($entry_type);
        if ($subs === false) {
            $exitcode = 0;
            continue;
        }
                        
        $categories = $model->getAllCategories($entry_type);
        if ($categories === false) {
            $exitcode = 0;
            continue;
        }

        while ($su = $subs->FetchRow()) {
            $user_id = $su['user_id'];
            $cats_to_delete = array();  

            $cats = $model->getUserSubscribedCategories($user_id, $entry_type);
            // echo 'user id: ', $user_id, "\n", print_r($cats,1);

            if ($cats === false) {
                $exitcode = 0;
                return $exitcode;
            }

            if (!empty($cats)) {

                // subscribed to all, keep only "All KB"
                // if(in_array(0, array_keys($cats), true)) {
                if(isset($cats[0])) {
                    unset($cats[0]);
                    $cats_to_delete = array_keys($cats);

                } else {
                    $user_subscribed = array_keys($cats);

                    foreach ($user_subscribed as $id) {
                        $parents = array();
                        $parent_id = $categories[$id]['parent_id'];
                        while ($parent_id > 0) {
                            $parents[] = $parent_id;
                            $parent_id = $categories[$parent_id]['parent_id'];
                        }

                        foreach ($parents as $parent) {
                            if (in_array($parent, $user_subscribed)) {
                                $cats_to_delete[] = $id;
                                break;
                            }
                        }
                    }
                }

                // echo print_r($cats_to_delete,1);
                if (!empty($cats_to_delete)) {
                    $ret = $model->deleteSubscription($user_id, $entry_type, implode(',', $cats_to_delete));
                    if($ret) {
                        $deleted += count($cats_to_delete);
                    } else {
                        $exitcode = 0;
                        return $exitcode;
                    }
                }
            }
        
        } // -> while
        
    } // -> $entry_types
    
    $cron->logNotify('%d category subscription(s) have been removed.', $deleted);

    return $exitcode;
}

?>