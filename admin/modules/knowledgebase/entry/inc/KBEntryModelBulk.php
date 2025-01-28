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



class KBEntryModelBulk extends BulkModel
{

    var $actions = array(
        'category_move', 'category_add', 'tag',
        'private', 'public', 'schedule', 'mustread',
        'type', /*'meta_description',*/ 'author',
        /*'external_link',*/ 'custom',
        'status', 'rate_reset', 'hits_reset', 'trash'
    );
    
    
    function setActionsAllowed($manager, $priv, $allowed = array()) {
    
        $actions = $this->getActionAllowedCommon($manager, $priv, $allowed);
        
        if(!AuthPriv::isAdmin()) {
            unset($actions['author']);
            unset($actions['rate_reset']);
            unset($actions['hits_reset']);
        }
        
        $this->actions_allowed = array_keys($actions);
        return $this->actions_allowed;
    }
    
    
    function setCategoryMove($cat, $ids) {
        $ids_str = $this->model->idToString($ids);
        if($cat) {
            $max_sort_values = $this->model->getMaxSortOrderValues($cat);
            $cat_id = current($cat);
            foreach($ids as $entry_id) {
                $sql = "UPDATE {$this->model->tbl->entry} 
                SET category_id = '{$cat_id}', date_updated = date_updated 
                WHERE id = ($entry_id)";
                $this->model->db->Execute($sql) or die(db_error($sql));                
            }
            
            $this->model->deleteEntryToCategory($ids_str);
            $this->model->saveEntryToCategory($cat, $ids, $max_sort_values, false);
            
            $category_mva = sprintf('(%s)', implode(',', $cat));
            $this->updateSphinxAttributes('category', $category_mva, $ids_str);
        }
    }
    
    
    function setCategoryAdd($cat, $ids) {
        $ids_str = $this->model->idToString($ids);
        if($cat) {
            $max_sort_values = $this->model->getMaxSortOrderValues($cat);
            $this->model->saveEntryToCategory($cat, $ids, $max_sort_values, true);
            
            $categories = $this->model->getCategoryByIds(implode(',', $ids));
            foreach ($ids as $id) {
                $category_mva = sprintf('(%s)', implode(',', array_keys($categories[$id])));
                $this->updateSphinxAttributes('category', $category_mva, $id);
            }
        }
    }        
    
    
    function setPrivate($values, $private, $ids) {
        $ids_str = $this->model->idToString($ids);
        $private = PrivatePlugin::getPrivateValue($private);
        
        $this->updateEntryPrivate($private, $ids_str);
        $this->model->deleteRoleToEntry($ids_str);
        
        $role_read = (!empty($values['role_read'])) ? $values['role_read'] : array();
        $role_write = (!empty($values['role_write'])) ? $values['role_write'] : array();
        $this->model->saveRoleToEntry($private, $role_read, $role_write, $ids);        
        
        $role_read_mva = (empty($role_read)) ? '(0)' : sprintf('(%s)', implode(',', $role_read));
        $role_write_mva = (empty($role_write)) ? '(0)' : sprintf('(%s)', implode(',', $role_write));
        
        $this->updateSphinxAttributes('private', $private, $ids_str);
        $this->updateSphinxAttributes('private_roles_read', $role_read_mva, $ids_str);
        $this->updateSphinxAttributes('private_roles_write', $role_write_mva, $ids_str);
    }
        
    
    function setPublic($ids) {
        $ids_str = $this->model->idToString($ids);
        $this->updateEntryPrivate(0, $ids_str);
        $this->model->deleteRoleToEntry($ids_str);
        
        $this->updateSphinxAttributes('private', 0, $ids_str);
        $this->updateSphinxAttributes('private_roles_read', '(0)', $ids_str);
        $this->updateSphinxAttributes('private_roles_write', '(0)', $ids_str);
    }
    
    
    function updateEntryPrivate($val, $ids) {
        $sql = "UPDATE {$this->model->tbl->entry} 
        SET private = '{$val}', date_updated = date_updated WHERE id IN ($ids)";
        $this->model->db->Execute($sql) or die(db_error($sql));        
    }
    
    
    function setSchedule($values_on, $values, $ids) {
        $ids_str = $this->model->idToString($ids);
        $this->model->deleteSchedule($ids_str, $this->model->entry_type);

        $values_ = array();
        $values_[1]['date'] = date('YmdHi00', strtotime($values[1]['date']));
        $values_[1]['st'] = $values[1]['st'];
        $values_[1]['note'] = $values[1]['note'];
        
        if(isset($values_on[2])) {
            $values_[2]['date'] = date('YmdHi00', strtotime($values[2]['date']));
            $values_[2]['st'] = $values[2]['st'];
            $values_[2]['note'] = $values[2]['note'];
        }

        if($values_) {
            $this->model->saveSchedule($values_, $ids, $this->model->entry_type);    
        }
    }
    
    
    function removeSchedule($ids) {
        $ids = $this->model->idToString($ids);
        $this->model->deleteSchedule($ids, $this->model->entry_type);
    }
    
    
    function setMustread($values, $ids, $action) {
        $ids_str = $this->model->idToString($ids);

        if($action == 'set' && $values) {
            $reset_users = (!empty($values['reset'])) ? true : false;
            $this->model->mr_manager->deleteByEntryId($ids_str, $this->model->entry_type, $reset_users);
            $this->model->mr_manager->saveMustread($values, $ids, $this->model->entry_type);
        
        } elseif($action == 'remove') {
            $this->model->mr_manager->deleteByEntryId($ids_str, $this->model->entry_type);
        }
    }
    
    
    function setEntryType($values, $ids) {
        $ids = $this->model->idToString($ids);
        $sql = "UPDATE {$this->model->tbl->entry} 
        SET entry_type = '$values', date_updated = date_updated WHERE id IN($ids)";
        $this->model->db->Execute($sql) or die(db_error($sql));
        
        $this->updateSphinxAttributes('entry_type', $values, $ids);
    }
    
    
    function setMetaDescription($val, $ids) {
        $ids = $this->model->idToString($ids);
        $sql = "UPDATE {$this->model->tbl->entry} 
        SET meta_description = '{$val}', date_updated = date_updated WHERE id IN ($ids)";
        $this->model->db->Execute($sql) or die(db_error($sql));                
    }
        
    
    function setExternalLink($val, $ids) {
        $ids = $this->model->idToString($ids);
        $sql = "UPDATE {$this->model->tbl->entry} 
        SET external_link = '{$val}', date_updated = date_updated WHERE id IN ($ids)";
        $this->model->db->Execute($sql) or die(db_error($sql));                
    }
    
    
    function setSortOrder($val, $ids) {
    
        $val_sort = array();
        $val_category = array();
        foreach($val as $k => $v) {
            list($entry_id, $category_id) = explode('_', $k);
            $val_sort[$entry_id] = $v;
            $val_category[$entry_id] = $category_id;
        }
        
        foreach($ids as $entry_id) {
            $sort = $val_sort[$entry_id];
            $category_id = $val_category[$entry_id];
            
            $sql = "UPDATE {$this->model->tbl->entry_to_category} 
            SET sort_order = '{$sort}'
            WHERE entry_id = '{$entry_id}' AND category_id = '{$category_id}'";
            $this->model->db->Execute($sql) or die(db_error($sql));
        }
    }
    
    
    function resetRate($ids) {
        $ids = $this->model->idToString($ids);
        $sql = "DELETE FROM {$this->model->tbl->rating} WHERE entry_id IN ($ids)";
        $this->model->db->Execute($sql) or die(db_error($sql));        
    }
    
    
    function setTags($val, $ids, $action) {
        
        $ids_str = $this->model->idToString($ids);
        
        if($action == 'remove') {
            $this->model->tag_manager->deleteTagToEntry($ids_str);
            $this->setMetaKeywords('', $ids);
        
        } elseif($val) {

            // meta keywords
            $tags = $this->model->tag_manager->getTagByIds($this->model->idToString($val));
            $tags = RequestDataUtil::addslashes($tags);
            //$keywords = $this->model->getValuesArray($tags, 'title');
            $keywords = array_values($tags);
            $tag_ids = $val;

            if($action == 'add') {

                $etags = $this->model->tag_manager->getTagToEntry($ids_str);
                foreach($ids as $entry_id) {
                    if(isset($etags[$entry_id])) {
                        
                        foreach($etags[$entry_id] as $tag_id => $title) {
                            if(in_array($tag_id, $tag_ids)) {
                                unset($etags[$entry_id][$tag_id]);
                            }
                        }
                        
                        $tag_keywords = RequestDataUtil::addslashes($etags[$entry_id]);
                        $tag_keywords = array_merge($tag_keywords, $keywords);
                        
                    } else {
                        $tag_keywords = $keywords;
                    }
                    
                    $this->setMetaKeywords($tag_keywords, $entry_id);
                }

                $this->model->tag_manager->saveTagToEntry($val, $ids);
                

            } elseif($action == 'set') {
                
                $this->model->tag_manager->deleteTagToEntry($ids_str);
                $this->model->tag_manager->saveTagToEntry($val, $ids);            
                $this->setMetaKeywords($keywords, $ids);
            }
        }
    }


    function setMetaKeywords($val, $ids) {
        return $this->model->setMetaKeywords($val, $ids);
    }
    
    
    function setAuthor($author_val, $updater_val, $ids) {
        $ids_str = $this->model->idToString($ids);
        
        $params = array();
        
        if ($author_val) {
            $params[] = 'author_id = ' . $author_val;
        }
        
        if ($updater_val) {
            $params[] = 'updater_id = ' . $updater_val;
        }
        
        if (!empty($params)) {
            $params = implode(', ', $params);
            
            $sql = "UPDATE {$this->model->tbl->entry} 
                SET {$params}, date_updated = date_updated
                WHERE id IN ($ids_str)";
            
            $this->model->db->Execute($sql) or die(db_error($sql)); 
        }               
    }
    
    
    function setCustomData($val, $ids, $form_values) {
        
        $ids_str = $this->model->idToString($ids);
        $field_id = $form_values['custom_field'];
        $cvalues = array();

        if($field_id == 'remove') {
            $this->model->cf_manager->delete($ids_str);
            return;
            
        } elseif($field_id == 'set') {
            $values = $val;
            $this->model->cf_manager->delete($ids_str);
        
        } else {
            $field_id = (int) $field_id;
            $values[$field_id] = (isset($val[$field_id])) ? $val[$field_id] : '';
            
            if(!empty($form_values['custom_append'][$field_id])) {
                $cvalues = $this->model->cf_manager->getCustomDataCurrent($ids_str, $field_id);
            }
            
            $this->model->cf_manager->deleteByEntryIdAndFieldId($ids_str, $field_id);
        }
        
        $this->model->cf_manager->save($values, $ids, $cvalues);
    }


    function status($val, $ids) {
        $ids_string = $this->model->idToString($ids);
        
        $this->model->status($val, $ids, 'active', 'date_updated');
        $this->updateSphinxAttributes('active', $val, $ids_string);
        
        // for attachment type
        if($this->model->entry_type == 2) {
            AppSphinxModel::updateAttributes('file_active', $val, $ids_string, 5);
        }
    }
    
    
    function delete($ids) {
        
        // to skip enties that have inline links to articles to be deleted
        // $related_ids in array (deleted entry[] = entry that has reference)
        $related_ids = $this->model->getEntryToRelated($this->model->idToString($ids), '2,3', true);
        if($related_ids) {
            $ids = array_diff($ids, array_keys($related_ids));
        }
        
        if($ids) {
            $this->model->delete($ids, true); // false to skip sort updating  ???
        }
        
        return array_keys($related_ids);
    }
    
    
    function trash($ids) {
        
        // to skip enties that have inline links to articles to be deleted
        // $related_ids in array (deleted entry[] = entry that has reference)
        $related_ids = $this->model->getEntryToRelated($this->model->idToString($ids), '2,3', true);
        if($related_ids) {
            $ids = array_diff($ids, array_keys($related_ids));
        }
        
        if (!empty($ids)) {
            
            $objs = array();
            foreach ($ids as $id) {
                $data = $this->model->getById($id);
                $obj = new KBEntry;
                $obj->collect($id, $data, $this->model, 'save');
                $objs[] = $obj;
            }
            
            $this->model->trash($ids, $objs);
        }

        return array_keys($related_ids);
    }
    
    
    function addSphinxRebuildTask($entry_type) {
        $rule_id = array_search('sphinx_index', $this->model->entry_task_rules);
        
        $sql = "REPLACE {$this->model->tbl->entry_task} (rule_id, entry_type, entry_id) VALUES (%d, %d, 0)";
        $sql = sprintf($sql, $rule_id, $entry_type);
        
        $this->model->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // Reset Hits // ----------------------
    
    function getHitsFieldName() {
        return 'hits';
    }
    
    
    function _resetHits($ids) {
        $field = $this->getHitsFieldName();
        $sql = "UPDATE {$this->model->tbl->entry} 
        SET {$field} = 0, date_updated = date_updated 
        WHERE id IN ($ids)";
        $this->model->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function _resetHitsEntry($ids) {
        $sql = "DELETE FROM {$this->model->tbl->entry_hits} 
        WHERE entry_id IN ($ids) AND entry_type = '{$this->model->entry_type}'";
        $this->model->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // get sum that will be deleted, excluding today ones
    function _getSumHits($ids) {
        $field = $this->getHitsFieldName();
        $sql = "SELECT SUM({$field}) AS hits FROM {$this->model->tbl->entry} WHERE id IN ($ids)";
        $result = $this->model->db->Execute($sql) or die(db_error($sql));
        return ($hits = $result->Fields('hits')) ? $hits : 0;
    }

    
    // need to corret summary report
    // set previous value for previous date - $sum_hits
    function _correctSummaryReport($ids, $report_rule, $sum_hits) {
        $sql = "UPDATE {$this->model->tbl->report_summary}
        SET prev_int = IF(prev_int < %d, 0, prev_int - %d)
        WHERE report_id = %d AND date_day = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        $sql = sprintf($sql, $sum_hits, $sum_hits, $report_rule);    
        $this->model->db->Execute($sql) or die(db_error($sql));
    }


    // function _correctEntryReport($ids, $report_rule) {
    //     $sql = "DELETE FROM {$this->model->tbl->report_entry}
    //     WHERE report_id = '{$report_rule}'
    //     AND entry_id IN ($ids)";
    //     // echo '<pre>', print_r($sql,1), '<pre>';
    //     $this->model->db->Execute($sql) or die(db_error($sql));
    // }
    
    
    function resetHits($ids) {
        $ids = $this->model->idToString($ids);
        
        $entry_type_to_report_rule = array(1 => 1, 2 => 2, 3 => 11);
        $report_rule = $entry_type_to_report_rule[$this->model->entry_type];
        $sum_hits = $this->_getSumHits($ids);
        
        $this->_correctSummaryReport($ids, $report_rule, $sum_hits);
        // $this->_correctEntryReport($ids, $report_rule);
        
        $this->_resetHits($ids);
        $this->_resetHitsEntry($ids);
    }
    
    
    /*
    // to reset all the hits
    // reset entries  
    TRUNCATE kbp_entry_hits;
    UPDATE kbp_kb_entry SET hits = 0, date_updated = date_updated;
    UPDATE kbp_file_entry SET downloads = 0, date_updated = date_updated;
    UPDATE kbp_news SET hits = 0, date_updated = date_updated;

    -- below are optional
    -- Reset Summary report for articles, files, news hits  
    DELETE FROM kbp_report_summary WHERE report_id IN (1,2,11);

    -- Reset report entry
    TRUNCATE kbp_report_entry;
    */
}
?>