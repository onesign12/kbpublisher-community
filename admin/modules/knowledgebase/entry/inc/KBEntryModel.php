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


/*
attachment type/related type
1 - attachment
2 - inline file (link in document)
3 - exists as attachment and as inline

date_edited always changed is used for automations task only 
to check if article outdated
*/


class KBEntryModel extends CommonEntryModel
{
    var $tbl_pref_custom = 'kb_';
    var $tables = array('table'=>'entry', 'entry', 'category', 'entry_to_category',
                        'comment', 'rating', 'rating_feedback',
                        'attachment_to_entry', 'related_to_entry', 'entry_history',
                        'custom_data');

    var $custom_tables =  array(
        'file_entry', 'file_category', 'feedback', 'list_value',
        'role'=>'user_role',
        'data_to_value'=>'data_to_user_value',
        'data_to_value_string'=>'data_to_user_value_string',
        'template' => 'article_template',
        'user', 'entry_schedule',
        'lock' => 'entry_lock',
        'draft' => 'entry_draft',
        'draft_workflow' => 'entry_draft_workflow',
        'workflow_history' => 'entry_draft_workflow_history',
        'autosave' => 'entry_autosave',
        'entry_trash', 'entry_hits', 'entry_task', 'entry_featured',
        'report_summary', 'report_entry',
        'user_subscription', 
        'trigger', 'trigger_to_run',
        'entry_mustread', 'entry_mustread_to_rule'
        );


    var $use_entry_private = false;
    var $role_read_rule = 'kb_entry_to_role_read';
    var $role_read_id = 101;

    var $role_write_rule = 'kb_entry_to_role_write';
    var $role_write_id = 105;

    var $select_type = 'index';
    var $update_diff = 60; // seconds, to display updated if difference more than

    var $entry_type = 1; // means article
    var $draft_type = 7; // article's draft
    
    var $attachment_cat_name = 'Attachments';


    function __construct($user = array(), $apply_private = 'write') {
        
        parent::__construct();
        
        $this->dv_manager = new DataToValueModel();
        $this->cat_manager = new KBCategoryModel($user);
        $this->cf_manager = new CommonCustomFieldModel($this);
        
        $this->tag_manager = new CommonTagModel;
        $this->tag_manager->entry_type = $this->entry_type;
        
        $this->mr_manager = new MustreadModel;
        $this->mr_manager->entry_type = $this->entry_type;
        

        $this->user_id = (isset($user['user_id'])) ? $user['user_id'] : AuthPriv::getUserId();
        $this->user_priv_id = (isset($user['priv_id'])) ? $user['priv_id'] : AuthPriv::getPrivId();
        $this->user_role_id = (isset($user['role_id'])) ? $user['role_id'] : AuthPriv::getRoleId();

        $this->role_manager = &$this->cat_manager->role_manager;
        $this->setEntryRolesSql($apply_private);
        $this->setCategoriesNotInUserRole($apply_private);
    }

    
    // will be used in export to get all the fields
    function getRecordsSqlCategoryAll() {

        $sql = "
        SELECT e.*, {$this->sql_params_select}
        FROM
            ({$this->tbl->entry} e,
            {$this->tbl->category} cat,
            {$this->tbl->entry_to_category} e_to_cat
            {$this->sql_params_from})

        LEFT JOIN {$this->tbl->rating} r ON e.id = r.entry_id
        
        {$this->entry_role_sql_from}
        {$this->sql_params_join}

        WHERE 1
            AND e.id = e_to_cat.entry_id
            AND cat.id = e_to_cat.category_id
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}
        {$this->sql_params_group}
        {$this->sql_params_order}";

        return $sql;
    }    
    
    
    function getRecordsSqlCategory() {

        $sql = "
        SELECT
            e.id,
    		e.title,
            e.author_id,
            e.updater_id,
            e.entry_type,
            e.hits,
            e.private,
            e.date_posted,
            e.date_updated,
            e.active,
            e.meta_keywords,
            e.category_id as main_category,
            e_to_cat.sort_order AS real_sort_order,
            cat.id AS category_id,
            cat.private AS category_private,
            cat.name AS category_title,
            r.votes AS votes,
            (r.rate/r.votes) AS rating,
            UNIX_TIMESTAMP(e.date_posted) AS ts,
            UNIX_TIMESTAMP(e.date_updated) AS tsu,
            {$this->sql_params_select}

        FROM
            ({$this->tbl->entry} e,
            {$this->tbl->category} cat,
            {$this->tbl->entry_to_category} e_to_cat
            {$this->sql_params_from})

        LEFT JOIN {$this->tbl->rating} r ON e.id = r.entry_id
        
        {$this->entry_role_sql_from}
        {$this->sql_params_join}

        WHERE 1
            AND e.id = e_to_cat.entry_id
            AND cat.id = e_to_cat.category_id
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}
        {$this->sql_params_group}
        {$this->sql_params_order}";

        // echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }


    // for page by page
    function getCountRecordsSqlCategory() {
        $s = ($this->sql_params_group) ? 'COUNT(DISTINCT(e.id))' : 'COUNT(*)';
        $sql = "SELECT {$s} AS 'num'
        FROM
            ({$this->tbl->entry} e,
            {$this->tbl->category} cat,
            {$this->tbl->entry_to_category} e_to_cat
            {$this->sql_params_from})
            {$this->entry_role_sql_from}
            {$this->sql_params_join}

        WHERE e.id = e_to_cat.entry_id
        AND cat.id = e_to_cat.category_id
        AND {$this->entry_role_sql_where}
        AND {$this->sql_params}";

        //echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }


    function getRecordsSqlIndex() {

        $sql = "
        SELECT
            e.id,
			e.title,
            e.author_id,
            e.updater_id,
            e.entry_type,
            e.hits,
            e.private,
            e.date_posted,
            e.date_updated,
            e.active,
            e.meta_keywords,
            e.category_id as main_category,
            e.sort_order AS real_sort_order,
            cat.id AS category_id,
            cat.private AS category_private,
            cat.name AS category_title,
            r.votes AS votes,
            (r.rate/r.votes) AS rating,
            UNIX_TIMESTAMP(e.date_posted) AS ts,
            UNIX_TIMESTAMP(e.date_updated) AS tsu,
            {$this->sql_params_select}

        FROM
            ({$this->tbl->entry} e {$this->entry_sql_force_index},
            {$this->tbl->category} cat
            {$this->sql_params_from})

        LEFT JOIN {$this->tbl->rating} r ON e.id = r.entry_id
        
        {$this->entry_role_sql_from}
        {$this->sql_params_join}

        WHERE 1
            AND e.category_id = cat.id
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}
        {$this->sql_params_group}
        {$this->sql_params_order}";

        // echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }


    // for page by page
    function getCountRecordsSqlIndex() {
        $s = ($this->sql_params_group) ? 'COUNT(DISTINCT(e.id))' : 'COUNT(*)';
        $sql = "SELECT {$s} AS 'num'
        FROM
            ({$this->tbl->entry} e,
            {$this->tbl->category} cat
            {$this->sql_params_from})
            {$this->entry_role_sql_from}
            {$this->sql_params_join}

        WHERE e.category_id = cat.id
        AND {$this->entry_role_sql_where}
        AND {$this->sql_params}";

        //echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }


    function getRecordsSql() {
        return ($this->select_type == 'index') ? $this->getRecordsSqlIndex()
                                               : $this->getRecordsSqlCategory();
    }


    function getCountRecordsSql() {
        return ($this->select_type == 'index') ? $this->getCountRecordsSqlIndex()
                                               : $this->getCountRecordsSqlCategory();
    }


    function getCommentsNum($ids) {
        $sql = "SELECT entry_id, COUNT(*) FROM {$this->tbl->comment} 
            WHERE entry_id IN ($ids) GROUP BY entry_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function getRatingCommentsNum($ids) {
        $sql = "SELECT entry_id, COUNT(*) FROM {$this->tbl->rating_feedback} 
            WHERE entry_id IN ($ids) GROUP BY entry_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function getRating($id) {
        $sql = "SELECT entry_id, votes, (rate/votes) AS rating
        FROM {$this->tbl->rating}
        WHERE entry_id = {$id}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return ($r = $result->FetchRow()) ? $r : array('entry_id' => $id, 'votes' => 0, 'rating' => 0);
    }    


    // USERS QUESTIONS // -------------------

    function getMemberQuestionById($id) {
        $sql = "SELECT * FROM {$this->tbl->feedback} WHERE id = %d";
        $result = $this->db->Execute(sprintf($sql, $id)) or die(db_error($sql));
        return $result->FetchRow();
    }

    function setUserEntryPlaced($record_id) {
        $sql = "UPDATE {$this->tbl->feedback} SET placed = 1 WHERE id = '{$record_id}'";
        $this->db->Execute($sql) or die(db_error($sql));
    }


    // ATTACHMENT // -------------------------

    // get array with attachments filenames
    function getAttachmentById($record_id, $attachment_type = '1,3') {

        $sql = "
        SELECT
            f.id,
            f.filename
        FROM
            {$this->tbl->file_entry} f,
            {$this->tbl->attachment_to_entry} a
        WHERE 1
            AND a.entry_id IN ($record_id)
            AND a.attachment_id = f.id
            AND a.attachment_type IN ($attachment_type)";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function getAttachmentByIds($ids) {
        $sql = "SELECT f.id, f.filename FROM {$this->tbl->file_entry} f
        WHERE f.id IN ($ids) ORDER BY sort_order";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    // emode
    function getAttachmentInfoByIds($ids) {
        $sql = "SELECT f.id, f.filename, f.title, f.filesize, f.active FROM {$this->tbl->file_entry} f
        WHERE f.id IN ($ids) ORDER BY sort_order";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function getInlineAttachmentIds($str) {

        if(!DocumentParser::isLinkFile($str) && !DocumentParser::isEmbedFile($str)) {
            return array();
        }

        $search = "/\[(link|embed):file\|(\d+)\]/";
        preg_match_all($search, $str, $match);
        
        $ret = array();
        if(isset($match[2])) {
            $ret = array_unique($match[2]);
        }

        return $ret;
    }


    function getAttachmentsNum($ids) {
        $sql = "SELECT entry_id, COUNT(*) FROM {$this->tbl->attachment_to_entry} 
            WHERE entry_id IN ($ids) GROUP BY entry_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }

    // get all article ids if this file attached
    function getEntryToAttachment($file_id, $types = '1,2,3') {
        $sql = "SELECT entry_id, entry_id AS id FROM {$this->tbl->attachment_to_entry}
        WHERE attachment_id = '$file_id' AND attachment_type IN($types)";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function getAttachmentCategory() {
        $sql = "SELECT id FROM {$this->tbl->file_category} WHERE name = '{$this->attachment_cat_name}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('id');
    }
    
    
    function createAttachmentCategory($obj, $manager) {
        $obj->set('id', NULL);
        $obj->set('name', $this->attachment_cat_name);
        $obj->set('parent_id', 0);
        $obj->set('active', 0);
        $obj->set('sort_order', 'sort_end');
        
        return $manager->save($obj, 'insert');
    }


    // RELATED // ---------------------

    // get array with related, open form for update
    function getRelatedById($record_id, $related_type = '1,3') {

        $sql = "
        SELECT
            e.id,
            e.title,
            r.related_ref AS ref
        FROM
            {$this->tbl->entry} e,
            {$this->tbl->related_to_entry} r
        WHERE 1
            AND r.entry_id IN ($record_id)
            AND r.related_entry_id = e.id
            AND r.related_type IN ($related_type)
        ORDER BY r.sort_order";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    // if wrong form submission get titles for related
    function getRelatedByIds($ids) {
        $sql = "SELECT e.id, e.title FROM {$this->tbl->entry} e 
        WHERE e.id IN ($ids) ORDER BY sort_order";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function getInlineRelatedIds($str) {

        if(!DocumentParser::isLinkArticle($str)) {
            return array();
        }

        $search = "/\[link:article\|(\d+)\]/";
        preg_match_all($search, $str, $match);

        $ret = array();
        if(isset($match[1])) {
            $ret = array_unique($match[1]);
        }

        return $ret;
    }


    //is any links to this article
    function &getEntryToRelated($record_id, $types = '1,2,3', $in_bulk = false) {

        $sql = "
        SELECT entry_id, related_entry_id
        FROM
            {$this->tbl->related_to_entry} r
        WHERE 1
            AND r.related_entry_id IN ($record_id)
            AND r.related_type IN ($types)
            # to skip if reference and article also to be deleted (in bulk)
            AND r.entry_id NOT IN($record_id)";

        //echo '<pre>', print_r($sql, 1), '</pre>';
        $result = $this->db->Execute($sql) or die(db_error($sql));

        $data = array();
        if($in_bulk) {
            while($row = $result->FetchRow()) {
                $data[$row['related_entry_id']][] = $row['entry_id'];
            }
        } else {
            $data = array_keys($result->GetAssoc());
        }

        return $data;
    }


    // TEMPLATE // -------------------

    // select all ra
    function getArticleTemplateSelectRange() {
        $sql = "SELECT t.id, t.title
        FROM {$this->tbl->template} t
        WHERE t.active = 1
        AND entry_type = '{$this->entry_type}'
        ORDER BY t.title";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function getArticleTemplate($template_id) {
        $sql = "SELECT body, is_widget FROM {$this->tbl->template} WHERE id = '{$template_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    static function getIndexText($str) {
        // to include templates to index
        // if(DocumentParser::isTemplate($str)) {
        //     $cmanager = new KBClientModel;
        //     DocumentParser::parseTemplate($str, array($cmanager, 'getTemplate'));
        // }
    
        return RequestDataUtil::getIndexText($str);
    }
    

    // ACTIONS // ---------------------

    function saveAttachmentToEntry($obj, $record_id) {

        $inline_ids = $this->getInlineAttachmentIds($obj->get('body'));
        if(!$obj->getAttachment() && !$inline_ids) { return; }
        
        $i = 1;
        $cat = $obj->getAttachment();
        foreach($cat as $id) {
            $attachment[$id] = array(1, $id, $i++);
        }

        foreach($inline_ids as $id) {
            $is_attached = (isset($attachment[$id]));
            $type = ($is_attached) ? 3 : 2; // attachment type
            $sort = ($is_attached) ? $attachment[$id][2] : 0;
            
            $attachment[$id] = array($type, $id, $sort);
        }


        $sql = MultiInsert::get("INSERT {$this->tbl->attachment_to_entry} (attachment_type, attachment_id, sort_order, entry_id)
                                VALUES ?", $attachment, array($record_id));

        // echo "<pre>"; print_r($inline_ids); echo "</pre>";
        // echo "<pre>"; print_r($sql); echo "</pre>";
        // exit;

        return $this->db->Execute($sql) or die(db_error($sql));
    }


    function saveRelatedToEntry(&$obj, $record_id) {

        $inline_ids = $this->getInlineRelatedIds($obj->get('body'));
        if(!$obj->getRelated() && !$inline_ids) { return; }

        
        $related = array();
        $i = 1;
        $cat = $obj->getRelated();
        foreach($cat as $id => $v) {
            $related[$id] = array($v['ref'], 1, $id, $i++); // 1 for attachment type
        }

        // remove self related
        if(isset($related[$record_id])) {
            unset($related[$record_id]);
        }

        foreach($inline_ids as $id) {
            $is_attached = (isset($related[$id]));
            $type = ($is_attached) ? 3 : 2; // attachment type 2, just inline
            $ref = ($is_attached) ? $related[$id][0] : 0;
            $sort = ($is_attached) ? $related[$id][3] : 0;

            $related[$id] = array($ref, $type, $id, $sort);
        }


        if($related) {
            $sql = "INSERT IGNORE {$this->tbl->related_to_entry} 
                    (related_ref, related_type, related_entry_id, sort_order, entry_id) VALUES ?";
            $sql = MultiInsert::get($sql, $related, array($record_id));

            // echo '<pre>', print_r($inline_ids, 1), '</pre>';
            // echo "<pre>"; print_r($related); echo "</pre>";
            // echo "<pre>"; print_r($sql); echo "</pre>";
            // exit;
            
            return $this->db->Execute($sql) or die(db_error($sql));
        }
    }


    function addRecord($obj, $sort_values = array()) {
        
        $id = $this->add($obj);
        
        $this->saveEntryToCategory($obj->getCategory(), $id, $sort_values);
        if($obj->get('private')) {
            $this->saveRoleToEntryObj($obj, $id);
        }
        
        $this->saveRelatedToEntry($obj, $id);
        $this->saveAttachmentToEntry($obj, $id);
        $this->saveSchedule($obj->getSchedule(), $id);
        
        $this->tag_manager->saveTagToEntry($obj->getTag(), $id);
        $this->cf_manager->save($obj->getCustom(), $id);
        
        $mustread_id = $this->mr_manager->saveMustread($obj->getMustread(), $id);
        // $this->mr_manager->populateUsersToMustreadOnUpdate($mustread_id, $obj, $this);
        
        return $id;
    }


    function save($obj) {

        // sorting manipulations
        $action = (!$obj->get('id')) ? 'insert' : 'update';
        $sort_values = $this->updateSortOrder($obj->get('id'),
                                              $obj->getSortValues(),
                                              $obj->getCategory(),
                                              $action);

        // for sort order in main table now always 1
        $obj->set('sort_order', 1);

        // always change date_updated,
        // it not use will not be updated when only related or attachents changed
        // commented December 20, 2019 eleontev as we added an option to skip update date_updated
        // it seems we do not need 
        // $obj->set('date_updated', 'NOW()');


        // insert
        if($action == 'insert') {
            
            $id = $this->addRecord($obj, $sort_values);
            $this->addHitRecord($id);

        // update
        } else {

            $id = (int) $obj->get('id');

            $this->update($obj);

            $this->deleteEntryToCategory($id);
            $this->saveEntryToCategory($obj->getCategory(), $id, $sort_values);

            $this->deleteRoleToEntry($id);
            $this->saveRoleToEntryObj($obj, $id);

            $this->deleteRelatedToEntry($id);
            $this->saveRelatedToEntry($obj, $id);

            $this->deleteAttachmentToEntry($id);
            $this->saveAttachmentToEntry($obj, $id);

            $this->deleteSchedule($id);
            $this->saveSchedule($obj->getSchedule(), $id);

            $this->tag_manager->deleteTagToEntry($id);
            $this->tag_manager->saveTagToEntry($obj->getTag(), $id);

            $this->cf_manager->delete($id);
            $this->cf_manager->save($obj->getCustom(), $id);
            
            $mustread_id = $this->mr_manager->updateMustread($obj->getMustread(), $id);
            // $this->mr_manager->populateUsersToMustreadOnUpdate($mustread_id, $obj, $this);
        }

        return $id;
    }


    // DELETE RELATED // ---------------------

    function deleteEntries($record_id) {
        $sql = "DELETE FROM {$this->tbl->entry} WHERE id IN ({$record_id})";
        return $this->db->_Execute($sql) or die(db_error($sql));
    }

    function deleteRating($record_id) {
        $sql = "DELETE FROM {$this->tbl->rating} WHERE entry_id IN ({$record_id})";
        return $this->db->_Execute($sql) or die(db_error($sql));
    }

    function deleteRatingComments($record_id) {
        $sql = "DELETE FROM {$this->tbl->rating_feedback} WHERE entry_id IN ({$record_id})";
        return $this->db->_Execute($sql) or die(db_error($sql));
    }

    function deleteComments($record_id) {
        $sql = "DELETE FROM {$this->tbl->comment} WHERE entry_id IN ({$record_id})";
        return $this->db->_Execute($sql) or die(db_error($sql));
    }

    function deleteEntryToCategory($record_id, $all = true) {
        $param = ($all === true) ? 1 : "is_main = '{$all}'";
        $sql = "DELETE FROM {$this->tbl->entry_to_category} WHERE entry_id IN ({$record_id}) AND {$param}";
        return $this->db->_Execute($sql) or die(db_error($sql));
    }

    // delete by entry_id from related if any
    // so we do not have any related for this entry
    function deleteRelatedToEntry($record_id) {
        $sql = "DELETE FROM {$this->tbl->related_to_entry} WHERE entry_id IN ({$record_id})";
        return $this->db->_Execute($sql) or die(db_error($sql));
    }

    // delete from related records where this entry as related_entry
    function deleteEntryToRelated($record_id) {
        $sql = "DELETE FROM {$this->tbl->related_to_entry} WHERE related_entry_id IN ({$record_id})";
        return $this->db->_Execute($sql) or die(db_error($sql));
    }

    function deleteAttachmentToEntry($record_id) {
        $sql = "DELETE FROM {$this->tbl->attachment_to_entry} WHERE entry_id IN ({$record_id})";
        return $this->db->_Execute($sql) or die(db_error($sql));
    }


    function lockTablesOnDelete() {
        $skip = array('feedback', 'template', 'list_value', 'role');
        $tables = $this->getTables($skip);
        $this->lockTables($tables);
    }


    function delete($record_id, $update_sort = true, $on_trash = false) {

        // convert to string 1,2,3... to use in IN()
        $record_id = $this->idToString($record_id);

        //$this->lockTablesOnDelete();

        $this->deleteEntries($record_id);

        if($update_sort) {
            $this->updateSortOrderOnDelete($record_id);
        }

        $this->deleteEntryToCategory($record_id);
        $this->deleteRelatedToEntry($record_id);
        $this->deleteEntryToRelated($record_id); // delete where some articles link to this article
        $this->deleteAttachmentToEntry($record_id);
        $this->deleteSchedule($record_id);
        $this->deleteRoleToEntry($record_id);

        if(!$on_trash) {
            $this->deleteHitRecord($record_id);
            $this->deleteRating($record_id);
            $this->deleteRatingComments($record_id);
            $this->deleteComments($record_id);
            $this->deleteSubscription($record_id);
            $this->deleteSubscription($record_id, 31); // comments
            $this->mr_manager->deleteByEntryId($record_id); // mustread
        }

        $this->tag_manager->deleteTagToEntry($record_id); // tags
        $this->cf_manager->delete($record_id); // custom fields
        
        AppSphinxModel::updateAttributes('is_deleted', 1, $record_id, $this->entry_type);

        //$this->unlockTables();
    }


    // TRASH // -----------------------------

    // not delete comments. rating, susbscription, hits
    function deleteOnTrash($record_id) {
        $this->delete($record_id, false, true);
    }


    function deleteMissedComment() {
        $sql = "DELETE c FROM {$this->tbl->comment} c
        LEFT JOIN {$this->tbl->entry} e ON e.id = c.entry_id
        WHERE e.id IS NULL";

        return $this->db->_Execute($sql) or die(db_error($sql));
    }


    function deleteMissedRating() {
        $sql = "DELETE c FROM {$this->tbl->rating} c
        LEFT JOIN {$this->tbl->entry} e ON e.id = c.entry_id
        WHERE e.id IS NULL";

        return $this->db->_Execute($sql) or die(db_error($sql));
    }


    function deleteMissedRatingComment() {
        $sql = "DELETE c FROM {$this->tbl->rating_feedback} c
        LEFT JOIN {$this->tbl->entry} e ON e.id = c.entry_id
        WHERE e.id IS NULL";

        return $this->db->_Execute($sql) or die(db_error($sql));
    }


    function deleteMissedCommentSubscription() {
        $sql = "DELETE s FROM {$this->tbl->user_subscription} s
        LEFT JOIN {$this->tbl->entry} e ON e.id = s.entry_id
            AND s.entry_type IN ({$this->entry_type}, 31)
        WHERE e.id IS NULL";

        return $this->db->_Execute($sql) or die(db_error($sql));
    }


    function deleteOnTrashEmpty() {
        $this->deleteMissedComment();
        $this->deleteMissedCommentSubscription();
        $this->deleteMissedRating();
        $this->deleteMissedRatingComment();
        $this->mr_manager->deleteMissedMustreads();
    }


    function deleteOnTrashEntry($record_id) {
        $this->deleteComments($record_id);
        $this->deleteSubscription($record_id);
        $this->deleteSubscription($record_id, 31); // comments
        $this->deleteRating($record_id);
        $this->deleteRatingComments($record_id);
        $this->mr_manager->deleteByEntryId($record_id);
    }


    // PRIV // ------------------------------

    // if check priv is different for model so reassign
    function checkPriv(&$priv, $action, $record_id = false, $popup = false, $bulk_action = false) {

        $priv->setCustomAction('question', 'insert');
        $priv->setCustomAction('entry_to_related', 'select');

        $priv->setCustomAction('category', 'select'); // for popup categories
        $priv->setCustomAction('category2', 'select');
        $priv->setCustomAction('template', 'select'); // for popup article templates
        $priv->setCustomAction('role', 'select'); // for popup roles
        $priv->setCustomAction('lock', 'select'); // if entry locked
        $priv->setCustomAction('autosave', 'select'); // if autosave exists
        $priv->setCustomAction('extract', 'update');
        $priv->setCustomAction('trash', 'delete');
        $priv->setCustomAction('preview', 'select');
        $priv->setCustomAction('convert', 'insert');
        $priv->setCustomAction('tags', 'select');
        $priv->setCustomAction('kb_comment', 'select');
        $priv->setCustomAction('kb_rate', 'select');

        $priv->setCustomAction('history', 'select');
        $priv->setCustomAction('hpreview', 'select');
        $priv->setCustomAction('hfile', 'select');
        $priv->setCustomAction('diff', 'select');
        $priv->setCustomAction('rollback', 'update');
        $priv->setCustomAction('hdelete', 'update');
        
        $priv->setCustomAction('approval_log', 'select');
        $priv->setCustomAction('attachment', 'select');
        $priv->setCustomAction('draft_remove', 'select');
        $priv->setCustomAction('move_to_draft', 'delete');
        $priv->setCustomAction('edit_as_draft', 'select');
        
        $priv->setCustomAction('advanced', 'insert');
        $priv->setCustomAction('custom_field', 'insert');
        
        // bulk will be first checked for update access
        // later we probably need to change it
        // for now it works ok as we do not allow bulk without full update access
        if($action == 'bulk') {
            $bulk_manager = new KBEntryModelBulk();
            $allowed_actions = $bulk_manager->setActionsAllowed($this, $priv);
            if(!in_array($bulk_action, $allowed_actions)) {
                echo $priv->errorMsg();
                exit;
            }
        }

        // TODO new need to implement and test
        // if($record_id) {
        //     if(!$this->isRecordExists($record_id)) {
        //         $reg = &Registry::instance();
        //         $controller = &$reg->getEntry('controller');
        //         $controller->go('record_not_exists', true);
        //     }
        // }

        // as draft only allowed
        $actions = array('insert', 'update', 'clone');
        if(in_array($action, $actions)) {
            $action_to_check = ($action == 'clone') ? 'insert' : $action;
            if($priv->isPrivOptional($action_to_check, 'draft')) {
                echo $priv->errorMsg();
                exit;
            }
        }

        // check for roles
        $actions = array(
            // 'preview', 'detail', //  has_private for write only in admin now, so need to validate
            'clone', 'status', 'update', 'delete', 'move_to_draft',
            'history', 'hpreview', 'diff', 'rollback', 'hdelete'
        );

        if(in_array($action, $actions) && $record_id) {

            // entry is private and user no role
            if(!$this->isEntryInUserRole($record_id)) {
                echo $priv->errorMsg();
                exit;
            }

            // if some of categories is private and user no role
            $categories = $this->getCategoryById($record_id);
            $has_private = $this->isCategoryNotInUserRole($categories);
            if($has_private) {
                echo $priv->errorMsg();
                exit;
            }
        
        // March 24, 2021 no record_id,  nothing to do
        } elseif(in_array($action, $actions) && !$record_id) {
            $reg = &Registry::instance();
            $controller = &$reg->getEntry('controller');
            $controller->go('record_not_exists', true);
        }
        

        // check for roles on insert
        if($action == 'insert') {
            $categories = array();
            if(!empty($_POST['category'])) {
                $categories = $_POST['category'];
            }

            $has_private = $this->isCategoryNotInUserRole($categories);
            if($has_private) {
                echo $priv->errorMsg();
                exit;
            }
        }


        $sql = "SELECT 1 FROM {$this->tbl->table} WHERE id = '{$record_id}' AND author_id = '{$priv->user_id}'";
        $priv->setOwnSql($sql);

        $sql = "SELECT active AS status FROM {$this->tbl->table}  WHERE id = '{$record_id}'";
        $priv->setEntryStatusSql($sql);

        $priv->check($action);

        // set sql to select own records
        if($popup == 1) { $priv->setOwnParam(1); }
        else       { $priv->setOwnParam($this->getOwnParams($priv)); }

        $this->setSqlParams('AND ' . $priv->getOwnParam());
    }


    function getOwnParams($priv) {
        return sprintf("author_id=%d", $priv->user_id);
    }


    // concrete

    function getEntryStatusPublishedConcrete() {
        return $this->getEntryStatusPublished('article_status');
    }


    // VERSION & HISTORY // --------------------------
    
    
    static function getHistoryAllowedRevisions($ehmax = false, $skey = 'entry_history_max') {
        if(!AppPlugin::isPlugin('history')) {
            $ehmax = 0;
        }
        
        if($ehmax === false) {
            $reg =& Registry::instance();
            $setting  = &$reg->getEntry('setting');
            $ehmax = $setting[$skey];
        }

        if(strtolower($ehmax) == 'all') {
            $ret = true;
        } else {
            $ret = (int) $ehmax;
        }

        return $ret;
    }


    // TRIGGERS // ---------------------------------
    
    function getTrackedFields($obj) {
        $fields = array();

        $fields['id'] = $obj->get('id');
        $fields['type'] = $obj->get('entry_type');
        $fields['active'] = $obj->get('active');

        $fields['tag'] = $obj->getTag();

        return $fields;
    }
    
}
?>