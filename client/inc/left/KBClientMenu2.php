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

// it is old menu, should be depricated left for compability
// in v6.0 by default use ajax menu like in fixed view


class KBClientMenu2  extends KBClientMenu
{

    var $tree_menu_limit = 25; // num items (entries) shown in menu
    var $entry_menu_max_len = 0; // nums signs to leave in menu items, set to 0 to disable cut off

    // how to parse entries (not categories (folders))
    // entry    - display entry items for category where current entry open
    // category - display entry items for category where current entry open or category open
    // all      - show all tree all the time
    var $tree_entry_display = 'entry'; // entry, category, all

    var $utf_replace = true; // replace bad sign to ? to avoid error with ajax


    // to parse single items, mostly category entry
    function stripVarJs($str) {
        if($this->utf_replace) {
            $str = $this->replaceBadUtf8String($str);
        }

        return $this->view->jsEscapeString($this->view->stripVars($str));
    }


    function callLeftMenuAjax($manager, $type) {

        $ajax = &$this->view->getAjax('menu');
        $ajax->menu = &$this;
        $xajax = &$ajax->getAjax($manager);

        if ($type == 'followon') {
            $xajax->registerFunction(array('getAllFollowEntries', $ajax, 'getAllFollowEntries'));

        } else {
            $xajax->registerFunction(array('getAllTreeEntries', $ajax, 'getAllTreeEntries'));
        }
    }
}

/*
id      Number      Unique identity number.
pid     Number     Number refering to the parent node. The value for the root node has to be -1.
name     String     Text label for the node.
url     String     Url for the node.
title     String     Title for the node.
targe     String     Target for the node.
icon     String     Image file to use as the icon. Uses default if not specified.
iconO    String     Image file to use as the open icon. Uses default if not specified.
open     Boolean     Is the node open.
*/

?>