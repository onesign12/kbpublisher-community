<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+
// | This source file is free software; you can redistribute it and/or    |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This source file is distributed in the hope that it will be useful,  |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// +----------------------------------------------------------------------+
//
// $Id: tpl_templatez.php,v 1.1.1.1 2004/09/15 16:57:44 root Exp $

/**
 * tplTemplatez is a class used to separate PHP and HTML.
 * 
 * Easy to use. Very effective in hiding or showing blocks. 
 * No code or special instructions in template.
 * Ability to parse nested blocks.
 *
 * @since 21/03/2003
 * @author Evgeny Leontiev <eleontev@gmail.com>
 * @access public
 */


class tplTemplatez {
    
    var $clean_html            = false;            // deleting empty lines and all tabs after parsing. In some cases you needn't to do this. 
    var $strip_vars            = false;            // use true to strip all unassigned vars 
                                                // or use more flexible decision - function tplStripVars()
    var $strip_double        = false;                                            
    
    var $tpl_file;
    var $tpl_box;
    var $is_setted;
    
    var $tpl_blocks         = array();            // main blocks, you can iterate this block
    var $alt_blocks         = array();            // alternative blocks
    var $need_alt_blocks    = array();
    var $parsed             = array();            // parsed blocks
    var $vars                 = array();
    //var $global_vars        = array();            // they will be parsed in tplPrint
    var $is_nested            = false;
    
    var $error_pref            = '<font color="#FF0000">TEMPLATEZ</font>';     // error prefix
    
    var $s_var_tag            = "{";                // var tags
    var $e_var_tag             = "}";

    var $s_block_tag         = "<tmpl:";            // block tags
    var $e_block_tag         = "</tmpl:";
    var $ee_block_tag         = ">";

    var $s_box_tag             = "<!--tmpl:";         // template file can has as many different templates as you want 
    var $e_box_tag             = "-->";               // they should be separated with these tags
    
    var $s_include_tpl_tag     = "<INCLUDE_TPL=";    // they should be separated with these tags
    //var $s_include_php_tag     = "<INCLUDE_PHP:";    // they should be separated with these tags
    var $e_include_tag        = ">";
    
    var $s_skip_strip_vars_tag    = '<skip_strip>';
    var $e_skip_strip_vars_tag    = '</skip_strip>';
    
    
    /*-----------------------------------------------------------------------------\
    |                           public functions                                   |
    \-----------------------------------------------------------------------------*/

   /**
    * FUNCTION: tplTemplatez - constructor.
    *
    * Loading the template file.
    * Make all the necessary preparation with template.
    *
    * @param string $tplfile path to template file
    * @param string $box certain part of page to be a template file
    * @return boolean true
    * @access public
    */
    function __construct($tplfile, $box = null) {
        
        $this->tpl_blocks['content'] = file_get_contents($tplfile);
        // $this->tpl_blocks['content'] = implode('',file($tplfile)); // use this before PHP 4.3

        $this->tpl_file = $tplfile;
        $this->tpl_box  = $box;
        
        if(isset($_SERVER["PHP_SELF"])) {
            $this->vars['PHP_SELF'] = $_SERVER["PHP_SELF"]; // can be useful 
        }
    
        if(isset($_SERVER['QUERY_STRING'])) {
            $this->vars['CUR_PAGE'] = $_SERVER["PHP_SELF"].'?'.$_SERVER['QUERY_STRING']; // can be useful 
        }
        
        // just only what in box
        if ($box) {
            if (strpos($this->tpl_blocks['content'],$this->s_box_tag . $box .$this->e_box_tag) === false) {
                trigger_error($this->error_pref. " Can't find box name <b>".$box."</b> in template <b>".$this->tpl_file."</b>");
            }

            $chunk = explode($this->s_box_tag . $box .$this->e_box_tag, $this->tpl_blocks['content']);
            $this->tpl_blocks['content'] = $chunk[1];
            unset($chunk);
        }
        
        $this->_tplInclude();
        $this->_tplSetAltBlocks('content');
        
        return true; 
    }
    

   /**
    * FUNCTION: tplSetOptions - setting options for template.
    *
    * @param array $options key is an option and vakue is a value
    * @access public
    */
    function tplSetOptions($array) {
        foreach($array as $k => $v) {
            $this->$k = $v;
        }
    }
    
    
   /**
    * FUNCTION: tplParse -- parses template block, replace {var} with values.
    *
    * The template can has a lot of blocks, you needn't call this function to parse each of them
    * Usually should be called to parse dynamic block, nested dynamic block or whole template
    * To hide or show block use tplSetNeeded($alt_blocks) instead.
    *
    * @param array $array  array with template vars
    * @param string $block  block to parse, by default the whole template
    * @return true/false
    * @access public
    */    
    function tplParse($array = array(), $block = 'content') {
        
        $this->_tplSetBlock($block);
        
        $block = ($pos = strrpos($block, '/')) ? substr($block, $pos+1) : $block;
        $temp = $this->tpl_blocks[$block]; // assign part of tpl // changed
        
        // execute if there are need_alt_blocks
        if (isset($this->need_alt_blocks[$block])) {
            foreach($this->need_alt_blocks[$block] as $k => $v) {
                $s_delim = $this->s_block_tag . $v . $this->ee_block_tag;
                $e_delim = $this->e_block_tag . $v . $this->ee_block_tag; 
                
                if (strpos($temp, $s_delim) === false || strpos($temp, $e_delim) === false) {
                    trigger_error($this->error_pref." Can't find block name <b>".$v."</b> in template <b>".$this->tpl_file."</b>");
                } 
                $temp = str_replace(array($s_delim, $e_delim), '', $temp); // strip tags
            }    
            $skip_blocks = array_diff($this->alt_blocks[$block], $this->need_alt_blocks[$block]); // array of not needed blocks
            $temp = $this->_tplStripBlocks($skip_blocks,$temp);// strip not needed blocks
        }
        
        elseif (isset($this->alt_blocks[$block])) { 
            $temp = $this->_tplStripBlocks($this->alt_blocks[$block],$temp);// strip not needed blocks
        }
        
        
        $array = (!is_array($array)) ? array() : $array;
        foreach(array_merge($array, $this->vars) as $k => $v) {
            $search[] = $this->s_var_tag . $k . $this->e_var_tag;
            $replace[] = $v;
        } 

        if(!isset($this->parsed[$block])) {
            $this->parsed[$block] = '';
        }

        @$this->parsed[$block] .= str_replace($search, $replace, $temp);
        unset($this->need_alt_blocks[$block]);
        return true;
    } 
    
    
    function &tplParseString($string, $vars_ar = array()) {
        if (strpos($string, $this->s_var_tag) !== false && strpos($string, $this->e_var_tag ) !== false) {
            foreach(array_merge($vars_ar, $this->vars) as $k => $v) {
                $search[] = $this->s_var_tag . $k . $this->e_var_tag;
                $replace[] = $v;
            }
            
            $string = (isset($search)) ? str_replace($search, $replace, $string) : $string;
        }
        
        return $string;
    }    
    
    
    // need to parse parsed content, replace someting, etc
    // call after tplParse
    // example: 
    // $tpl->tplParse(array_merge($data, $msg));  
    // $tpl->tplPostParse(array($this, 'replaceMSWordCharacters'));
    function tplPostParse($func, $params = array(), $block = false) {
        
        $blocks = ($block) ? array($block) : array_keys($this->parsed);
        
        foreach($blocks as $v) {
            array_unshift($params, $this->parsed[$v]);
            $this->parsed[$v] = call_user_func_array($func, $params);
        }
    }
    
    
    /**
    * FUNCTION: tplParseVars -- Populate block(s) with values
    *
    * Call it to parse parsed blocks, to skip parsing in a row.
    * examle: foreach ... { $tpl->tplParse($row, 'row');  }
    * $tpl->tplParseVars($this->msg, 'row');
    *
    * @param array $array values to be populated
    * @param string $block block name (optional)
    * @access public
    */
    function tplParseVars($array, $block = false) {
        
        $blocks = ($block) ? array($block) : array_keys($this->parsed);
        
        foreach(array_merge($array, $this->vars) as $k => $v) {
            $search[] = $this->s_var_tag . $k . $this->e_var_tag;
            $replace[] = $v;
        }
        
        foreach($blocks as $block) {
            $this->parsed[$block] = str_replace($search, $replace, $this->parsed[$block]);
        }
    }
    
    /**
    * FUNCTION: tplSetNeeded -- Set what blocks should be parsed (hide or show).
    *
    * The syntax for $alt_blocks (alternate blocks) is parent_block/alt_block_1/alt_block_2/...
    * if parent block is whole template the syntax is /alt_block_1/alt_block_2/...
    *
    * @param string $alt_blocks parent_block/alt_block_1/alt_block_2/...
    * @access public
    */
    function tplSetNeeded($alt_blocks) {
        
        $chunk = explode('/', $alt_blocks);
        $block = ($chunk[0] == '') ? 'content' : $chunk[0];
        unset($chunk[0]);

        foreach($chunk as $v)
            $this->need_alt_blocks[$block][] = $v;
    } 
    
   
       /**
    * FUNCTION: tplSetNeededGlobal -- Set what blocks should be parced (hide or show).
    *
    * For example you have 5 blocks <tmpl:if_blah>...</tmpl:if_blah> and they are in
    * different dinamyc or nested blocks. If you want show these blocks just call
    * tplSetNeededGlobal('if_blah') after tplTemplatez($tplfile) and that's all, they will be printed.
    *
    * @param string $alt_blocks alt_block_1/alt_block_2/...
    * @access public
    */
    function tplSetNeededGlobal($alt_blocks) {
        
        $chunk = explode('/', $alt_blocks);
        
        foreach($chunk as $k => $v) {
            $s_delim = $this->s_block_tag . $v . $this->ee_block_tag;
            $e_delim = $this->e_block_tag . $v . $this->ee_block_tag; 
            
            if (strpos($this->tpl_blocks['content'], $s_delim) === false || strpos($this->tpl_blocks['content'], $e_delim) === false) {
                trigger_error($this->error_pref." Can't find block name <b>".$v."</b> in template <b>".$this->tpl_file."</b>");
            } 
            
            $this->tpl_blocks['content'] = str_replace(array($s_delim, $e_delim), '',$this->tpl_blocks['content']); // strip tags
        }
        
        $this->alt_blocks['content'] = array_diff($this->alt_blocks['content'],$chunk);
    }
   
   
   /**
    * FUNCTION: tplSetNested -- Set nested block (loop).
    *
    * It should be called after parsing nested block
    * The syntax for $block is parent_block/nested_1/nested_2/...
    * if parent block is whole template the syntax is /nested_1/nested_2/...
    *
    * @param string $block parent_block/nested_1/nested_2/...
    * @access public
    */
    function tplSetNested($block) {
        
        $this->_tplSetBlock($block);
        $this->is_nested = true;  // a flag, we have nested block, to call _tplStripTags()
        
        $chunk = explode('/', $block);
        $num = count($chunk);
        $block = ($chunk[0] == '') ? 'content' : $chunk[$num - 2]; // before last slash
        $sub_block = $chunk[$num - 1];                                // after last slash
        
        $s_delim = $this->s_block_tag . $sub_block . $this->ee_block_tag;
        $e_delim = $this->e_block_tag . $sub_block . $this->ee_block_tag;
        
        // replace sub block in block, stay delims
        $this->tpl_blocks[$block] = $this->_tplSubstrReplace($this->tpl_blocks[$block], @$this->parsed[$sub_block], $s_delim, $e_delim, false);
        unset($this->parsed[$sub_block]);
    } 
    

   /**
    * FUNCTION: tplAssign -- assigns variables to be used by the template.
    *
    * If $var is an array, then it will treat it as an associative array
    * using the keys as variable names and the values as variable values.
    *
    * @param mixed $var to define variable name
    * @param mixed $value to define variable value
    * @access public
    */
    function tplAssign($var, $value = false) {
        
        if(is_array($var)) {
            foreach($var as $k => $v) {
                $this->vars[$k] = $v;
            }
        } else {
            if($value === false) {
                trigger_error($this->error_pref. " Can't assign value for var - <b>".$var."</b>. Missing argument 2 for tplAssign();");
            } else { 
                $this->vars[$var] = $value; 
            }
        }
    }
    
    
   /**
    * FUNCTION: tplPrint -- Print or return the result of parsing template.
    *  
    * @param bool $to_return use true to return result whithout printing
    * @return string template
    * @access public
    */
    function &tplPrint($to_return = false) {
        
        if($this->strip_vars) { $this->tplStripVars(); }
        
        // strip not defined blocks
        if(isset($this->parsed['content'])) {
            $temp = &$this->parsed['content'];
        } elseif(isset($this->alt_blocks['content'])) {
            $temp = $this->_tplStripBlocks($this->alt_blocks['content'], $this->tpl_blocks['content']);
        } else {
            $temp = &$this->tpl_blocks['content'];
        }
        
        foreach(array_keys($this->parsed) as $k) {
            if($k == 'content') continue;
            
            $s_delim = $this->s_block_tag . $k . $this->ee_block_tag;
            $e_delim = $this->e_block_tag . $k . $this->ee_block_tag;
            $temp = $this->_tplSubstrReplace($temp, $this->parsed[$k], $s_delim, $e_delim);
        }
        
        if($this->clean_html)    { $temp = &$this->_tplCleanHtml($temp); }
        if($this->is_nested)     { $temp = &$this->_tplStripTags($temp); }
        if($this->strip_double)  { $temp = str_replace(array('{{', '}}'), array('{', '}'), $temp); }

        if(!$to_return) { echo $temp; }
        else            { return $temp; }
    } 


    /**
     * FUNCTION: tplPrintIn -- Prints content in another remplate
     *
     * @param string $template full path to html template
     * @param array $user_func_array array to be used with call_user_func_array.
     * @access public
     */
    function tplPrintIn($template, $user_func_array = array()) {
        
        $tpl = new tplTemplatez($template);
        $tpl->tplAssign('content', $this->tplPrint(1));
        
        foreach($user_func_array as $v) {
            call_user_func_array(array($tpl, $v[0]), $v[1]);
        }
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }

    
   /**
    * FUNCTION: tplStripVars -- Strips unassigned vars {var}.
    * 
    * Sometimes can be useful not show unasigned vars after parsing template.
    * Call it after tplParce() and before tplPrint()
    *
    * @param string $block from what block to strip unassigned vars, by default from all parsed blocks
    * @access public
    */
    function tplStripVars($block = false) {
        
        $search = "#(?<!\{)".$this->s_var_tag."\w+".$this->e_var_tag."(?!\})#";
        $replace = "";
        
        if($block) {
            if(isset($this->parsed[$block]))
                $this->parsed[$block] = preg_replace($search, $replace, $this->parsed[$block]);        
            else
                trigger_error($this->error_pref. " You haven't parsed block <b>".$block."</b> yet. You have to parse (tplParse()) it first before call function tplStripVars().");
        }
        else {
            foreach(array_keys($this->parsed) as $k) {
                $this->parsed[$k] = preg_replace($search, $replace, $this->parsed[$k]);                
            }
        }
    } 
  
  
   /**
    * FUNCTION: tplShowTemplate -- Prints the template code, for debugging purposes.
    *  
    * @param string $block with block to print, by default the whole template
    * @access public
    */    
    function tplShowTemplate($block = null) {
        
        if(!isset($block))
            $block = $this->tpl_blocks['content'];
        else {
            $s_delim = $this->s_block_tag . $block . $this->ee_block_tag;
            $e_delim = $this->e_block_tag . $block . $this->ee_block_tag;
            
            $block = $this->_tplSubstr($this->tpl_blocks['content'], $s_delim, $e_delim);
        }
        echo '<pre>'.str_replace("\t","&nbsp;&nbsp;&nbsp;&nbsp;",htmlspecialchars($block)).'</pre>';
    }
        
    
    /*-----------------------------------------------------------------------------\
    |                           private functions                                  |
    \-----------------------------------------------------------------------------*/
    
   /**#@+5          
    * @access private
    */
    function _tplSetBlock($block) {
        
        if(isset($this->is_setted[$block])) { return; }
        $this->is_setted[$block] = 1;
        
        //$array = 'content/'.$block;
        
        $array = explode('/',$block);
        //if(isset($this->tpl_blocks[$array[0]])) { return; }
        
        array_unshift($array, 'content');
        $num = count($array);
    
        for($i=1;$i<$num;$i++){
            if(!isset($this->tpl_blocks[$array[$i]]))
            $this->_tplPrepareBlock($array[$i],$array[$i-1]);
        }
    }
    

    function _tplPrepareBlock($block, $tpl_block = 'content') {
        
        $s_delim = $this->s_block_tag . $block . $this->ee_block_tag;
        $e_delim = $this->e_block_tag . $block . $this->ee_block_tag;
        
        if(strpos($this->tpl_blocks[$tpl_block], $s_delim) === false || strpos($this->tpl_blocks[$tpl_block], $e_delim) === false) {
            trigger_error($this->error_pref . " Can't find block <b>".$block."</b>, in template <b>".$this->tpl_file."</b>");
            return false;
        } 
        
        $this->tpl_blocks[$block] = $this->_tplSubstr($this->tpl_blocks[$tpl_block], $s_delim, $e_delim, false); // new tpl block with name $block
        $this->tpl_blocks[$tpl_block] = $this->_tplSubstrReplace($this->tpl_blocks[$tpl_block], '', $s_delim, $e_delim, false); // strip new block from parent
        
        $this->_tplSetAltBlocks($block);             // add alt blocks
        if(isset($this->alt_blocks[$block])) {         // delete alt blocks from parent
            foreach($this->alt_blocks[$block] as $k => $v) {
                unset($this->alt_blocks[$tpl_block][array_search($v, $this->alt_blocks[$tpl_block])]);
            }
        }
        
        // unset block from alt_blocks[tpl_block], now it won't be striiped
        unset($this->alt_blocks[$tpl_block][array_search($block, $this->alt_blocks[$tpl_block])]);
    }
    
    
    // alternative block
    function _tplSetAltBlocks($block) {
        if (strpos($this->tpl_blocks[$block],$this->s_block_tag) !== false) {
            preg_match_all("#".preg_quote($this->s_block_tag)."(\w+)".preg_quote($this->ee_block_tag)."#", $this->tpl_blocks[$block], $match);
            $this->alt_blocks[$block] = $match[1];
            //$this->block_setted[$block] = 1;
        } 
    }
    
    
    // 29.05.2003//<INCLUDE_TPL></INCLUDE>
    function _tplInclude() {
        if (strpos($this->tpl_blocks['content'],$this->s_include_tpl_tag) !== false) {
            preg_match_all("#".preg_quote($this->s_include_tpl_tag)."(\s{0,2}[\"|\']{0,1}(.*?)[\"|\']{0,1}\s{0,2})".preg_quote($this->e_include_tag)."#", $this->tpl_blocks['content'], $match);
            //echo "<pre>"; print_r($match); echo "</pre>";
            for($i=0,$num = count($match[1]); $i<$num; $i++){
            
                $s_delim = $this->s_include_tpl_tag;
                $e_delim = $match[1][$i] . $this->e_include_tag;
    
                $content =  implode('',file($match[2][$i]));
                $this->tpl_blocks['content'] = $this->_tplSubstrReplace($this->tpl_blocks['content'], $content, $s_delim, $e_delim);
            }
        }
    }
    
    
    
    // 29.05.2003//<HELP="help text">
    // to make robust for creating pop up div with help in it
    function _tplHelp() {
        if (strpos($this->tpl_blocks['content'],$this->s_help_tpl_tag) !== false) {
            preg_match_all("#".preg_quote($this->s_include_tpl_tag)."(\s{0,2}[\"|\']{0,1}(.*?)[\"|\']{0,1}\s{0,2})".preg_quote($this->e_help_tag)."#", $this->tpl_blocks['content'], $match);
            //echo "<pre>"; print_r($match); echo "</pre>";
            for($i=0,$num = count($match[1]); $i<$num; $i++){
            
                $s_delim = $this->s_include_tpl_tag;
                $e_delim = $match[1][$i] . $this->e_include_tag;
    
                $content =  implode('',file($match[2][$i]));
                $this->tpl_blocks['content'] = $this->_tplSubstrReplace($this->tpl_blocks['content'], $content, $s_delim, $e_delim);
            }
        }
    }
    
    
    // strip not needed blocks
    // $array - array with name of blocks to strip, $string - string with this blocks
    // $temp = preg_replace("#<tmpl:(.+?)>(.+?)</tmpl:\1>#is","",$temp); // with preg desicion
    function _tplStripBlocks($array, $string) {
        foreach($array as $k => $v) {
            $s_delim = $this->s_block_tag . $v . $this->ee_block_tag;
            $e_delim = $this->e_block_tag . $v . $this->ee_block_tag;
            $string = $this->_tplSubstrReplace($string, '', $s_delim, $e_delim);
        }
        return $string;
    }
    
    
    function &_tplStripTags($string) {
        $search[1] = "#".preg_quote($this->s_block_tag)."(.+?)".preg_quote($this->ee_block_tag)."#";
        $search[2] = "#".preg_quote($this->e_block_tag)."(.+?)".preg_quote($this->ee_block_tag)."#";
        $string = preg_replace($search, '', $string);
        return $string;
    }

    
    // beautify html        
    function &_tplCleanHTML($string) {
        $string = preg_replace('/[\r\n]+/s',"\n", preg_replace('/[\r\n][ \t]+/s',"\n",$string)); 
        return $string;
    }
    
    
    function _tplSubstrReplace($string, $replace, $s_delim, $e_delim, $with_delims = true) { 
        
        // if not to check, can lead to unexpected result
        if (strpos($string, $s_delim) !== false && strpos($string, $e_delim) !== false) {
            $s_point = strpos($string, $s_delim); // number
            $e_point = strpos($string, $e_delim); // number
            
            if ($with_delims) {
                $string = substr_replace($string, $replace, $s_point, ($e_point - $s_point + strlen($e_delim))); // delims will be replaced as well
            }
            else {
                $string = substr_replace($string, $replace, $s_point + strlen($s_delim), $e_point - $s_point - strlen($s_delim)); // delims will stay in string         
            }
        } 
        return $string;
    } 

    
    function _tplSubstr($string, $s_delim, $e_delim, $with_delims = true) {
    
        // if not to check, can lead to unexpected result
        if (strpos($string, $s_delim) !== false && strpos($string, $e_delim) !== false) {
            $s_point = strpos($string, $s_delim); // number
            $e_point = strpos($string, $e_delim); // number
            
            if (@$with_delims) {
                $string = substr($string, $s_point, ($e_point - $s_point + strlen($e_delim))); // with delims
            }
            else {
                $string = substr($string, $s_point + strlen($s_delim), $e_point - $s_point - strlen($s_delim)); // without delims
            }
        }
        return $string;
    } 
} 



class tplTemplatezString extends tplTemplatez
{
        
    
    function __construct($string) {
        
        $this->tpl_blocks['content'] = $string;

        $this->tpl_file = '';
        $this->vars['PHP_SELF'] = $_SERVER["PHP_SELF"]; // can be useful 
        $this->vars['CUR_PAGE'] = $_SERVER["PHP_SELF"].'?'.$_SERVER['QUERY_STRING']; // can be useful 
                
        $this->_tplInclude();
        $this->_tplSetAltBlocks('content');
        
        return true; 
    }
}
?>