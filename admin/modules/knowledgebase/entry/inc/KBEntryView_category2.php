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


class KBEntryView_category2 extends AppView
{

    var $tmpl = 'form_category2.html';


    function execute(&$obj, &$manager) {

        $this->addMsg('user_msg.ini');


        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);


        // called from client, remove not published
        if(@$_GET['referer'] == 'client') {
            $manager->cat_manager->setSqlParams("AND c.active=1");
        }

        // all categories here, private removed in getCategoryBlockSearch
        $categories = $manager->cat_manager->getSelectRecords();

        $add_option = $this->priv->isPriv('insert', 'kb_category');
        $more = array('popup' => 1);
        $referer = WebUtil::serialize_url($this->getLink('this', 'this', false, 'category2', $more));

        $block = CommonEntryView::getCategoryBlockSearch($manager, $categories, $add_option, $referer, 'knowledgebase', 'kb_category', $this->controller);
        $tpl->tplAssign('category_block_search_tmpl', $block);

        $client_controller = &$this->controller->getClientController();
        $link = $client_controller->getLink('entry_add', 'category');
        $tpl->tplAssign('category_link', $this->controller->_replaceArgSeparator($link));

        $full_categories = $manager->getCategorySelectRangeFolow($categories);

        $caption = $this->msg['assigned_category_msg'];
        if ($obj->get('category_id')) { // main
            $tpl->tplSetNeeded('/main_category');
            $tpl->tplAssign('category_id', $obj->get('category_id'));
            $tpl->tplAssign('category_name', $full_categories[$obj->get('category_id')]);

            $caption = $this->msg['default_category_msg'];

        } else {
            $tpl->tplSetNeeded('/also_listed');
        }

        $tpl->tplAssign('caption', $caption);

        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();

        $more = array('field_id' => $this->controller->getMoreParam('field_id'));
        $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more));
        $xajax->registerFunction(array('getCategoryPrivateInfo', $this, 'ajaxGetCategoryPrivateInfo'));

        if (!empty($_GET['category_id'])) { // new
            $tpl->tplSetNeeded('/set_new_category');
            $category_id = $_GET['category_id'];

            $tpl->tplAssign('new_category', $category_id);
            $tpl->tplAssign('new_category_name', $full_categories[$category_id]);
        }

        $tpl->tplAssign($this->setCommonFormVars($obj));
        // $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function ajaxGetCategoryPrivateInfo($category_id, $category_title) {
        if(AppPlugin::isPlugin('private')) {
            return PrivatePlugin::ajaxGetCategoryPrivateInfo($category_id, $category_title, $this->manager);
        }
    }
}
?>