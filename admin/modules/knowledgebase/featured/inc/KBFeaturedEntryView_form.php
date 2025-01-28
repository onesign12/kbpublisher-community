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



class KBFeaturedEntryView_form extends AppView 
{
    
    var $template = 'form.html';
    

    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        $data = $manager->getByEntryId($_GET['id']);
        
        $eobj = new KBEntry;
        $emanager = new KBEntryModel;
        
        $categories = $emanager->cat_manager->getSelectRangeFolow();
        $featured_categories = $data['category'];
        
        $index_page = !empty($featured_categories[0]); 
        $tpl->tplAssign('ch_index_page', $this->getChecked($index_page));
             
        if (!empty($featured_categories)) {
            
            if (!empty($featured_categories[0])) {
                unset($featured_categories[0]);
            }
            
            $eobj->setCategory(array_keys($featured_categories));
        }
        
        $entry_categories = $emanager->getCategoryById($_GET['id']);
        
        $b_options = array(
            'no_button' => true,
            'entry_categories' => $entry_categories,
            'hide_private' => true
            );
        $tpl->tplAssign('category_block_tmpl', 
            CommonEntryView::getCategoryBlock($eobj, $manager, $categories,
                                              $this->controller->module, $this->controller->page, $b_options));
        
        $tpl->tplAssign($data);
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->msg);
        
        if($this->controller->getMoreParam('popup')) {
            $param = array('popup' => 3);
            $tpl->tplAssign('cancel_link', $this->controller->getLink('knowledgebase', 'kb_entry', false, false, $param));
        }
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>