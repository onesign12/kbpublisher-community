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


class SubscriptionView_types extends AppView
{

    var $tmpl = 'types_list.html';


    function execute(&$obj, &$manager) {

        $this->addMsg('user_msg.ini');

        $lview = new SubscriptionView_list();
        $lview->addMsg('user_msg.ini');
        
        $is_all = SubscriptionView_list::getAll($manager);
        $all_rows = SubscriptionView_list::getAllRowsCounts($manager);

        // remove subsc types
        $module_news = SettingModel::getQuick(0, 'module_news');
        $module_news = (!AppPlugin::isPlugin('news')) ? false : $module_news;
        if(!$module_news) {
            unset($manager->types[3]);
        }

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));

        foreach ($manager->types as $type_id => $type_key) {
            $a = array();
            
            $count_num = (in_array($type_id, $is_all)) ? $this->msg['all_msg']  : $all_rows[$type_id];
            $subscribed = ($count_num !== 0);
            
            $a['item_count'] = ($subscribed) ? sprintf('(%s)', $count_num) : '';
            $a['item_subscribed'] = ($subscribed) ? $this->msg['subscribed_msg'] : $this->msg['unsubscribed_msg'];
            $a['item_manage'] = $this->msg['manage_msg'];
            $a['item_title'] = $lview->getTitle($manager, $type_id);
            $a['do_confirm'] = 0;
            $a['options'] = '';
            
            $more = array('type' => $type_id, 'View' => 'account');
            $a['item_link'] = $this->getLink('account', 'this', 'this', 'this', $more);
    
            // news
            if($type_key == 'news') {
                
                $more['subsc'] = ($subscribed) ? 0 : 1;
                $a['item_link'] = $this->getActionLink('update', $type_id, $more);
                $a['item_manage'] = ($subscribed) ? $this->msg['unsubscribe_msg'] : $this->msg['subscribe_msg'];
                $a['item_count'] = '';
                $a['do_confirm'] = 1;
            
            // entries
            } elseif(in_array($type_key, array('articles', 'files'))) {
            
                $more = array('filter[e]' => $type_id, 'filter[s]' => 1, 'r' => 1, 'View' => 'account');
                $a['item_link'] = $this->getLink('account', 'account_list', '', '', $more);
                $tpl->tplSetNeeded('row/new_window');
            }
            
            @$a['class'] = ($i++ & 1) ? 'trLighter' : 'trDarker'; // rows colors
            $a['style'] = ($subscribed) ? '' :  $this->inactive_style;

            $tpl->tplAssign($this->msg);

            $tpl->tplParse($a, 'row');
        }
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));

        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }

}
?>