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

class SubscriptionView_form extends AppView
{

    function __construct() {
        parent::__construct();
        $this->addMsg('user_msg.ini');
        $this->addMsg('knowledgebase/common_msg.ini');
    }


    function createJsObj($categories, $ids) {

        $cat = array();
        $dis = array();

        $str = '{id: %s, title: "%s"}';

        foreach (array_keys($categories) as $k) {
            if (in_array($k, $ids)) {
                $dis[] = $k;
            }

            $cat[] = sprintf($str, $k, $categories[$k]);
        }

        $dis = implode(',', $dis);
        $cat = implode(",\n", $cat);

        return array($cat, $dis);
    }


    function getIds(&$manager) {
        $rq = new RequestData($_GET, array('id'));
        $user_id = AuthPriv::getUserId();
        $ids = array();

        if (array_key_exists($rq->type, $manager->types)) {
            $manager->setSqlParams("AND entry_type = '$rq->type'");
            $manager->setSqlParams("AND user_id = '$user_id'");
        }

        $bp =& $this->pageByPage($manager->limit, $manager->getRecordsSql());
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));

        foreach($rows as $entry) {
            $ids[] = $entry['entry_id'] ;
        }

        return $ids;
    }
}
?>