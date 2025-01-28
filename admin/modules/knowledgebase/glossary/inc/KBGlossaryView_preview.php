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



class KBGlossaryView_preview extends AppView
{
    
    var $template = 'preview.html';
    
    
    function execute(&$obj, &$manager) {

        $tpl = new tplTemplatez($this->template_dir . $this->template);

        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        $xajax->registerFunction(array('parseBody', $this, 'ajaxParseBody'));
                
        $client_path = $this->conf['client_path'];
        if($this->conf['ssl_admin']) {
            $client_path = str_replace('http://', 'https://', $client_path);
        }
        $tpl->tplAssign('kb_path', $client_path);
        
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxParseBody($body) {
    
        $objResponse = new xajaxResponse();
        

        DocumentParser::parseCurlyBracesSimple($body);
        
        $objResponse->assign('definition', 'innerHTML' , $body);
    
        return $objResponse;    
    }    
    
}
?>