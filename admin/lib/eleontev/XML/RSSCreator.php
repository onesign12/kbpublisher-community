<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2006-2021 Evgeny Leontev                                    |
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

require_once 'utf8/utils/validation.php';
require_once 'utf8/utils/bad.php';

//


class RSSCreator
{

    var $rss_version = '2.0';
    var $encoding = 'utf-8';
    
    var $templates = array();
    
    
    var $channels = array();
    var $channels_tags = array('title', 'description', 'link');    
                          
    var $optional_channels = array();
    var $optional_channels_tags = array('pubDate'     => '<pubDate>%s</pubDate>',
                                        'category'    => '<category>%s</category>');                          
                          
    
    var $items = array();
    var $items_tags = array('title', 'link');                       
    
    var $optional_items = array();
    var $optional_items_tags = array('description' => '<description>%s</description>',
                                     'category'    => '<category>%s</category>',
                                     'pubDate'     => '<pubDate>%s</pubDate>',
                                     'guid'        => '<guid>%s</guid>',
                                     'comments'    => '<comments>%s</comments>',
                                     'content'     => '<content:encoded><![CDATA[%s]]></content:encoded>');
    
    var $convert_date = true;
    
    
    function __construct() {
        $this->_setTemplate();
    }
    
    
    function setEncoding($val) {
        $this->encoding = $val;
    }
    
    
    function setChannel($key, $value = false) {
        $this->channels[$key] = $this->callBack($key, $value);
    }
    
    
    function setItem($title, $link, $optional = array()) {
        static $i = 0; 
        $i++;
        
        $this->items[$i] = array('title' => $this->callBack('title', $title), 
                                 'link'  => $this->callBack('link', $link)
                                 );
        
        foreach($optional as $k => $v) {
            if(isset($this->optional_items_tags[$k])) {
                $v = $this->callBack($k, $v);
                $this->optional_items[$i][$k] = sprintf($this->optional_items_tags[$k], $v);        
            }
        }
    }
    
    
    function setOptonalItemTag($key, $tag) {
        $this->optional_items_tags[$key] = $tag;
    }
        
    
    function callBack($key, $value) {
        if($key == 'pubDate' && $this->convert_date) {
            $value = date('r', $value);
        
        } elseif($key != 'content') {
            $value = RequestDataUtil::stripVarsXml($value);
        }
        
        if($this->encoding == 'utf-8' || $this->encoding == 'UTF-8') {
            if(!utf8_compliant($value)) {
                $value = utf8_bad_replace($value, '?');
            }
        }

        return $value;
    }
    
    
    function convertDate($timestamp) {
        $this->convert_date = false;
        return date('r', $timestamp);
    }
    
    
    function setItems($arr) {
        foreach($arr as $k => $v) {
            $this->setItem($v['title'], $v['link'], $v);
        }
    }
    
    
    function getTemplate() {
        return $this->templates[$this->rss_version];
    }
    
    
    function setTemplate($key, $template) {
        $this->templates[$key];
    } 
    
        
    function sendXMLHeader() {
        $encoding = $this->encoding;
        header("Content-type: text/xml; charset={$encoding}");
        //header("Content-type: application/rss+xml; charset={$encoding}");
    }
    
    
    function getXML($xml_header = false) {
        
        if($xml_header) {
            $this->sendXMLHeader();
        }
        
        $tpl = new tplTemplatezString($this->getTemplate());
        
        foreach($this->channels as $k => $v) {
            $tpl->tplAssign('channel_' . $k, $v);
        }        
        
        foreach($this->items as $k => $v) {
            $v['optional_items'] = implode("\n\t\t", $this->optional_items[$k]);
            $tpl->tplParse($v, 'row_item');
        }

        $tpl->tplAssign('optional_channels', implode("\n\t", $this->optional_channels));
        $tpl->tplAssign('encoding', $this->encoding);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function _setTemplate() {
        
        $this->templates['0.9'] = 
'<?xml version="1.0" encoding="{encoding}"?>
<rdf:RDF 
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns="http://my.netscape.com/rdf/simple/0.9/">
    
    <channel>
        <title>{channel_title}</title>
        <description>{channel_description}</description>
        <link>{channel_link}</link>
    </channel>
    
    <tmpl:row_item>
    <item>
        <title>{title}</title>
        <link>{link}</link>
    </item>
    </tmpl:row_item>

</rdf:RDF>';
        
        
        $this->templates['1.0'] = 
'<?xml version="1.0" encoding="{encoding}"?>
<rdf:RDF 
      xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" 
      xmlns:dc="http://purl.org/dc/elements/1.1/"
      xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
      xmlns:co="http://purl.org/rss/1.0/modules/company/"
      xmlns:ti="http://purl.org/rss/1.0/modules/textinput/"
      xmlns="http://purl.org/rss/1.0/">
    
    <channel rdf:about="{channel_link}">
        <title>{channel_title}</title>
        <description>{channel_description}</description>
        <link>{channel_link}</link>
    </channel>
    
    <items>
        <rdf:Seq>
            <tmpl:row_items>
            <rdf:li rdf:resource="{link}"/>
            <tmpl:row_items>
        </rdf:Seq>
    </items>
    
    <tmpl:row_item>
    <item rdf:about="{link}">
        <title>{title}</title>
        <link>{link}</link> 
    </item>
    </tmpl:row_item>

</rdf:RDF>';
        
        
        $this->templates['2.0'] = 
'<?xml version="1.0" encoding="{encoding}"?>
<rss xmlns:content="http://purl.org/rss/1.0/modules/content/" version="2.0">
<channel>
    <title>{channel_title}</title>
    <description>{channel_description}</description>
    <link>{channel_link}</link>
    {optional_channels}
    
    <tmpl:row_item>
    <item>
        <title>{title}</title>
        <link>{link}</link>
        {optional_items}
    </item>
    </tmpl:row_item>
    
</channel>
</rss>';
    
    }    
}
?>