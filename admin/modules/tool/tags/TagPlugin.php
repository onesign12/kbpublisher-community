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

class TagPlugin
{
    
    static function getTagBlock($tag, $popup_link = '', $options = array()) {

        $is_public = (!empty($options['public'])) ? 1 : 0;
        $name = (!empty($options['name'])) ? $options['name'] : 'tag';
        $msg = (!empty($options['msg'])) ? $options['msg'] : array();
        $width = (!empty($options['width'])) ? $options['width'] : 710;

        $tpl = new tplTemplatez(APP_MODULE_DIR . 'knowledgebase/entry/template/block_tag_entry2.html');

        $tags = array();
        $str = '[%d, "%s"]';

        $tag = RequestDataUtil::stripVars($tag, array(), true);

        // set tags
        foreach($tag as $tag_id => $title) {
            $data = array(
                'name' => $name
            );
            $data['tag_id'] = $tag_id;
            $data['tag_title'] = $title;

            $tags[] = sprintf($str, $data['tag_id'], $data['tag_title']);

            $tpl->tplParse($data, 'tag_row');
        }
        
        if (empty($tag)) {
            $tpl->tplAssign('container_class', 'empty');
        }

        $v['tags'] = implode(',', $tags);

        $creation_allowed = SettingModel::getQuick(1, 'allow_create_tags');
        $v['creation_allowed'] = $creation_allowed;
        
        $v['name'] = $name;

        // source url
        $link = AppController::getAjaxLinkToFile('suggest_tag');
        $v['tag_suggest_link'] = $link;

        $v['is_public'] = $is_public;

        $tpl->tplParse(array_merge($v, $msg), 'js');

        
        // $tag_hint = ($creation_allowed) ? 'tag_hint_msg' : 'tag_hint2_msg';
        // $tag_hint_msg = ($msg) ? $msg : AppMsg::getMsg('common_msg.ini');
        // $tag_hint_msg = str_replace("'", '"', $tag_hint_msg[$tag_hint]);
        // $tpl->tplAssign('tag_hint', $tag_hint_msg);
        
        if(empty($options['hide_tag_tip'])) {
            $tooltips = AppView::getHelpTooltip('tag_tip_msg');
            $tpl->tplAssign('tag_tip_msg', $tooltips['tag_tip_msg']);
            $tpl->tplSetNeeded('/tag_tip');
        }

        $tpl->tplAssign('tag_popup_link', $popup_link);
        $tpl->tplAssign('delete_tag_button_display', ($tag) ? 'inline' : 'none');
        $tpl->tplAssign('width', $width);
        
        $tpl->tplParse($msg);

        if (!empty($msg)) {
            return $tpl;
        }

        return $tpl->tplPrint(1);
    }
    
}


?>