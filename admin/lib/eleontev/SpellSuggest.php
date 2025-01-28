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

class SpellSuggest
{
    
    static $min_length = 3;
    
    
    static $sources = array(
        'enchant',
        'pspell',
        'bing'
    );
    

    static function factory($tool) {
        $class = 'SpellSuggest_' . $tool;   
        return new $class;
    }
    
    
    static function getSources() {
        $sources = self::$sources;
        if(BaseModel::isCloud()) {
            $sources = array('enchant');
        }
        
        return $sources;
    }
    
    
    static function checkForQuotes(&$str) {
        $str = trim($str);
        $string_quoted = ($str[0] == '"' && substr($str, -1) == '"');
        
        if ($string_quoted) {
            $str = trim($str, '"');
        }
        
        return $string_quoted;
    }
    
    
    static function restoreQuotes($data) {
        $_data = array();
        foreach ($data as $k => $v) {
            $key = sprintf('"%s"', $k);
            $_data[$key] = $v;
        }
        
        return $_data;
    }
    
    
    static function isSpellCheckNeeded($word) {
        $last_char = substr($word, -1);
        if ($last_char == '*') {
            return false;
        }
        
        return true;
    }
    
    
    static function getSuggestions($source, $str, $dictionary, $custom_words) {
        $check_method = ($source == 'enchant') ? 'enchant_dict_check' : 'pspell_check';
        $suggest_method = ($source == 'enchant') ? 'enchant_dict_suggest' : 'pspell_suggest';
        
        $suggestions = array();
        foreach ($str as $key => $value) {
            $check = self::isSpellCheckNeeded($value);
            
            if ($check) {
                $operator = false;
                $operators = array('+', '-', '~');
                if (in_array($value[0], $operators)) {
                    $operator = $value[0];
                    $value = substr($value, 1);
                }
                
                $in_custom_list = in_array(_strtolower($value), $custom_words);
                
                if(!$check_method($dictionary, $value)) {
                    $list = $suggest_method($dictionary, $value);
                    
                    $_suggestions = array();
                    if ($in_custom_list) {
                        $_suggestions[] = $value;
                    }                    
                    
                    
                    if (!empty($list)) {
                        if ($operator) {
                            foreach (array_keys($list) as $k) {
                                $list[$k] = $operator . $list[$k]; 
                            }
                        }
                    
                        $_suggestions = array_merge($_suggestions, $list);
                    }
                    
                    if (!empty($_suggestions)) {
                        $suggestions[$key] = array_map('_strtolower', $_suggestions);
                    }
                }
            }
        }
        
        return $suggestions;
    }


    static function sortSuggestions(&$suggestions) {
        
        $break_symbols = array(' ', '-');
        foreach (array_keys($suggestions) as $key) {

            foreach (array_keys($suggestions[$key]) as $key2) {
                $suggestion = $suggestions[$key][$key2];

                $push_to_end = false;
                foreach ($break_symbols as $symbol) {
                    $words = explode($symbol, $suggestion);
                    if (count($words) > 1) {
                        foreach ($words as $word) {
                            if (_strlen($word) <= 3) {
                                $push_to_end = true;
                                break 2;
                            }
                        }
                    }
                }

                if ($push_to_end) {
                    unset($suggestions[$key][$key2]);
                    array_push($suggestions[$key], $suggestion);
                }
            }

            $suggestions[$key] = array_values($suggestions[$key]);
        }
    }
    
    
    static function excludeSuggestions(&$suggestions) {
        foreach (array_keys($suggestions) as $key) {
            
            foreach (array_keys($suggestions[$key]) as $key2) {
                $suggestion = $suggestions[$key][$key2];
                $words = explode(' ', $suggestion);
                
                $exclude = true;
                foreach ($words as $word) {
                    if (_strlen($word) > 1) {
                        $exclude = false;
                        break;
                    }
                }
                
                if (!preg_match('#[^-\s\?]#', $suggestion)) {
                    $exclude = true;
                }
                
                if ($exclude) {
                    unset($suggestions[$key][$key2]);
                }
            }
            
            $suggestions[$key] = array_values($suggestions[$key]);
        }
    }

}


class SpellSuggest_pspell extends SpellSuggest
{
    
    static function suggest($dictionary_name, $custom_words, $str, $live = false) {
        
        $string_quoted = self::checkForQuotes($str);
        
        // splitting into words
        $str = explode(' ', trim(str_replace(',', ' ', $str)));
        $generator = SpellSuggestWorldRule::factory(count($str));

        $config_dic = pspell_config_create($dictionary_name);
        pspell_config_ignore($config_dic, self::$min_length);

        pspell_config_mode($config_dic, PSPELL_FAST);
        $dictionary = pspell_new_config($config_dic);
        
        $suggestions = self::getSuggestions('pspell', $str, $dictionary, $custom_words);

        $data = array();
        if (!empty($suggestions)) {
            self::excludeSuggestions($suggestions);
            self::sortSuggestions($suggestions);
            $data = $generator->getData($suggestions, $str);
            
            if ($string_quoted) {
                $data = self::restoreQuotes($data);
            }
        }

        return $data;
    }
}


class SpellSuggest_enchant extends SpellSuggest
{
    
    static function suggest($provider, $dictionary, $custom_words, $str) {
        $string_quoted = self::checkForQuotes($str);
        
        // splitting into words
        $str = explode(' ', trim(str_replace(',', ' ', $str)));
        $generator = SpellSuggestWorldRule::factory(count($str));

        $r = enchant_broker_init();
        enchant_broker_set_ordering($r, $dictionary, $provider);
        $d = enchant_broker_request_dict($r, $dictionary);
        
        $suggestions = self::getSuggestions('enchant', $str, $d, $custom_words);
        
        unset($d, $r);
        //enchant_broker_free_dict($d); // deprecated
        //enchant_broker_free($r);

        $data = array();
        if (!empty($suggestions)) {
            //if ($provider == 'aspell') {
                self::excludeSuggestions($suggestions);
                self::sortSuggestions($suggestions);
            //}

            $data = $generator->getData($suggestions, $str);
            
            if ($string_quoted) {
                $data = self::restoreQuotes($data);
            }
        }

        return $data;
    }
    
}


class SpellSuggest_bing extends SpellSuggest
{
    
    static function suggest($key, $url, $str, $custom_words) {
        
        $str = trim($str);
        if (strpos($str, ' ') === false) { // one word
            if (in_array(strtolower($str), $custom_words)) {
                return false;
            }
            
            if (substr($str, -1) == '*') {
                return false;
            }
        }
        
        
        $url = str_replace('[search_query]', urlencode($str), $url);
        list($body, $code) = self::request($url, $key);

        $body = json_decode($body);

        if ($code != 200) {
            return false;

        } elseif (!empty($body->flaggedTokens)) {
            return array(self::getCorrectedText($str, $body->flaggedTokens) => 1);
            
        } elseif (!empty($body->suggestionGroups)) {
            $data = array();
            foreach ($body->suggestionGroups[0]->searchSuggestions as $suggestion) {
                $data[$suggestion->displayText] = 1;
            }
            
            return $data;
        }
    }
    
    
    static function getCorrectedText($text, $tokens) {
        foreach ($tokens as $token) {
            $suggestion = $token->suggestions[0]->suggestion;
            $text = substr_replace($text, $suggestion, $token->offset, strlen($token->token));
        }
        
        return $text;
    }


    static function request($url, $key) {
        
        $ch = curl_init();

        $headers = array(
            'Ocp-Apim-Subscription-Key: ' . $key
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,5);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headers = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        $errno = curl_errno($ch);
        $error = curl_error($ch);

        curl_close($ch);

        return array($body, $code);
    }
    
}


// Word Rules // --------------------------

class SpellSuggestWorldRule
{
    
    static function factory($words_num) {
        
        $class = 'null';

        if ($words_num == 1) {
            $class = 'one';

        } elseif(in_array($words_num, array(2,3))) {
            $class = 'two';
        }

        $class = 'SpellSuggestWorldRule_' . $class;
        return new $class;
    }
}


class SpellSuggestWorldRule_null
{
    function getData() {
        return array();
    }
}


class SpellSuggestWorldRule_one
{

    function getData($suggestions, $str) {

        $data = array();

        for ($i = 0; $i < 10; $i ++) {
            foreach (array_keys($suggestions) as $key) {
                if (count($data) >= 10) {
                    break 2;
                }

                if (!empty($suggestions[$key][$i])) {
                    $_str = $str;
                    $_str[$key] = $suggestions[$key][$i];
                    $str1 = implode(' ', $_str);
                    $data[$str1] = 1;
                    $replacement_suggest = true;
                }
            }
        }

        return $data;
    }
}


class SpellSuggestWorldRule_two
{

    function getData($suggestions, $str) {

        $data = array();

        if (count($suggestions) > 2) {
            return $data;
        }

        if (count($suggestions) == 2) {
            list ($first_suggestion, $second_suggestion) = array_keys($suggestions);

            $pairs = array(
                array(0, 0), array(1, 0), array(0, 1),
            );

            foreach ($pairs as $pair) {
                $_str = $str;
                
                if (empty($suggestions[$first_suggestion][$pair[0]]) ||
                    empty($suggestions[$second_suggestion][$pair[1]])) {
                    break;
                }

                $_str[$first_suggestion] = $suggestions[$first_suggestion][$pair[0]];
                $_str[$second_suggestion] = $suggestions[$second_suggestion][$pair[1]];
                $str1 = implode(' ', $_str);
                $data[$str1] = 1;
            }

            return $data;
        }

        if (count($suggestions) == 1) { // just 5
            $index = key($suggestions);

            for ($i = 0; $i < 5; $i ++) {
                if (!empty($suggestions[$index][$i])) {
                    $_str = $str;
                    $_str[$index] = $suggestions[$index][$i];
                    $str1 = implode(' ', $_str);
                    $data[$str1] = 1;
                }
            }

            return $data;
        }

        return $data;
    }
}

?>