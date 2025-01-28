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

class KBEntryView_template extends AppView 
{
    
    var $template = 'form_template.html';
     
    var $module = 'knowledgebase';
    var $template_page = 'article_template';
    

    function execute(&$obj, &$manager) {

        $this->addMsg('user_msg.ini');
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');
        

        $this->template_dir = APP_MODULE_DIR . 'knowledgebase/entry/template/';
        $tpl = new tplTemplatez($this->template_dir . $this->template);

        // add button
        if($this->priv->isPriv('insert', $this->template_page)) {
            $referer = WebUtil::serialize_url($this->controller->getLink('all', false, false, false, array('popup' => 1)));
            $more = array('popup'=>1,'referer'=>$referer);
            
            $msg = $this->msg['add_new_template_msg'];
            $link = $this->getLink($this->module, $this->template_page, false, 'insert', $more);
            $button = array($msg => $link);
            
            $options = array('bulk_form' => false);
            $tpl->tplAssign('header', $this->commonHeaderList(false, false, $button, $options));
        }        
        
        // Create hash for live search
        $categories = $manager->getArticleTemplateSelectRange();

        $js_hash = array();
        $str = '{id: %s, title: "%s"}';
        foreach(array_keys($categories) as $k) {
            $js_hash[] = sprintf($str, $k, $categories[$k]);
        }
   
        $js_hash = implode(",\n", $js_hash); 
        $tpl->tplAssign('categories', $js_hash);
        $tpl->tplAssign('handler_name', htmlentities($_GET['field_id']));

        $tpl->tplAssign($this->setCommonFormVars($obj));
        // $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
  
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>