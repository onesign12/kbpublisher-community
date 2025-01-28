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


class KBClientAjax_entry_update extends KBClientAjax
{


    function setEntryReleased() {

        $objResponse = new xajaxResponse();
        
        $this->emanager->setEntryReleased($this->entry_id);

        return $objResponse;
    }


    function getTags($limit = false, $offset = 0) {

        $objResponse = new xajaxResponse();

        if ($limit) {
            $limit ++;
        }

        $tags = $this->emanager->tag_manager->getSuggestList($limit, $offset);
        $tags = RequestDataUtil::stripVars($tags, array(), true);

        $end_reached = !$limit || (count($tags) < $limit);
        if (!$end_reached) {
            array_pop($tags);
        }

        $data = array();
        foreach($tags as $v) {
            $data[] = array($v['id'], $v['title']);
        }

        $objResponse->addScriptCall('TagManager.updateSuggestList', $data);

        if ($end_reached) {
            $objResponse->addScriptCall('TagManager.hideAllButtons');

        } else {
            $objResponse->addScriptCall('TagManager.showAllButtons');
        }

        return $objResponse;
    }
    
    
    function saveTitle($title) {

        $objResponse = new xajaxResponse();
        
        $title = RequestDataUtil::stripVars($title, array(), 'addslashes');
        $this->manager->updateTitle($this->entry_id, $title);

        return $objResponse;
    }

}
?>