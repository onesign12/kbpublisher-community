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


class KBClientAjax_preview extends KBClientAjax
{
    
    
    function ajaxParseBody($body, $first_call) {
        
        $objResponse = new xajaxResponse();
        
        $highlighter_needed = DocumentParser::isCode($body);
        
        if($first_call && $highlighter_needed) {
            $langs = DocumentParser::getLangList($body);
    
            $script = array();
            $brushes = DocumentParser::getBrushList();
            $script_str = '$.getScript("%sjscript/syntax_highlighter/scripts/shBrush%s.js")';
             
            foreach ($langs as $lang) {
                $brush_name = (isset($brushes[$lang])) ? $brushes[$lang] : 'Plain';
                $script[] = sprintf($script_str, $this->controller->client_path, $brush_name);
            }
            
            $js_str = '$.when(
                    %s,
                    $.Deferred(function(deferred) {
                        $(deferred.resolve);
                    })
                ).done(function() {
                    parseBody(0);
                });';
                
            
            $objResponse->script(sprintf($js_str, implode(',', $script)));
                
            return $objResponse;
        }
        
        
        if(DocumentParser::isTemplate($body)) {
            DocumentParser::parseTemplate($body, array($this->manager, 'getTemplate'));
        }
                
        if(DocumentParser::isCode($body)) {   
            DocumentParser::parseCode($body, $this->manager, $this->controller);    
        }

        DocumentParser::parseCurlyBraces($body);
        
        $objResponse->assign('kbp_article_body', 'innerHTML' , $body);
        $objResponse->assign('article', 'style.display' , 'block');
        
        if ($highlighter_needed) {
            $path = sprintf('%sjscript/syntax_highlighter', $this->controller->client_path);
            $clipboardSwf = sprintf('%s/scripts/clipboard.swf', $path);
                
            $js_str = 'SyntaxHighlighter.config.clipboardSwf = "%s";
                SyntaxHighlighter.config.stripBrs = true;
                SyntaxHighlighter.highlight();';
                    
            $objResponse->script(sprintf($js_str, $clipboardSwf));
        }
        
        $objResponse->call('hljs.initHighlighting');
        
        return $objResponse;    
    }
    
}
?>