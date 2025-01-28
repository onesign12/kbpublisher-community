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

class CaptchaImage {

    var $font = 'Vera.ttf';
    var $code;
    var $num_characters = 6;
    var $width = '200';
    var $height = '60';
    var $image;
    

    function __construct($options = array()) {
        $this->setCode();
    }
    
    function setCode() {
        $this->code = $this->generateCode($this->num_characters);
    }
    
    function getCode() {
        return $this->code;
    }
    
    function setFont($font) {
        $this->font = $font;
    }
    
    function isPossible($dir = false) {
        
        $image = @imagecreate(1, 1);
        if(!$image) {
            return false;
        }
        
        //if(!is_writeable(dirname($filename))) {
        if($dir && !is_writeable($dir)) {
            return false;
        }
        
        //imagejpeg($image, $filename);
        //imagedestroy($image);
    
        return true;
    }
    
    
    function getImage($imagename = false) {
        
        /* font size will be 75% of the image height */
        $font_size = $this->height * 0.5;
        $image = @imagecreate($this->width, $this->height) or die('Cannot Initialize new GD image stream');
        
        /* set the colours */
        $background_color = imagecolorallocate($image, 255, 255, 255);
        $text_color = imagecolorallocate($image, 20, 40, 100);
        $noise_color = imagecolorallocate($image, 100, 120, 180);
        
        /* generate random dots in background */
        for( $i=0; $i<($this->width*$this->height)/3; $i++ ) {
            imagefilledellipse($image, mt_rand(0,$this->width), mt_rand(0,$this->height), 1, 1, $noise_color);
        }
        
        /* generate random lines in background */
        for( $i=0; $i<($this->width*$this->height)/150; $i++ ) {
            imageline($image, mt_rand(0,$this->width), mt_rand(0,$this->height), mt_rand(0,$this->width), mt_rand(0,$this->height), $noise_color);
        }
        
        /* create textbox and add text */
        $textbox = imagettfbbox($font_size, 0, $this->font, $this->code);
        $x = ($this->width - $textbox[4])/2;
        $y = ($this->height - $textbox[5])/2;
        imagettftext($image, $font_size, 0, $x, $y, $text_color, $this->font , $this->code);
        
        /* output captcha image to browser */
        if($imagename) {
            imagejpeg($image, $filename);
        } else {
            imagejpeg($image);
        }
        
        imagedestroy($image);
    }

    
    function generateCode($characters) {
        $possible = '345689ABCDEFRTWQXYWZbcdfghjkmnpqrstvwxyz';
        $code = '';
        $i = 0;
        while ($i < $characters) { 
            $code .= substr($possible, mt_rand(0, strlen($possible)-1), 1);
            $i++;
        }
        
        return $code;
    }
    
    
    static function isRequredLib() {
        
        $ret = true;
        
        /*
        [GD Version] => bundled (2.0 compatible)
        [FreeType Support] => 1
        [FreeType Linkage] => with freetype
        [T1Lib Support] => 
        [GIF Read Support] => 
        [GIF Create Support] => 
        [JPG Support] => 1
        [PNG Support] => 1
        [WBMP Support] => 1
        [XBM Support] => 
        
        Freetype Linkage    string value describing the way in which Freetype was linked.
        Expected values are: 'with freetype', 'with TTF library', and 'with unknown library'.
        This element will only be defined if Freetype Support evaluated to TRUE.
        Workin is "with freetype", not sure about others values
        */
        if (!extension_loaded('gd')) {
            return false;
        }
        
        $gd = gd_info();
        if(empty($gd['GD Version']) || empty($gd['FreeType Support'])) {
            return false;
        }
        
        return $ret;
    }
}

//header('Content-Type: image/jpeg');
//$captcha = new CaptchaImage();
//$captcha->getImage();

//echo "<pre>"; print_r($captcha); echo "</pre>";
?>