<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KBPublisher - web based knowledgebase publisher tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2023 Evgeny Leontev                                         |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+

use \ForceUTF8\Encoding;


class FileTextExctractor
{    

    static $classes = array(
        'pdf'  => 'pdf', 
        
        'txt'  => 'txt',
        'csv'  => 'txt',
        'ini'  => 'txt',
        
        'html' => 'html', 
        'htm'  => 'html',
        'xml'  => 'html',
        
        'doc'  => 'doc',
        'rtf'  => 'doc',

        'doc2' => 'doc2', // antiword
        'rtf2' => 'doc2', // antiword

        'xls'  => 'xls',
        'ppt'  => 'ppt',
        'docx' => 'docx',
        'xlsx' => 'xlsx',
        'pptx'  => 'pptx',
        'odt'  => 'odt',
        'ods'  => 'odt'
    );
        
    
    var $tools = array();
    
    
    function __construct($extension, $tools = false) {
        
        // to load diff class, for example for doc
        // it can use catdoc or antiword
        if($tools && isset($tools[$extension]['load_extension'])) {
            $extension = $tools[$extension]['load_extension'];
        }
        
        $this->ex = $this->factory($extension);
    }
    
    function getText($filename, $test = false) {
        if($this->ex->validateTool()) {
            return $this->ex->getText($filename, $test);
        }
    }
    
    function setExtractDir($dir) {
        $this->ex->extract_save_dir = $dir;
    }
    
    function setTool($tools) {
        if(is_array($tools)) {
            if(isset($tools[$this->ex->tool_name])) { 
                if(is_array($tools[$this->ex->tool_name])) {
                    $this->ex->tool = $tools[$this->ex->tool_name][0];
                    $this->ex->tool_params = $tools[$this->ex->tool_name][1];
                } else {
                    $this->ex->tool = $tools[$this->ex->tool_name];
                }
            }
        } else {
            $this->ex->tool = $tools;
        }
    }
    
    function setDecode($decode_from, $decode_to, $decode = true) {
        $this->ex->decode_from = $decode_from; 
        $this->ex->decode_to = $decode_to;
        $this->ex->decode = $decode; 
    }    
    
    function getExtension($extension) {
        $ret = false;
        $extension = strtolower($extension);
        if(isset(self::$classes[$extension])) {
            $ret = self::$classes[$extension];
        }
        
        return $ret;
    }
        
    function factory($extension) {
        
        $ext = $this->getExtension($extension);
        if($ext) {
            $class = 'FileTextExctractor_' . $ext;
            return new $class;
        
        } else {
            return new FileTextExctractor_common();
        }
    }
}


class FileTextExctractor_common
{
    
    var $tool;
    var $tool_params;
    var $tool_name;
    var $extract_save_dir;
    var $extract_save_file = 'content.txt';
    
    var $decode = true; //false;
    var $decode_from;
    var $decode_to;
    
    
    function strip($str) {
        
        // remove the same lines ???
        // $search[]  = "/^(.*)(\r?\n\1)+$/";
        // $replace[] = '\1';
        
        $search[]  = '/[\n\r]/';
        $replace[] = ' ';
        
        $search[]  = '/[:;",\.\'\-\t]/';
        $replace[] = ' ';
        
        $search[]  = '/( \w{1,2} )/';
        $replace[] = ' ';
        
        $search[]  = '/\s{2,}/';
        $replace[] = ' ';
        
        // ??? to remove not non ASCII characters
        // we may need remove this for some langs, chinese  for example ?
        // $search[]  = '/[^(\x20-\x7F)]*/';
        // $replace[] = '';        
                
        return preg_replace($search, $replace, $str);
    }
    
    
    function prepareXml($str) {
        return str_replace('/><', '/> <', $str);
    }
    
    
    function validateTool() {
        return !($this->tool == 'off');
    }
    
    
    function getText($filename, $test = false) { 
        return false; 
    }
    
    
    // we use Encoding::toUTF8, it uses utf_encode which is deprecated
    // we may need to replace it
	function decodeData($string, $from, $to) {
        return Encoding::toUTF8($string);
	} 
    
    
    function stripParams($params) {
        $search[] = '#[\|\<\>]#';
        $replace[] = ' ';        
        $search[] = '#\s{2,}#';
        $replace[] = ' ';
        return trim(preg_replace($search, $replace, $params));
    }
}


class FileTextExctractor_pdf extends FileTextExctractor_common
{

    var $tool_name = 'pdf';

    // EXIT CODES - The Xpdf tools use the following exit codes
    var $codes = array(
        0 => 'No error',
        1 => 'Error opening a PDF file',
        2 => 'Error opening an output file (temp file to write content)',
        3 => 'Error related to PDF permissions',
        99 => 'Other error',
        127 => 'Filesystem error',
        -1 => 'Function system() is not available',
        -2 => 'Test pdf file is not readable or does not exist'
    );


    function &getText($filename, $test = false) {
                
        $content = false;
        //$filename = escapeshellarg($filename);
        //$this->tool = escapeshellarg($this->tool);
        
        if(!file_exists($filename) || !is_readable($filename)) {
            if($test) { $content = -2; }
            return $content;            
        }

        $params = ' -raw -enc UTF-8';
        if(!empty($this->tool_params)) {
            $params .= ' ' . $this->stripParams($this->tool_params) . ' ';
        }
                
        $cmd = (substr(PHP_OS, 0, 3) == "WIN") ? 'pdftotext.exe' : 'pdftotext';
        $cmd = trim($this->tool) . $cmd . $params;
        $save_file = $this->extract_save_dir . $this->extract_save_file;
    
        //$command = "pdftotext -raw file_read.pdf file_write.txt;
        $command = sprintf('%s "%s" "%s"', $cmd, $filename, $save_file);
        system($command, $return);
        // exec($command, $content_exec, $return);
    
        // echo "<pre>"; print_r('Command:' . $command); echo "</pre>";
        // echo "<pre>"; print_r('Return:' . $return); echo "</pre>";
        // exit;
    
        if($test) {
            $content = $return;

        } else {

            if($return == 0) {

                @$fp = fopen($save_file, 'rb');
                if($fp) {
                    $content = $this->strip(strip_tags(fread($fp, filesize($save_file))));
                    fclose($fp);

                    if($this->decode) {
                        $content = $this->decodeData($content, $this->decode_from, $this->decode_to);
                    }
                }
            }
            
        }
        
        if(file_exists($save_file)) {
            unlink($save_file);
        }

        return $content;
    }    
}


class FileTextExctractor_doc  extends FileTextExctractor_common
{
    
    var $tool_name = 'doc';
    
    var $codes = array(
        0   => 'No error',
        1   => 'Error opening a file',
        127 => 'Filesystem error',
        -1  => 'Function exec() is not available',
        -2  => 'Test word file is not readable or does not exist'
    );
    

    function &getText($filename, $test = false) {

        $content = false;
        //$filename = escapeshellarg($filename);
        //$this->tool = escapeshellarg($this->tool);
                
        if(!file_exists($filename) || !is_readable($filename)) {
            if($test) { $content = -2; }
            return $content;            
        }

        
        $cmd = $this->getCmd();
        $command = sprintf('%s "%s"', $cmd, $filename);
                         
        exec($command, $content, $return);
        $content = implode(' ', $content);

        // echo '<pre>Command: ', print_r($command, 1), '</pre>';
        // echo '<pre>Content: ', print_r($content, 1), '</pre>';
        // echo '<pre>Return: ', print_r($return, 1), '</pre>';
        // exit;

        if($test) {
            return $return;
        }            
        
        if($return == 0) {
            $content = $this->strip(strip_tags($content));
            if($this->decode) {
                $content = $this->decodeData($content, $this->decode_from, $this->decode_to);
            }                
        }

        return $content;
    }

    
    function getCmd() {

        $params = ' -w';
        if(!empty($this->tool_params)) {
            $params .= ' ' . $this->stripParams($this->tool_params) . ' ';
        }
        
        // $cmd = 'catdoc';
        $cmd = (substr(PHP_OS, 0, 3) == "WIN") ? 'catdoc.exe' : 'catdoc';
        $cmd = trim($this->tool) . $cmd . $params;

        return $cmd;
    }
}


class FileTextExctractor_xls  extends FileTextExctractor_doc
{

    function getCmd() {
        
        // $cmd = 'xls2csv';
        $cmd = (substr(PHP_OS, 0, 3) == "WIN") ? 'xls2csv.exe' : 'xls2csv';
        $cmd = trim($this->tool) . $cmd;

        return $cmd;
    }
    
}


class FileTextExctractor_ppt  extends FileTextExctractor_doc
{

    function getCmd() {
        
        // $cmd = 'catppt';
        $cmd = (substr(PHP_OS, 0, 3) == "WIN") ? 'catppt.exe' : 'catppt';
        $cmd = trim($this->tool) . $cmd;

        return $cmd;
    }
    
}


class FileTextExctractor_doc2  extends FileTextExctractor_common
{
    
    var $tool_name = 'doc';
    
    var $codes = array(
        0   => 'No error',
        1   => 'Error opening a file',
        127 => 'Filesystem error',
        -1  => 'Function exec() is not available',
        -2  => 'Test word file is not readable or does not exist'
    );
    

    function &getText($filename, $test = false) {

        $content = false;
        //$filename = escapeshellarg($filename);
        //$this->tool = escapeshellarg($this->tool);
                
        if(!file_exists($filename) || !is_readable($filename)) {
            if($test) { $content = -2; }
            return $content;            
        }

        
        $params = ' -t';
        
        // -m mapping file
        // This file is used to map Unicode characters to your local character set.  
        // The default mapping  file  depends on the locale.
        // may help for Chinese 
        // $params = ' -m UTF-8.txt ';
        
        if(!empty($this->tool_params)) {
            $params .= ' ' . $this->stripParams($this->tool_params) . ' ';
        }
        
        $cmd = (substr(PHP_OS, 0, 3) == "WIN") ? 'antiword.exe' : 'antiword';
        $cmd = trim($this->tool) . $cmd . $params;
        
        $command = sprintf('%s "%s"', $cmd, $filename);
                         
        exec($command, $content, $return);
        $content = implode(' ', $content);

        // echo '<pre>Command: ', print_r($command, 1), '</pre>';
        // echo '<pre>Content: ', print_r($content, 1), '</pre>';
        // echo '<pre>Return: ', print_r($return, 1), '</pre>';
        // exit;

        if($test) {
            return $return;
        }            
        
        if($return == 0) {
            $content = $this->strip(strip_tags($content));
            if($this->decode) {
                $content = $this->decodeData($content, $this->decode_from, $this->decode_to);
            }                
        }

        return $content;
    }
}


class FileTextExctractor_txt  extends FileTextExctractor_common
{
    
    var $tool_name = 'txt';
    
    function &getText($filename, $test = false) {
        
        $content = false;
    
        if(file_exists($filename)) {
            
            @$fp = fopen($filename, 'rb');
            if($fp) {
                $content = fread($fp, filesize($filename));
                $content = $this->stripData($content);
                fclose($fp);

                if($this->decode) {
                    $content = $this->decodeData($content, $this->decode_from, $this->decode_to);
                }                
            }
        }
        
        return $content;
    }
    
    
    function stripData($str) {
        return $this->strip($str);
    }    
}


class FileTextExctractor_html extends FileTextExctractor_txt
{
        
    function stripData($str) {
        return $this->strip(strip_tags($str));
    }    
}


class FileTextExctractor_xml extends FileTextExctractor_txt
{
        
    function stripData($str) {
        return $this->strip(strip_tags($this->prepareXml($str)));
    }    
}


class FileTextExctractor_docx  extends FileTextExctractor_common
{
    
    var $tool_name = 'docx';
    var $path = 'word/document.xml';
    
    var $codes = array(
        0   => 'No error',
        1   => 'Error opening a file',
        127 => 'Filesystem error',
        -1  => 'Extension zip is not available',
        -2  => 'File to parse does not exist or it is not readable'
    );
    
    
    function &getText($filename, $test = false) {
        
        $content = false;
    
        if(!file_exists($filename) || !is_readable($filename)) {
            if($test) { $content = -2; }
            return $content;            
        }

        if(extension_loaded('zip')) {
            
            $zip = new ZipArchive;
            
            if ($zip->open($filename) === true) {
            
                if (($index = $zip->locateName($this->path)) !== false) {

                    $data = $zip->getFromIndex($index);
                
                    $doc = new DOMDocument();
                    $doc->loadXML($data, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
                    $content = $this->strip(strip_tags($this->prepareXml($doc->saveXML())));
                
                    if($this->decode) {
                        $content = $this->decodeData($content, $this->decode_from, $this->decode_to);
                    }
                }
                
                $zip->close();
            }
        }
        
        return $content;
    }
}


class FileTextExctractor_xlsx  extends FileTextExctractor_docx
{
    
      var $path = 'xl/sharedStrings.xml';

}


class FileTextExctractor_pptx  extends FileTextExctractor_docx
{
    
      // var $path = 'xl/sharedStrings.xml';
      function &getText($filename, $test = false) {
          
          $content = false;
      
          if(!file_exists($filename) || !is_readable($filename)) {
              if($test) { $content = -2; }
              return $content;            
          }

          if(extension_loaded('zip')) {
    
            $zip = new ZipArchive();
            
            if (true === $zip->open($filename)) {
                
                $slide_number = 1; //loop through slide files
                $doc = new DOMDocument();
                
                $path = 'ppt/slides/slide%s.xml';
                while (($xml_index = $zip->locateName(sprintf($path, $slide_number))) !== false) {

                    $data = $zip->getFromIndex($xml_index);
                    $doc->loadXML($data, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
                    $content .= $this->strip(strip_tags($this->prepareXml($doc->saveXML())));

                    $slide_number++;
                }
                
                $zip->close();
            }
        }
        
        return $content;
    }
}


class FileTextExctractor_odt  extends FileTextExctractor_docx
{
      var $path = 'content.xml';
}
?>