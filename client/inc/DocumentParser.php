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

use Michelf\MarkdownExtra;


class DocumentParser
{
    
    // LINKS
    
    static function isLink($str) {
        return (strpos($str, '[link:') !== false) || 
               (strpos($str, '[embed:') !== false);
    }
    
    static function isLinkArticle($str) {
        return (strpos($str, '[link:article') !== false);
    }
    
    static function isLinkFile($str) {
        return (strpos($str, '[link:file') !== false); 
    }
    
    static function isEmbedFile($str) {
        return (strpos($str, '[embed:file') !== false);
    }
    
    
    static function &parseLink(&$str, $func, $manager, $inline_entries, 
                                    $article_id = false, $controller = false) {
        
        self::parseArticleLink($str, $func, $inline_entries, $article_id, $controller);
        self::parseFileLink($str, $func, $article_id, $controller);
        self::parseEmbedFile($str, $func, $article_id, $controller);    
        return $str;
    }    
    
    
    // all not existing, private stripped
    static function &parseArticleLink(&$str, $func, $inline_entries, $article_id = false, $controller = false) {
        
        if(self::isLinkArticle($str)) {
        
            if($controller->mod_rewrite == 3) {
                
                $search = "#\[link:article\|(\d+)\]#";
                preg_match_all($search, $str, $match);
                $match = (isset($match[1])) ? $match[1] : array();
                
                foreach($match as $id) {
                    if(isset($inline_entries[$id])) {
                        $row = $inline_entries[$id];        
                        $entry_id = $controller->getEntryLinkParams($id, $row['title'], $row['url_title']);
                    
                        $search = "#\[link:article\|(" . $id . ")\]#";
                        $str = preg_replace_callback(
                            $search, 
                            function ($matches) use($func, $entry_id) {
                                return call_user_func_array($func, 
                                    array('entry', false, $entry_id)
                                    );
                            },
                            $str);
                    }
                }
                
            } else {
                
                if($inline_entries == 'all') {
                    $search = "#\[link:article\|(\d+)\]#";
                } else {
                    $ids = implode('|', array_keys($inline_entries));
                    $search = "#\[link:article\|(" . $ids . ")\]#";
                }
                
                $str = preg_replace_callback(
                    $search, 
                    function ($matches) use($func) {
                        return call_user_func_array($func, 
                            array('entry', false, $matches[1])
                            );
                    },
                    $str);
            }
        
            if(DocumentParser::isLinkArticle($str)) {
                DocumentParser::parseLinkDoEmpty($str, ['article']);
            }
        }
        
        return $str;
    }
    
    
    static function &parseLinkDoEmpty(&$str, $types = ['article']) {
        $search = '#<a href="\[link:(%s)\|\d+\]">(.*?)<\/a>#';
        $search = sprintf($search, implode('|', $types));
        $str = preg_replace($search, '$2', $str);    
        return $str;
    }
    
    
    static function &parseFileLink(&$str, $func, $article_id = false, $controller = false) {
        if(self::isLinkFile($str)) {
            $search = "#\[link:file\|(\d+)\]#";
            
            $ftype = 1; // inline link behavior 1 = download, 2 = open 
            
            if(1) { // add tooltip to open or download file
                $replace = '%s" data-flink="%s" data-fparam="%s" target="%s';
                $str = preg_replace_callback(
                    $search, 
                    function ($matches) use($func, $article_id, $ftype, $replace) {

                        $link_download = call_user_func_array($func, 
                            array('afile', false, $article_id, false, array('AttachID' => $matches[1]), 1)
                            );
                            
                        $link_open = call_user_func_array($func, 
                            array('afile', false, $article_id, false, array('AttachID' => $matches[1], 'f' => 1), 1)
                            );
                        
                        if($ftype == 1) {
                            return sprintf($replace, $link_download, $link_open, 1, '_self');
                        } else {
                            return sprintf($replace, $link_open, $link_download, 0, '_blank');
                        }
                            
                    },
                    $str);
                    
            } else {
                
                $fparam = 0;
                $str = preg_replace_callback(
                    $search, 
                    function ($matches) use($func, $article_id, $fparam) {
                        return call_user_func_array($func, 
                            array('afile', false, $article_id, false, array('AttachID' => $matches[1], 'f' => $fparam), 1)
                            );
                    },
                    $str);
            }
        }
    
        return $str;
    }
    

    static function &parseEmbedFile(&$str, $func, $article_id = false, $controller = false) {
        if(self::isEmbedFile($str)) {
            $search = "#\[embed:file\|(\d+)\]#";
        
            $str = preg_replace_callback(
                $search, 
                function ($matches) use($func, $article_id) {
                    return call_user_func_array($func, 
                        array('afile', false, $article_id, false, array('AttachID' => $matches[1], 'embed' => 1))
                        );
                },
                $str);
        }
        
        return $str;
    }
    
    // <- links
    
    static function &parseMarkdown(&$str) {
        // $str = MarkdownExtra::defaultTransform($str);
        
        // $pd = new Parsedown();
        $pd = new ParsedownExtra();
        $str = $pd->text($str);
        return $str;
    }
    
    
    static function isTemplate($str) {
        return (strpos($str, '[tmpl:') !== false);
    }
    
    
    static function &parseTemplate(&$str, $func) {
        static $i = 1; $i++;
        
        if(strpos($str, '[tmpl:include') !== false) {
            // $search = "#\[tmpl:include\|(\w+)\]#e";
            // $str = preg_replace($search, "call_user_func_array(\$func, array('$1'))", $str);

            $search = "#\[tmpl:include\|(\w+)\]#";
            $str = preg_replace_callback(
                $search, 
                function ($matches) use($func) {
                    return call_user_func_array($func, array($matches[1]));
                },
                $str);
        }
        
        if(DocumentParser::isTemplate($str) && $i <= 5) {
            DocumentParser::parseTemplate($str, $func);
        }
        
        return $str;
    }
    
    
    // replace {} to &#123; &#125; to not strip by template engine
    static function &parseCurlyBraces(&$str) {
        if((strpos($str, '{') !== false)) {
            RequestDataUtil::stripVarsCurlyBraces($str, true);
        }
        
        return $str;
    }


    static function &parseCurlyBracesSimple(&$str) {        
        RequestDataUtil::stripVarsCurlyBraces($str, true, true); // last true for simple (str_replace)
        return $str;
    }


    static function _replace_glossary_item(&$string, $k, $d, $once, $case, $js_key) {
        
        $replaced = 0;
        $k2 = str_replace('/', "\\/", preg_quote($k));

        $s_delim = "[ )('\"]|\\s|&nbsp;|&#160;|&#x[aA]0;";    // delimiters between words, common for Start and End positions
        $s_skip = "(?<!skip-glossary)";    // skip marked to skip negative loop-behind assertion
        $s_notintag = "(?![^<]*>)";    // skip if it is inside tag - "(?!...)" negative loop-ahead assertion
        $s_start_delim = "{$s_skip}(>|^|{$s_delim})";
        $s_end_delim = "([.!?,:;]|$|{$s_delim}){$s_notintag}";    // char '<' is outside, because it will be "inside tag"

        $modifier = ($case) ? 'u' : 'iu';
        $s_pattern = "/{$s_start_delim}({$k2})(<|{$s_end_delim})/$modifier";
        
        $str = '$1<span class="glossaryItem _tooltip_custom_glossary" title="" onClick="$(this).tooltipster(\'content\', glosarry_items[%s]).tooltipster(\'show\');" onmouseout="closeTooltip(this);">$2</span>$3';
        
        $str = sprintf($str, $js_key + 1);

        $num_replace = ($once) ? $once : (-1);
        $string = preg_replace($s_pattern, $str, $string, $num_replace);
        
        if (strpos($string, '<span class="glossaryItem') !== false) {
            $replaced = 1;
        }
        
        return $replaced;
    }


    // $force_highlight_all to force highlight all found items, need in faq types categories 
    static function &parseGlossaryItems(&$string, $glossary, $manager, $force_highlight_all = false) {
        
        $i = 0;
        $js_key = 0;
        $js_arr = array();

        // gettting all IDs of glossary items which are used in this string
        $ids = array();
        if ($glossary) {
            uasort($glossary, 'kbpSortByLength');
            
            $num_in_array = 50;
            if(count($glossary) <= $num_in_array) {
                $pattern = "/".str_replace(array('\|', '/'), array('|', "\\/"), preg_quote(implode('|', $glossary)))."/iu";
                preg_match_all($pattern, $string, $match); // PREG_OFFSET_CAPTURE
    
            // 2014-04-29, eleontev added array_chunk to reduce preg pattern
            } else {
                $match_collect = array();
                $gl = array_chunk($glossary, $num_in_array, true);
                foreach(array_keys($gl) as $k) {
                    $pattern = "/".str_replace(array('\|', '/'), array('|', "\\/"), preg_quote(implode('|', $gl[$k])))."/iu";
                    preg_match_all($pattern, $string, $match2); // PREG_OFFSET_CAPTURE
                    if (!empty($match2[0])) {
                        $match_collect = array_merge($match_collect, $match2[0]);
                    }
                }

                $match[0] = $match_collect;
            }
            
            
            if (!empty($match[0])) {
                $match[0] = array_unique($match[0]);
                $pattern = "/".str_replace(array('\|', '/'), array('|', "\\/"), preg_quote(implode('|', $match[0])))."/iu";
                $ids = array_keys(preg_grep($pattern, $glossary));
            }
        }

        // echo '<pre>', print_r($string   ,1), '<pre>';
        // echo '<pre>', print_r($pattern,1), '<pre>';
        // echo '<pre>', print_r($ids,1), '<pre>';
        // exit;

        if ($ids) {
            $glossary = $manager->getGlossaryDefinitions(implode(',', $ids));
            uksort($glossary, 'kbpSortByLength'); // to sort by strlen, shorter first            

            // replacing all glossary items in string
            foreach (array_keys($glossary) as $k) {
                $definition = addslashes(str_replace(array("\r\n", "\n", "\r"), ' ', $glossary[$k]['d']));
                $highlight = ($force_highlight_all) ? 0 : $glossary[$k]['h']; 
                $replaced = DocumentParser::_replace_glossary_item($string /* passed by ref */, $k, $definition, $highlight, $glossary[$k]['case'], $js_key);
                if ($replaced > 0) {
                    $js_key += 1;
                    $js_arr[$js_key] = $definition;
                }
            }
            
            // adding needed glossary items into javascript
            if (count($js_arr) > 0) {
                $js_str = '
                <script>
                    var glosarry_items = new Array;
                    %s
                </script>' . "\n\n";            
                
                $js_str2 = "glosarry_items[%s] = '<span class=\"glossaryItemTooltip\">%s</span>';";
                foreach(array_keys($js_arr) as $k) {
                    $js_terms[] = sprintf($js_str2, $k, $js_arr[$k]);
                }

                $js = sprintf($js_str, implode("\n\t\t\t\t", $js_terms));
                $string = $string . $js;                
            }
        }
        
        return $string;
    }


    static function stripHTML($str) {
        
        $search[] = '#<script[^>]*>.*?<\/script>#si';
        $search[] = '#\[tmpl:.*?\]#i';
        $search[] = '#[-_]{2,}#';
        $search[] = '#\[code=(\w*)\].*?\[\/code\]#si';
        $search[] = '#<style[^>]*>.*?<\/style>#si';
        $str = preg_replace($search, '', $str);
        
        $values = array(
            '<br>', '<BR>',  '<br />',  '<br/>', '<li>', '<p>', '<P>', 
            '&nbsp;', "\n", "\r", "\t", '  '
            );
            
        return strip_tags(str_replace($values, ' ', $str));
    }


    static function getSummary($str, $num_signs = 150) {
    
        if($num_signs === 'all') { return $str; }
        if(!$num_signs) { return; }
        
        $str = DocumentParser::stripHTML($str);

        DocumentParser::parseCurlyBracesSimple($str);
        
        return BaseView::getSubstring($str, $num_signs);
    }
 
 
    static function getSummaryQuick($str, $num_signs = 150) {
        DocumentParser::parseCurlyBracesSimple($str);
        return BaseView::getSubstring($str, $num_signs);
    }
    
    
    static function getTitleSearch($str, $words) {

        $words2 = preg_quote($words);
        preg_match_all('#\w{3,}#iu', $words2, $m);

        if(empty($m[0])) {
            return $str;
        }
        
        if(preg_match('#^"(.*?)"$#iu', $words2, $m2)) { // search with quotes, match complete string
            $keywords = array(preg_quote($m2[1]));
            $search = '#(%s)#iu'; // it will not highlight quotes !
        } else {
            $keywords = array_unique($m[0]);
            $search = '#\b(%s)\b#iu';
        }
            
        $search = sprintf($search, implode('|', $keywords));  
        $replace = '<span class="highlightSearch">$0</span>';

        return preg_replace($search, $replace, $str);
    }
    
    
    static function getSummarySearch($str, $words, $num_signs = 150) {

        if(!$num_signs) { return; }

        DocumentParser::parseCurlyBracesSimple($str);
        $str = DocumentParser::stripHTML($str);

        $words2 = preg_quote(trim($words));
        preg_match_all('#\w{3,}#iu', $words2, $m);

        // nothing to highlight
        if(empty($m[0])) {
            return DocumentParser::getSummaryQuick($str, $num_signs);
        }

        if(preg_match('#^"(.*?)"$#iu', $words2, $m2)) { // search with quotes, match complete string
            $keywords = array(preg_quote($m2[1]));
        } else {
            $keywords = array_unique($m[0]);
        }
        
        // $search = '#(?:\S+\s+){0,5}\b(%s)\b(?:\s+\S+){0,5}#imu';
        $search = '#(?:\S+\s+\W?){0,5}(%s)(?:\W?\s+\S+){0,5}#ium'; // added two \W? to catch - ", etc
        $search = sprintf($search, implode('|', $keywords));    
        preg_match_all($search, $str, $m);
        // echo '<pre>', print_r($m, 1), '</pre>';

        if(empty($m[0])) {
            return DocumentParser::getSummaryQuick($str, $num_signs);
        }

        $num_slice = ceil($num_signs/100);
        $sentences = array_slice($m[0], 0, $num_slice);
        
        if(_strlen($str) > $num_signs) {
            $str = '... ' . implode(' ... ', $sentences) . ' ...';
        } else {
            $str = implode(' ... ', $sentences);
        }
        
        
        $str = BaseView::getSubstring($str, $num_signs, ' ...');
        
        $search = '#\b(%s)\b#iu';
        $search = sprintf($search, implode('|', $keywords));    
        $replace = '<span class="highlightSearch">$0</span>';

        return preg_replace($search, $replace, $str);
    }
    
    
    static function tidyCleanRepair(&$str, $charset) {
        
        if(!function_exists('tidy_parse_string')) {
            return $str;
        }
        
        $options = array(
            'clean' => true, 
            // 'output-xhtml' => true, 
            'output-html' => true,
            'word-2000' => true // Removes all proprietary data when an MS Word document has been saved as HTML
        );
        
        $str = tidy_parse_string($str, $options, $charset);
        tidy_clean_repair($str);
        return $str;
    }
    
    
    static function isCode($str) {
        return (stripos($str, '[code') !== false);
    }
    
    
    static function isCode2($str) {
        return (stripos($str, '<code') !== false);
    }
    
    
    static function parseCode(&$str, $manager, $controller, $files = true) {

        $langs = self::getLangList($str, $manager);
        if (empty($langs)) {
            return $str;
        }
        
        
        // replacing
        $search = '#\[code="?(' . implode('|', $langs) . ')"?\](.*?)\[\/code\]#si';
        if($manager->getSetting('article_block_position') == 'bottom') {
            $replace = '<pre class="brush: $1;">$2</pre>';
        } else {
            $replace = '<div style="margin-right: 200px;"><pre class="brush: $1;">$2</pre></div>';
        }
        $str = preg_replace($search, $replace, $str);
        // $str = str_replace(array("<br />", "<p>", "<\p>"), "", $str);
        
        
        if ($files) {
            $path = sprintf('%sjscript/syntax_highlighter', $controller->client_path);
        
            $js = array();
            $js[] = sprintf('<script src="%s/scripts/shCore.js"></script>', $path);
    
            $css = array();
            $css[] = sprintf('<link href="%s/styles/shCore.css" rel="stylesheet" type="text/css" />', $path);
            $css[] = sprintf('<link href="%s/styles/shThemeDefault.css" rel="stylesheet" type="text/css" />', $path);
    
            
            $brush = array();
            $brushes = self::getBrushList();
            $brush_str = '<script src="%s/scripts/shBrush%s.js"></script>'; 
            foreach ($langs as $lang) {
                $brush_name = (isset($brushes[$lang])) ? $brushes[$lang] : 'Plain';
                $brush[] = sprintf($brush_str, $path, $brush_name);
            }
            
            $clipboardSwf = sprintf('%s/scripts/clipboard.swf', $path);
            $js_exec = '<script>
                        // $(document).ready(function(){
                            SyntaxHighlighter.config.clipboardSwf = "'.$clipboardSwf.'";
                            SyntaxHighlighter.config.stripBrs = true;
                            SyntaxHighlighter.all();
                        // });
                        </script>%s';
    
            $js[] = sprintf($js_exec, implode("\n", $brush)); 
            $str = implode("\n", $css) . "\n" . implode("\n", $js) . "\n" . $str;
        }
    }
        
    
    static function parseCode2(&$str, $controller) {
        $files = self::parseCode2GetFiles($controller);
        $str .= $files . '<script>
            // $(document).ready(function() {
            //    hljs.initHighlightingOnLoad();
            // });
            
            hljs.initHighlightingOnLoad();
        </script>';
    }
    
    
    static function parseCode2GetFiles($controller) {
        $str = '<link rel="stylesheet" href="%stools/ckeditor_custom/plugins/codesnippet/lib/highlight/styles/default.css">
            <script src="%stools/ckeditor_custom/plugins/codesnippet/lib/highlight/highlight.pack.js"></script>';
        
        $str = sprintf($str, APP_ADMIN_PATH, APP_ADMIN_PATH);
        return $str;
    }
    
    
    static function getBrushList() {
        $brushes = array(
            'as3' => 'AS3',              'actionscript3' => 'AS3',    'bash' => 'Bash',      
            'shell' => 'Bash',           'cf' => 'ColdFusion',        'coldfusion' => 'ColdFusion', 
            'c-sharp' => 'CSharp',       'csharp' => 'CSharp',        'cpp' => 'Cpp',       
            'c' => 'Cpp',                'css' => 'Css',              'delphi' => 'Delphi', 
            'pas' => 'Delphi',           'pascal' => 'Delphi',        'diff' => 'Diff', 
            'patch' => 'Diff',           'erl' => 'Erlang',           'erlang' => 'Erlang',
            'groovy' => 'Groovy',        'js' => 'JScript',           'jscript' => 'JScript',
            'javascript' => 'JScript',   'java' => 'Java',            'jfx' => 'JavaFX',
            'javafx' => 'JavaFX',        'perl' => 'Perl',            'pl' => 'Perl',
            'php' => 'Php',              'plain' => 'Plain',          'text' => 'Plain',
            'ps' => 'PowerShell',        'powershell' => 'PowerShell','py' => 'Python',
            'python' => 'Python',        'rails' => 'Ruby',           'ror' => 'Ruby',
            'ruby' => 'Ruby',            'scala' => 'Scala',          'sql' => 'Sql',
            'vb' => 'Vb',                'vbnet' => 'Vb',             'xml' => 'Xml',
            'xhtml' => 'Xml',            'xslt' => 'Xml',             'html' => 'Xml'
        );
        
        return $brushes;
    }
    
    
    static function getLangList($str) {
        $brushes = self::getBrushList();
        $brushes_preg = implode('|', array_keys($brushes));
        $code_pattern = '#\[code="?(' . $brushes_preg . ')"?\](.*?)\[\/code\]#si';
        preg_match_all($code_pattern, $str, $matches);
        
        $langs = (!empty($matches[1])) ? $matches[1] : array();
        return $langs;
    }
    
    
    static function &parseCodePrint(&$str) {
        $search = '#\[code=\w*\](.*?)\[\/code\]#si';
        $replace = '<div style="border: 1px solid #999; padding: 10px;"><code>$1</code></div>';
        $str = preg_replace($search, $replace, $str);
        return $str;
    }
  

    static function &parseCodePreview(&$str) {
        $search = '#\[code=\w*\](.*?)\[\/code\]#si';
        $msg = 'This block will be parsed by syntax highlighter in normal view.';
        $replace = '<div style="border: 1px solid #999; padding: 10px;">'.$msg.'<br /><code>$1</code></div>';
        $str = preg_replace($search, $replace, $str);
        return $str;
    }
     
 
    static function isToc($body, $body_index, $manager) {
        $ret = false;
        $tags = $manager->getSetting('toc_tags');
        
        if(!$manager->getSetting('toc_generate') || !$tags) {
            return $ret;
        }
        
        $pre_tags = implode('|', explode(',', preg_quote($tags)));
        preg_match_all("#<({$pre_tags})[^>]*>#i", $body, $matches);
        $tags_num = count($matches[0]);    
    
        if($tags_num >= $manager->getSetting('toc_tag_limit')) {
            if(_strlen($body_index) >= $manager->getSetting('toc_character_limit')) {
                $ret = $tags;
            }
        }
        
        return $ret;
    }


    static function getToc($options = array()) {
    
        $preview = (!empty($options['preview'])) ? 1 : 0;
        $title = (!empty($options['title'])) ? $options['title'] : '{contents_msg}';
        $format = (!empty($options['js'])) ? 'js' : 'html';
        $display = (empty($_COOKIE['kb_hide_toc_']) || $preview) ? 'block' : 'none';
        $data_toc = (!empty($options['data_toc'])) ? $options['data_toc'] : 'kbp_article_body';
        $data_toc_headings = (!empty($options['data_toc_headings'])) ? $options['data_toc_headings'] : 'h1,h2,h3';
        
        if($format == 'html') {
            
            $html = array();
            $html[] = '<div id="toc_container">';
            $html[] = '<div><a href="#toc" class="atoc" onclick="toggleToc(\'toc_content\', \'kb_hide_toc_\'); return false;">%s:</a></div>';
            $html[] = '<div id="toc_content" style="display: %s;">';
            $html[] = '<ol id="toc" data-toc="#%s" data-toc-headings="%s"></ol>';
            $html[] = '</div></div>';
            $toc = implode('', $html);
            $toc = sprintf($toc, $title, $display, $data_toc, $data_toc_headings);
            
        } else {
            
            $js = '$("#toc").toc({content: "#%s", headings: "%s"});';
            $toc = sprintf($js, $data_toc, $data_toc_headings);
        }

        return $toc;
    }

    
    static function getTocJs() {
        
    }

}


function kbpSortByLength($a, $b) {
    return (_strlen($a) < _strlen($b)) ? 1 : 0;
}

?>