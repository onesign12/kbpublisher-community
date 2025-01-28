<?php
class ParseHtml 
{
    
    static function appendHtmlById( &$s, $sId, $sHtml, &$oDoc = null ) { 
        return self::setHtmlElementById( $oDoc, $s, $sId, $sHtml, true, false ); 
    }

    
    static function insertHtmlById( &$s, $sId, $sHtml, &$oDoc = null ) { 
        return self::setHtmlElementById( $oDoc, $s, $sId, $sHtml, false, true ); 
    }


    static function addHtmlBeforeById( &$s, $sId, $sHtml, &$oDoc = null ) { 
        return self::setHtmlElementById( $oDoc, $s, $sId, $sHtml, false, true, true ); 
    }

    
    static function addHtmlAfterById( &$s, $sId, $sHtml, &$oDoc = null ) { 
        return self::setHtmlElementById( $oDoc, $s, $sId, $sHtml, true, false, true ); 
    }

    
    static function setHtmlById( &$s, $sId, $sHtml, &$oDoc = null ) { 
        return self::setHtmlElementById( $oDoc, $s, $sId, $sHtml, true, true ); 
    }


    static function replaceHtmlElementById( &$s, $sId, $sHtml, &$oDoc = null ) { 
        return self::setHtmlElementById( $oDoc, $s, $sId, $sHtml, false, false ); 
    }


    static function removeHtmlElementById( &$s, $sId, &$oDoc = null ) { 
        return self::setHtmlElementById( $oDoc, $s, $sId, null, false, false ); 
    }
    
    
    static function setHtmlElementById(&$oDoc, &$s, $sId, $sHtml, $bAppend = false, $bInsert = false, $bAddToOuter = false) {
        
        if (self::isValidString($s) && self::isValidString($sId)) {
            
            $bCreate = true;
            if (is_object($oDoc)) {
                if (!($oDoc instanceof DOMDocument)) {
                    return false;
                }
                
                $bCreate = false;
            }
            
            if ($bCreate) {
                $oDoc = new DOMDocument();
            }
            
            libxml_use_internal_errors(true);
            // $oDoc->loadHTML($s);
            $oDoc->loadHTML('<?xml encoding="utf-8" ?>' . $s, LIBXML_HTML_NODEFDTD); // April 6, 2021 eleontev, to corrcet parse UTF8
            libxml_use_internal_errors(false);
            
            $oNode = $oDoc->getElementById($sId);
            
            if (is_object($oNode)) {
                $bReplaceOuter = (!$bAppend && !$bInsert);
                $sId = uniqid('SHEBI-');
                $aId = array("<!-- $sId -->", "<!--$sId-->");
                
                if ($bReplaceOuter) {
                    if (self::isValidString($sHtml)) {
                        $oNode->parentNode->replaceChild($oDoc->createComment($sId), $oNode);
                        $s = $oDoc->saveHtml();
                        $s = str_replace($aId, $sHtml, $oDoc->saveHtml());
                    } else {
                        $oNode->parentNode->removeChild($oNode);
                        $s = $oDoc->saveHtml();
                    }
                    
                    return true;
                }
                
                $bReplaceInner = ($bAppend && $bInsert);
                $sThis = null;
                
                if (!$bReplaceInner) {
                    $sThis = $oDoc->saveHTML($oNode);
                    $sThis = ($bInsert ? $sHtml : '') . ($bAddToOuter ? $sThis : (substr($sThis, strpos($sThis, '>') + 1, -(strlen($oNode->nodeName) + 3)))) . ($bAppend ? $sHtml : '');
                }
                
                if (!$bReplaceInner && $bAddToOuter) {
                    $oNode->parentNode->replaceChild($oDoc->createComment($sId), $oNode);
                    $sId = & $aId;
                } else {
                    $oNode->nodeValue = $sId;
                }
                
                $s = str_replace($sId, $bReplaceInner ? $sHtml : $sThis, $oDoc->saveHtml());
                return true;
            }
        }
        
        return false;
    }
    
    
    // A function of my library used in the function above:
    static function IsValidString(&$s, &$iLen = null, $minLen = null, $maxLen = null) {
        
        if (!is_string($s) || !isset($s[0])) {
            return false;
        }
        
        if ($iLen !== null) {
            $iLen = strlen($s);
        }
        
        // Deprecated: Array and string offset access syntax with curly braces is deprecated
        // return (($minLen === null ? true : ($minLen > 0 && isset($s{$minLen - 1}))) && $maxLen === null ? true : ($maxLen >= $minLen && !isset($s{$maxLen})));
        
        return (($minLen === null ? true : ($minLen > 0 && isset($s[$minLen - 1]))) && $maxLen === null ? true : ($maxLen >= $minLen && !isset($s[$maxLen])));
    }
    
}



/*

In the following examples, I assume that there is already content loaded into a variable called $sMyHtml and the variable $sMyNewContent contains some new html. The variable $sMyHtml contains an element called/with the id 'example_id'.

// Example 1: Append new content to the innerHTML of an element (bottom of element):
if( appendHtmlById( $sMyHtml, 'example_id', $sMyNewContent ))
 { echo $sMyHtml; }
 else { echo 'Element not found?'; }

// Example 2: Insert new content to the innerHTML of an element (top of element):
insertHtmlById( $sMyHtml, 'example_id', $sMyNewContent );    

// Example 3: Add new content ABOVE element:
addHtmlBeforeById( $sMyHtml, 'example_id', $sMyNewContent );    

// Example 3: Add new content BELOW/NEXT TO element:
addHtmlAfterById( $sMyHtml, 'example_id', $sMyNewContent );    

// Example 4: SET new innerHTML content of element:
setHtmlById( $sMyHtml, 'example_id', $sMyNewContent );    

// Example 5: Replace entire element with new content:
replaceHtmlElementById( $sMyHtml, 'example_id', $sMyNewContent );    

*/

?>