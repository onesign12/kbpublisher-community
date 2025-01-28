<?php

class KBApiResponce
{

    var $encoding = 'utf-8';
    var $format = 'json';


    function __construct($format = false, $encoding = false) {

        if($format) {
            $this->format = $format;
        }

        if($encoding) {
            $this->encoding = $encoding;
        }
    }


    static function factory($format) {
        $class = 'KBApiResponce_' . $format;
        return new $class;
    }


	// for search in all
	function parseEntryArray($data, $api) {
		foreach(array_keys($data) as $type) {
			
		}
			
			
		
	}


	function parseEntry($data, $api) {

        // echo '<pre>', print_r($data, 1), '</pre>';
        // exit;

	    foreach(array_keys($data) as $entry_id) {
		    foreach($api->html_fields as $html_key) {
    	        if(isset($data[$entry_id][$html_key])) {
    	            $data[$entry_id][$html_key] =
    	                $this->encodeHTML($data[$entry_id][$html_key], 'html');
    	        }
    	    }

            if($this->format == 'xml') {
                // $data[$entry_id]['@attributes'] = array('id' => $entry_id);
                $data[$entry_id]['@attributes'] = array('id' => $data[$entry_id]['id']);

            } elseif ($this->format == 'json') {

                // change custom field
                if(isset($data[$entry_id]['custom']['item'])) {
                    $data[$entry_id]['custom'] = $data[$entry_id]['custom']['item'];
                }
            }

        }

        // echo '<pre>', print_r($data, 1), '</pre>';

	    return $this->parse($data, $api->root_tag, $api->root_attributes);
	}


	function _process($api, $controller, $manager) {

	    $data = $api->execute($controller, $manager);
		// echo '<pre>', print_r($data,1), '<pre>';
		// echo '<pre>', print_r($api->root_tag,1), '<pre>';
		// echo '<pre>', print_r($api->root_attributes,1), '<pre>';

	    if(is_integer(key($data))) {
            $data = $this->parseEntry($data, $api);
	    } else {
            $data = $this->parse($data, $api->root_tag, $api->root_attributes);
        }

		return $data;
	}


	function process($api, $controller, $manager) {

	    if(!$api->cache_lifetime) {
	        return $this->_process($api, $controller, $manager);
	    }

        $lifetime = $api->cache_lifetime;

        $cache = new Cache_Lite();
        // $cache->setOption('caching', false);
        $cache->setOption('cacheDir', APP_CACHE_DIR);
        $cache->setOption('lifeTime', $lifetime);

        $cache_id = $this->getCacheId($controller);
        $cache_gr = 'api';

        $data = $cache->get($cache_id, $cache_gr);
        if($data !== false) {
            $this->sendHeader();
            // echo '<pre>', print_r('CACHE', 1), '</pre>';
        } else {
            $data = $this->_process($api, $controller, $manager);
            $cache->save($data);
        }

        return $data;
    }


    function getCacheId($controller) {

        parse_str($_SERVER['QUERY_STRING'], $params);
        unset($params['signature']);
        unset($params['timestamp']);

        $string_to_sign = $controller->request_method;
        $string_to_sign .= $controller->host;
        $string_to_sign .= KBApiController::getRequestVar('accessKey');
        // $string_to_sign .= WebUtil::getIP();
        $string_to_sign .= implode('.', $params);

        return md5($string_to_sign . 'af$ew!±#g');
    }

}



class KBApiResponce_json extends KBApiResponce
{

    var $format = 'json';


	function parse($rows, $root_tag, $root_attributes = array()) {

	    $this->sendHeader();
        // header("Content-type: text/json; charset={$this->encoding}");
        // header("Content-type: application/json; charset={$this->encoding}");

        // to remove numeric index in json arr
        $rows = array_values($rows);

        // add root tag
        $rows = array($root_tag => $rows);

        // add meta such as total records
        if($root_attributes) {
            $rows['meta'] = $root_attributes;
        }

        $rows = json_encode($rows);
        if($rows === false) {
            // KBApiError::error(14); // attention !!! it could be infinite loop
        }

        return $this->prettyPrint($rows);
        // $data = array_map(utf8_encode, $data);

        // Encode: json_encode(array_map('base64_encode', $array));
        // Decode: array_map('base64_decode', json_decode($array);
	}


    function sendHeader() {
        header("Content-type: text/json; charset={$this->encoding}");
        // header("Content-type: application/json; charset={$this->encoding}");
    }


    function encodeHTML($value, $type) {
        return array(
            'type' => $type,
            'value' => base64_encode($value)
            );
    }


    function prettyPrint($json) {

        $result = '';
        $level = 0;
        $prev_char = '';
        $in_quotes = false;
        $ends_line_level = NULL;
        $json_length = strlen( $json );

        for( $i = 0; $i < $json_length; $i++ ) {
            $char = $json[$i];
            $new_line_level = NULL;
            $post = "";
            if( $ends_line_level !== NULL ) {
                $new_line_level = $ends_line_level;
                $ends_line_level = NULL;
            }
            if( $char === '"' && $prev_char != '\\' ) {
                $in_quotes = !$in_quotes;
            } else if( ! $in_quotes ) {
                switch( $char ) {
                    case '}': case ']':
                        $level--;
                        $ends_line_level = NULL;
                        $new_line_level = $level;
                        break;

                    case '{': case '[':
                        $level++;
                    case ',':
                        $ends_line_level = $level;
                        break;

                    case ':':
                        $post = " ";
                        break;

                    case " ": case "\t": case "\n": case "\r":
                        $char = "";
                        $ends_line_level = $new_line_level;
                        $new_line_level = NULL;
                        break;
                }
            }
            if( $new_line_level !== NULL ) {
                $result .= "\n".str_repeat( "\t", $new_line_level );
            }
            $result .= $char.$post;
            $prev_char = $char;
        }

        return $result;
    }

}

class KBApiResponce_xml extends KBApiResponce
{

    var $format = 'xml';


	function parse($rows, $root_tag, $root_attributes = array()) {

	    // pack every row to entry element <entry>...</entry>
        if(is_integer(key($rows))) {
	        $rows = array('entry' => $rows);
        }

        $rows['@attributes'] = $root_attributes;
		
	    $this->sendHeader();
	    $xml = Array2XML::createXML($root_tag, $rows);
        return $xml->saveXML();
	}


    function sendHeader() {
        header("Content-type: text/xml; charset={$this->encoding}");
    }


    function encodeHTML($value, $type) {
        return array('@cdata' => $value);
    }


    function setAttributes($value) {

    }
}

?>