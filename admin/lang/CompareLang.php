<?php
class CompareLang
{

	var $original_lang = 'en';
	var $compared_lang;

	var $original_lang_files = array();
	var $compared_lang_files = array();

	var $lang_name;

	var $multi_ini_files = array(
		'common_hint.ini',
		'knowledgebase/hint_msg.ini',
		'file/hint_msg.ini',
		'email_setting/hint_msg.ini',
		'setup/phrase_msg.ini',
		'license_msg.ini',
		'after_action_msg2.ini',
		'public/text_msg.ini',
		'export_setting/plugin_msg.ini',
		'tooltip_msg.ini',
		'placeholder_msg.ini',
		'saml_setting/hint_msg.ini',
        'export/tooltip_msg.ini'
	);

	var $diff = array(
		'missed_files' => array(),
		'missed_fields' => array(),
        'empty_fields' => array(),
		'equal_fields' => array(),
		'not_equal_fields' => array()
	);

    var $comp_diff = array(
		'equal_fields' => array(),
		'not_equal_fields' => array()
	);

	var $word_count = array(
		'translate' => 0,
		'translated' => 0
	);

    var $files_list = array();


	function __construct($compared_lang, $original_lang = 'en') {
		if($original_lang) {
			$this->original_lang = $original_lang;
		}

		$this->setComparedLang($compared_lang);
		$this->lang_name = $this->getLangName();
	}


	function &getMissedFiles() {

        $b = array_diff($this->original_lang_files, $this->compared_lang_files);

		$this->deleteEmpty($b);
		$this->diff['missed_files'] = &$b;

		return $b;
	}


	// two dimensinal only
	function &getMissedFields($b = array()) {

		foreach($this->original_lang_files as $k => $v) {
			$b[$v] = $this->_getMissedFields($v);
		}

		$this->deleteEmpty($b);
		$this->diff['missed_fields'] = &$b;

		return $b;
	}


    function &getEmptyFields($b = array()) {

        foreach($this->original_lang_files as $k => $v) {

            if(is_array($v)) {
                foreach($v as $k1 => $v1) {
                    $b[$k][$v1] = $this->_getEmptyFields($k . '/' . $v1);
                }
            } else {
                $b[$v] = $this->_getEmptyFields($v);
            }
        }

        $this->deleteEmpty($b);
        $this->diff['empty_fields'] = &$b;

        return $b;
    }


	// two dimensinal only
	function &getEqualValues($equal = true) {

		$b = array();
		foreach($this->original_lang_files as $k => $v) {

			if(is_array($v)) {
				foreach($v as $k1 => $v1) {
                    $values = $this->_getEqualValues($k . '/' . $v1, $equal);
					$b[$k][$v1] = $values[0];
                    $c[$k][$v1] = $values[1];
				}

			} else {
				$values = $this->_getEqualValues($v, $equal);
				$b[$v] = (isset($values[0])) ? $values[0] : array();
                $c[$v] = (isset($values[1])) ? $values[1] : array();
			}
		}

        // echo '<pre>', print_r($values, 1), '</pre>';
        // echo '<pre>', print_r($c, 1), '</pre>';
        // echo '<pre>', print_r("================", 1), '</pre>';

		$this->deleteEmpty($b);
		$this->deleteEmpty($c);
		$key = ($equal) ? 'equal_fields' : 'not_equal_fields';
		$this->diff[$key] = &$b;
        $this->comp_diff[$key] = &$c;

		return $b;
	}


	// two dimensinal only
	function &getNotEqualValues() {
		return $this->getEqualValues(false);
	}


	function setComparedLang($lang) {
		$this->compared_lang = $lang;
	}


	function setFiles($skip_files, $skip_dirs) {

		$d = new MyDir;
		$d->setSkipDirs('.svn');
		$d->setSkipDirs($skip_dirs);
		// $d->setSkipFiles($this->original_lang . '/config_lang.php');
		$d->setSkipFiles($skip_files);
		$d->setAllowedExtension('ini', 'txt');
		$d->one_level = false;
		$d->full_path = false;

		$this->original_lang_files = $d->getFilesDirs($this->original_lang);
		$this->original_lang_files = $this->parseFilesToOneArray($this->original_lang_files);

		$this->compared_lang_files = $d->getFilesDirs($this->compared_lang);
		$this->compared_lang_files = $this->parseFilesToOneArray($this->compared_lang_files);

        $this->files_list = $this->original_lang_files;

		return $this->files_list;
     }


	function parseFilesToOneArray($files) {

	    $new_files = array();

        foreach($files as $name => $value) {
            if (!is_array($value)) {
				$new_files[] = $value;
            } else {
                foreach($value as $dir => $file) {
                    $new_files[] = $name. '/' .$file;
                }
            }
        }

        sort($new_files); // to have identical nums for files
        return $new_files;
	}


	function getLangName() {
		$conf = array();
		require_once $this->compared_lang . '/config_lang.php';
		return $conf['lang']['name'];
	}


	function getWordCountFields($arr) {
		$counter = 0;
		foreach($arr as $msg) {
            if (is_array($msg)) {
                foreach ($msg as $msg2) {
					$counter += $this->wordCount($msg2);
                }
            } else {
            	$counter += $this->wordCount($msg);
			}
        }

		return $counter;
	}


	function getFields($files, $lang) {
		$arr = array();

		foreach($files as $dir => $file) {
			if(is_array($file)) {
				foreach($file as $file2) {
					$file_check = $dir . '/' . $file2;
					$file_open = $lang . '/' . $dir . '/' . $file2;
					if(in_array($file_check, $this->multi_ini_files)) {
				        $arr[$dir][$file2] = $this->_parseMultiIni($file_open);
				    } else {
				        $arr[$dir][$file2] = parse_ini_file($file_open, 1);
					}
				}

			} else {
				$file_open = $lang . '/' . $file;
				if(in_array($file, $this->multi_ini_files)) {
				       $arr[$file] = $this->_parseMultiIni($file_open);
			    } else {
			        $arr[$file] = parse_ini_file($file_open, 1);
				}
			}
		}

		// echo '<pre>', print_r($files, 1), '</pre>';
		// echo '<pre>', print_r($arr, 1), '</pre>';

		return $arr;
	}


	function wordCount($str) {
        $str = strip_tags($str);
        $str = preg_replace("#\[.*\]#", '', $str);
		return str_word_count($str);
	}


	function charCount($str) {
        $str = trim(strip_tags($str));
        // $str = preg_replace("#\[.*\]#", '', $str);
		return strlen($str);
	}


	function _getEqualValues($file, $equal) {
		if(file_exists($this->compared_lang . '/' . $file)) {

			list($arr1, $arr2) = $this->parseFiles($file);

			// for testing
			// if($file == 'knowledgebase/setting_msg.ini') {
				// return $this->_doArrayIntersect($arr1, $arr2, $equal);
			// }

			return $this->_doArrayIntersect($arr1, $arr2, $equal);
		}
	}


	function _doArrayIntersect($arr1, $arr2, $equal, $a = array()) {

		foreach($arr1 as $k => $v) {
			if(is_array($arr1[$k])) {
				if(isset($arr2[$k])) {
					$r = $this->_array_intersect($arr1[$k], $arr2[$k], $equal);
					$a[0][$k] = $r[0];
					$a[1][$k] = $r[1];
				}

			} else {

                $b = array($k => $v);
                if(isset($arr2[$k])) {
                    $c = array($k => $arr2[$k]);

                    $r = $this->_array_intersect($b, $c, $equal);
                    if($r[0]) {
                        $a[0][$k] = $r[0][$k];
                        $a[1][$k] = $r[1][$k];
                    }
                }
			}
		}

		if(!$a) {
			$a = array(0=>array(), 1=>array());
		}

		return $a;
	}


	// own array_intersect function
	function _array_intersect($arr1, $arr2, $equal, $a = array(), $b = array()) {

		foreach($arr1 as $k => $v) {

			@$compare1 = str_replace(array("\n", "\r"), '', trim($arr1[$k]));
			@$compare2 = str_replace(array("\n", "\r"), '', trim($arr2[$k]));

			if($equal) {
				if($compare1 == $compare2) {
					$a[$k] = $v;
                    $b[$k] = $v;
				}

			} else {

				if($compare1 != $compare2) {
					$a[$k] = $v;
                    @$b[$k] = $arr2[$k];
				}
			}
		}

		return array($a, $b);
	}


	function _getMissedFields($file) {

		if(file_exists($file2 = $this->compared_lang . '/' . $file)) {

            list ($arr1, $arr2) = $this->parseFiles($file);

            if (isset($_GET['mode']) && $_GET['mode'] == 'public') {
                if ($file == 'public/user_msg.ini') {

                    $original_file1 = parse_ini_file($this->original_lang . '/user_msg.ini', 1);
                    $original_file2 = parse_ini_file($this->original_lang . '/public/user_msg.ini', 1);
                    $arr1 = array_merge($original_file1, $original_file2);

                    $compared_file1 = parse_ini_file($this->compared_lang . '/user_msg.ini', 1);
                    $compared_file2 = parse_ini_file($this->compared_lang . '/public/user_msg.ini', 1);
                    $arr2 = array_merge($compared_file1, $compared_file2);
                }
            }


            $ret = false;
            foreach($arr1 as $k => $v) {
                if(isset($arr2[$k])) {
                    if(is_array($v)) {
                        if($d = array_diff(array_keys($v), array_keys($arr2[$k]))) {
                            $ret = true;
                            break;
                        }
                    }
                } else {
                    $ret = true;
                    break;
                }
            }

            // echo '<pre>', print_r($ret, 1), '</pre>';
            return $ret;
		}

        return array();
	}


    function _getEmptyFields($file) {

        $data = array();

        if(file_exists($file2 = $this->compared_lang . '/' . $file)) {
            list ($arr1, $arr2) = $this->parseFiles($file);

            foreach($arr1 as $k => $v) {
                if(is_array($v)) {
                    foreach ($v as $k1 => $v1) {
                        if (trim($v1) !== '' && isset($arr2[$k][$k1]) && trim($arr2[$k][$k1]) === '') {
                            $data[$k][$k1] = $v1;
                        }
                    }

                } else {
                    if (in_array($file, $this->multi_ini_files)) {
                        if (trim($v) !== '' && isset($arr2[$k]) && trim($arr2[$k]) == '') {
                            $data[$k] = $v;
                        }

                    } else {
                        if (trim($v) !== '' && isset($arr2[$k]) && trim($arr2[$k]) === '') {
                            $data[$k] = $v;
                        }
                    }
                }
            }

        }

        return $data;
    }


	function deleteEmpty(&$arr) {

		foreach($arr as $k => $v) {
			if(is_array($arr[$k])) {
				$this->deleteEmpty($arr[$k]);
			}

			if(empty($arr[$k])) { unset($arr[$k]); }
		}
	}


	// parse multilines ini file
	// it will skip all before defining first [block]
	function & _parseMultiIni($file, $key = false) {
		$s_delim = '[';
		$e_delim = ']';

		$str = implode('',file($file));
		if($key && strpos($str, $s_delim . $key . $e_delim) === false) { return; }

		$str = explode($s_delim, $str);
		$num = count($str);

		for($i=1;$i<$num;$i++){
			$section = substr($str[$i], 0, strpos($str[$i], $e_delim));
			$arr[$section] = substr($str[$i], strpos($str[$i], $e_delim)+strlen($e_delim));
		}

		$arr = ($key) ? @$arr[$key] : @$arr;
		return $arr;
	}


	function getLangs() {

		$d = new MyDir;
		$d->setSkipDirs('.svn');
		$d->setSkipDirs('en');
        $d->setSkipDirs('tmpl');
		$d->setAllowedExtension('ini');
		$d->one_level = false;
		$d->full_path = false;

		$arr = array();
		foreach($d->getFilesDirs('.') as $k => $v) {
			if(is_string($k)) {
				$arr[] = $k;
			}
		}

		return $arr;
	}


	static function getLangSelectRange($dir) {

		

		$d = new MyDir();
		$d->full_path = true;
		$d->one_level = false;
		$d->setAllowedExtension('php');
		$d->setSkipDirs('.svn', 'CVS', 'tmpl');
		$dirs = $d->getFilesDirs($dir);

		$range = array();
		foreach($dirs as $k => $v) {
			if(!is_numeric($k) && isset($v[0])) {
				require $v[0];
				$range[$k] = sprintf('%s (%s)', $conf['lang']['name'], $k);
				continue;
			}
		}

		if(!$range) {
			$range = array('en' => 'English (en)');
		}

		return $range;
	}


	function getHTML($public_only = false) {
	    require $this->compared_lang . '/config_lang.php';

		$tpl = new tplTemplatez('tmpl/list.html');

        $dir = 'ltr';
        $align1 = 'left';
        $align2 = 'right';
        if (!empty($conf['lang']['rtl'])) {
            $dir = 'rtl';
            $align1 = 'right';
            $align2 = 'left';
        }

        $tpl->tplAssign('dir', $dir);
        $tpl->tplAssign('align1', $align1);
        $tpl->tplAssign('align2', $align2);

		$update_str = '<a href="test_language.php?mode=translate&f=%s">View</a>';

		$tpl->tplAssign('lang_name', $this->lang_name);

        if ($public_only) {
            $tpl->tplSetNeeded('/public_only');
        } else {
            $tpl->tplSetNeeded('/public_only_link');
        }

        if (substr(PHP_OS, 0, 3) != 'WIN') {
            $tpl->tplSetNeeded('/search');
            $tpl->tplAssign('search_action_link', APP_ADMIN_PATH . 'lang/test_language.php?mode=search');
        }

        $count['words'] = 0;
        $count['chars'] = 0;

		if(!$this->diff['missed_files']) {
			$tpl->tplSetNeeded('/no_missed_files');
		} else {
			$i = 0;

			foreach($this->diff['missed_files'] as $file_name) {
                $file = $file_name;

                if ($public_only && strpos($file_name, 'public/') === false) {
                    continue;
                }
                
				// $count_words += $this->countWordsToTranslate($file);
                $_count = $this->countWordsToTranslate($file);
                $count['words'] += $_count['words'];
                $count['chars'] += $_count['chars'];
				
				$update_link = sprintf($update_str, array_search($file, $this->files_list));

                $row['file'] = $file;
                $row['update_link'] = $update_link;
                $tpl->tplParse($row, 'missed_files_row');
			}
		}


		// missed fields
		if(!$this->diff['missed_fields']) {
			$tpl->tplSetNeeded('/no_missed_fields');
		} else {

			foreach($this->diff['missed_fields'] as $dir => $v) {

                if ($public_only && strpos($dir, 'public/') === false) {
                    continue;
                }
                
				$file = $dir;
                // $count_words += $this->countWordsToTranslate($file);
				$_count = $this->countWordsToTranslate($file);
                $count['words'] += $_count['words'];
                $count['chars'] += $_count['chars'];
				
				$update_link = sprintf($update_str, array_search($file, $this->files_list));

                $row['file'] = $file;
                $row['update_link'] = $update_link;
                $tpl->tplParse($row, 'missed_field_row');
			}
		}
        
        
        // empty fields        
        if(!$this->diff['empty_fields']) {
            $tpl->tplSetNeeded('/no_empty_fields');
        } else {
            $this->getFieldValueBlock($tpl, 'empty', $public_only);
            
            foreach($this->diff['empty_fields'] as $file => $fields) {
                if ($public_only && strpos($file, 'public/') === false) {
                    continue;
                }
                
                // $count_words += $this->countWordsToTranslate($file);
				$_count = $this->countWordsToTranslate($file);
                $count['words'] += $_count['words'];
                $count['chars'] += $_count['chars'];
            }
            
        }


		// equal fields
		if(!$this->diff['equal_fields']) {
			$tpl->tplSetNeeded('/no_equal_fields');
		} else {
            $this->getFieldValueBlock($tpl, 'equal', $public_only);
		}

        $tpl->tplAssign('count_words', number_format($count['words']));
        $tpl->tplAssign('count_chars', number_format($count['chars']));

        $tpl->tplParse();
        return $tpl->tplPrint(1);
	}


    function getFieldValueBlock(&$tpl, $block_key, $public_only) {
        $num = 0;
        $update_str = '<a href="test_language.php?mode=translate&f=%s#%s">View</a>';

        $fields = $this->diff[$block_key . '_fields'];

        foreach($fields as $dir => $v) {
            if ($public_only && $dir != 'public') {
                continue;
            }

            // directory
            if(is_array($v) && !strpos($dir, '.')) {
                foreach($v as $file_name => $fields_arr) {

                    foreach($fields_arr as $msg_key => $values) {
                        $file = $dir . '/' . $file_name;

                        // values array
                        if(is_array($values)) {
                            $field = $msg_key;
                            $sub_field = implode(', ', $values);

                            foreach($values as $key => $value) {
                                $row['file'] = $file;
                                $row['field'] = $msg_key . '[' . $key . ']';
                                $row['update_link'] = sprintf($update_str, array_search($file, $this->files_list), $row['field']);
                                $row['sub_field'] = $value;


                                $tpl->tplParse($row, $block_key . '_field_row');
                            }

                        } else {
                            $row['file'] = $file;
                            $row['field'] = $msg_key;
                            $row['update_link'] = sprintf($update_str, array_search($file, $this->files_list), $row['field']);
                            $row['sub_field'] = $values;

                            $tpl->tplParse($row, $block_key . '_field_row');
                        }
                    }
                }

            // file
            } else {

                foreach($v as $msg_key => $values) {
                    $file = $dir;

                    // values array
                    if(is_array($values)) {
                        $field = $msg_key;
                        $sub_field = implode(', ', $values);

                        foreach($values as $key => $value) {
                            $row['file'] = $file;
                            $row['field'] = $msg_key . '[' . $key . ']';
                            $row['update_link'] = sprintf($update_str, array_search($file, $this->files_list), $row['field']);
                            $row['sub_field'] = $value;

                            $tpl->tplParse($row, $block_key . '_field_row');
                        }

                    } else {
                        $row['file'] = $file;
                        $row['field'] = $msg_key;
                        $row['update_link'] = sprintf($update_str, array_search($file, $this->files_list), $row['field']);
                        $row['sub_field'] = $values;

                        $tpl->tplParse($row, $block_key . '_field_row');
                    }
                }
            }
        }
    }


	function getLanguageHTML($back_link = false) {
	    require $this->compared_lang . '/config_lang.php';

		$this->getNotEqualValues();
		$update_str = '<a href="test_language.php?mode=translate&f=%s">Edit</a>';

		$tpl = new tplTemplatez('tmpl/language.html');

        $dir = 'ltr';
        if (!empty($conf['lang']['rtl'])) {
            $dir = 'rtl';
        }

        $tpl->tplAssign('dir', $dir);

		if($back_link) {
			$tpl->tplSetNeeded('/back_link');
		}

		$tpl->tplAssign('lang_name', $this->lang_name);


		foreach($this->comp_diff['not_equal_fields'] as $dir => $v) {

			// directory
			if(is_array($v) && !strpos($dir, '.')) {

				foreach($v as $file_name => $fields_arr) {

					$file = $dir . '/' . $file_name;
					$update_link = sprintf($update_str, array_search($file, $this->files_list));

					foreach($fields_arr as $msg_key => $values) {

						// values array
						if(is_array($values)) {
							$field = $msg_key;
							$sub_field = implode(', ', $values);

							foreach($values as $key => $value) {

								$row['sub_field'] = $value;
								$row['update_link'] = $update_link;

                                $tpl->tplParse($row, 'row');
							}

						} else {

                                $row['sub_field'] = $values;
                                $row['update_link'] = $update_link;

                                $tpl->tplParse($row, 'row');
						}
					}
				}

			// file
			} else {

				foreach($v as $msg_key => $values) {

					$file = $dir;
					$update_link = sprintf($update_str, array_search($file, $this->files_list));

					// values array
					if(is_array($values)) {
						$field = $msg_key;
						$sub_field = implode(', ', $values);

						foreach($values as $key => $value) {

	                        $row['sub_field'] = $value;
                            $row['update_link'] = $update_link;

                            $tpl->tplParse($row, 'row');
						}

					} else {

                        $row['sub_field'] = $values;
                        $row['update_link'] = $update_link;

                        $tpl->tplParse($row, 'row');
					}
				}
			}
		}

        $tpl->tplParse();
        return $tpl->tplPrint(1);
	}


    function getConfigHTML() {
        require $this->compared_lang . '/config_lang.php';

        $tpl = new tplTemplatez('tmpl/config.html');

        $dir = 'ltr';
        if (!empty($conf['lang']['rtl'])) {
            $dir = 'rtl';
        }

        $tpl->tplAssign('dir', $dir);

        $tpl->tplAssign('name', 'config_lang_file');

        $content = file_get_contents($this->compared_lang . '/config_lang.php');
        $tpl->tplAssign('content', $content);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function getSphinxHTML() {
        require $this->compared_lang . '/config_lang.php';

        $tpl = new tplTemplatez('tmpl/config.html');

        $dir = 'ltr';
        if (!empty($conf['lang']['rtl'])) {
            $dir = 'rtl';
        }

        $tpl->tplAssign('dir', $dir);

        $tpl->tplAssign('name', 'sphinx_lang_file');

        if (file_exists($this->compared_lang . '/sphinx.conf')) {
            $content = file_get_contents($this->compared_lang . '/sphinx.conf');
            $tpl->tplAssign('content', $content);
        }

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    // Write new data to ini
    function writeIniFile($file, $user_data) {

        // read original file
        $original_path = $this->original_lang . '/' . $file;

        if (in_array($file, $this->multi_ini_files)) {
            $original = GetMsgHelper::parseMultiIni($original_path);
        } else {
            $original = GetMsgHelper::parseIni(APP_MSG_DIR . $original_path);
        }

        $content = file_get_contents($original_path);

        if ($file == 'public/user_msg.ini') {
			$original_file1 = parse_ini_file($this->original_lang . '/user_msg.ini', 1);
			$original_file2 = parse_ini_file($this->original_lang . '/public/user_msg.ini', 1);
			$original = array_merge($original_file1, $original_file2);
			$content = file_get_contents($this->original_lang . '/user_msg.ini');
        }

        // get output data
		$original = $this->stripVarFromFile($original);
        $output = $user_data + $original;
        unset($output['ini_filename']);

        // get output string
        foreach ($original as $section => $fields) {

            if (!is_array($fields)) {
				
                $str = $output[$section];

				if((in_array($file, $this->multi_ini_files))) {
					$fields = sprintf("[%s]%s", $section, $fields);
					$str = sprintf("[%s]\n%s", $section, $str);

				} else {
					$fields = sprintf('"%s"', $fields);
					$str = sprintf('"%s"', $str);
				}

				$content = str_replace($fields, $str, $content);

            } else {
                foreach ($fields as $field => $value) {

                    // try with _ instead dot, where option_1.1
                    if (!isset($output[$section][$field])) {
                        $field = str_replace('.', '_', $field);
                    }
					
                    $str = $output[$section][$field];

                    $value = sprintf('"%s"', $value);
                    $str = sprintf('"%s"', $str);

                    $content = str_replace($value, $str, $content);
                }
            }
        }

        return FileUtil::write($this->compared_lang . '/' . $file, $content);
    }


    function writeTxtFile($file, $user_data) {
        $content = $user_data;
        return FileUtil::write($this->compared_lang . '/' . $file, $content);
    }


    function getHtmlIni($original_data, $compared_data, $file, $from) {

        require $this->compared_lang . '/config_lang.php';

        if(in_array($file, $this->multi_ini_files)) {
            $tpl = new tplTemplatez('tmpl/ini_textarea.html');
			
        } elseif($this->getFileExtension($file) == 'txt') {
            $tpl = new tplTemplatez('tmpl/ini_textarea.html');
        
		} else {
            $tpl = new tplTemplatez('tmpl/ini_input.html');
        }

        $tpl->s_var_tag = '{$';

        $dir = 'ltr';
        if (!empty($conf['lang']['rtl'])) {
            $dir = 'rtl';
        }

        $count = $this->countWordsToTranslate($file);
        $tpl->tplAssign('count_words', number_format($count['words']));
        $tpl->tplAssign('count_chars', number_format($count['chars']));

        $tpl->tplAssign('dir', $dir);
        $tpl->tplAssign('file', $file);
        $tpl->tplParse(null, 'hiddenfile');

        $params = ($from) ? '?mode=notequal' : '';
        $tpl->tplAssign('params', $params);
        $tpl->tplParse(null, 'form');

        foreach ($original_data as $section => $fields) {

            if (!is_array($fields)) {

                if ($fields == '') {
                    continue;
                }

                // $v['original'] = nl2br(htmlspecialchars(trim($fields, '"')));
                // $v['compared'] = @trim($compared_data[$section], '"');

                $v['original'] = nl2br(htmlspecialchars(trim($fields)));
                $v['compared'] = @trim($compared_data[$section]);

                $v['parent_section'] = $section;
                $v['field'] = '';
                $v['anchor'] = $section;
                $v['style'] = ($v['compared'] == '') ? 'style="background: #f5a1a1;"' : '';
                $v['class'] = (@$i++ & 1) ? 'trDarker' : 'trLighter';

                $compare1 = str_replace(array("\n", "\r"), '', @trim($compared_data[$section]));
                $compare2 = str_replace(array("\n", "\r"), '', trim($fields));

                if ($compare1 == $compare2) {
                    $v['style'] = 'style="background: lightblue;"';
                }

                $tpl->tplParse($v, 'section/field');

            } else {

                foreach ($fields as $field => $value) {

                    if ($value == '') {
                        continue;
                    }

                    // $v['original'] = nl2br(htmlspecialchars(trim($value, '"')));
                    // $v['compared'] = trim(@$compared_data[$section][$field], '"');

                    $v['original'] = nl2br(htmlspecialchars(trim($value)));
                    $v['compared'] = trim(@$compared_data[$section][$field]);

                    $v['parent_section'] = $section;
                    $v['field'] = ':' . $field;
                    $v['anchor'] = $section . '[' . $field . ']';
                    $v['style'] = ($v['compared'] == '') ? 'style="background: #f5a1a1;"' : '';
                    $v['class'] = (@$i++ & 1) ? 'trDarker' : 'trLighter';

                    $compare1 = str_replace(array("\n", "\r"), '', trim(@$compared_data[$section][$field]));
                    $compare2 = str_replace(array("\n", "\r"), '', trim($value));

                    if ($compare1 == $compare2) {
                        $v['style'] = 'style="background: lightblue;"';
                    }

                    $tpl->tplParse($v, 'section/field');
                }
            }


            $tpl->tplSetNested('section/field');

            $tpl->tplAssign('section', $section);
            $tpl->tplParse(null, 'section');
        }

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }

	
	function _stripVarFromFile($value) {
		$value = str_replace (array("\'", '\"'), "'", $value);
		return $value;
	}

    function stripVarFromFile($arr) {

        if(!is_array($arr)) {
            return $arr = CompareLang::_stripVarFromFile($arr);
        }

        foreach($arr as $k => $v) {
            if(is_array($v)) {
                $arr[$k] = CompareLang::_stripVarFromFile($v);
            } else {
                $arr[$k] = CompareLang::_stripVarFromFile($arr[$k]);
            }
        }

        return $arr;
    }



   function _stripVar($value) {
        $value = str_replace('"', "'", trim($value));
		$value = str_replace (array("\'", '\"'), "'", $value);
        return $value;
    }


    function stripVars($arr) {

        if(!is_array($arr)) {
            return $arr = CompareLang::_stripVar($arr);
        }

        foreach($arr as $k => $v) {
            if(is_array($v)) {
                $arr[$k] = CompareLang::stripVars($v);
            } else {
                $arr[$k] = CompareLang::_stripVar($arr[$k]);
            }
        }

        return $arr;
    }


    function getSearchResult($str) {
        require $this->compared_lang . '/config_lang.php';

		$update_str = 'test_language.php?mode=translate&f=%s&h=%s';
		$search_dir = APP_ADMIN_DIR . 'lang/' . $this->compared_lang;
		$output = $this->doSearch($str, $search_dir);

		$i = 1;
		$files = array();
		$files_match = array();

        if (!empty($output)) {
            foreach ($output as $row) {
                list($filepath, $filecount) = explode(':', $row);

				$filename = substr($filepath, strlen($search_dir)+1);
				$fnum = array_search($filename, $this->files_list);
                if (!$fnum) {
                	continue;
				}

				$files_match[$i] = $filecount;
                $files[$i]['count'] = $filecount;
                $files[$i]['path'] = str_replace($search_dir . '/', '', $filepath);
    			$files[$i]['edit_link'] = sprintf($update_str, $fnum, urlencode($str));
				$i++;
			}
        }


		$tpl = new tplTemplatez('tmpl/search_result.html');

        $dir = 'ltr';
        if (!empty($conf['lang']['rtl'])) {
            $dir = 'rtl';
        }

        $tpl->tplAssign('dir', $dir);

        if (!empty($files)) {
			arsort($files_match);
            foreach ($files_match as $k => $v) {
	            $row = $files[$k];
                $tpl->tplParse($row, 'row');
    		}

		} else {
            $tpl->tplParse(null, 'no_row');
        }


        $tpl->tplAssign('search_string', $str);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


	function doSearch($str, $dir) {

        $exec_str = 'find %s -name "*.ini" -print0 | xargs -0 grep "%s" -ci | grep :0 -v';
        $cmd = sprintf($exec_str, $dir, $str);
        exec($cmd, $output);
		return $output;
	}


    static function getFileExtension($path) {
        return substr($path, strrpos($path, '.')+1);
    }
    
    
    function parseFiles($file) {

        $file1 = $this->original_lang . '/' . $file;
        $file2 = $this->compared_lang . '/' . $file;

        if(in_array($file, $this->multi_ini_files)) {
            $arr1 = $this->_parseMultiIni($file1);
            $arr2 = $this->_parseMultiIni($file2);

        } elseif($this->getFileExtension($file) == 'txt') {
            $fname = basename($file);
            $arr1[$fname] = file_get_contents($file1);
            $arr2[$fname] = file_get_contents($file2);

        } else {
            $arr1 = parse_ini_file($file1, 1);
            $arr2 = parse_ini_file($file2, 1);
        }

        return array($arr1, $arr2);
    }
    
    
    
    function getFileData($file) {
        
        $original_file = APP_MSG_DIR . $this->original_lang . '/' . $file;
        $compared_file = APP_MSG_DIR . $this->compared_lang . '/' . $file;

        $compared_data = array();
        if (in_array($file, $this->multi_ini_files)) {
            $original_data = GetMsgHelper::parseMultiIni($original_file);
            if (file_exists($compared_file)) {
                $compared_data = GetMsgHelper::parseMultiIni($compared_file);
            }

        } elseif(CompareLang::getFileExtension($original_file) == 'txt') {
            $original_data['content'] = file_get_contents($original_file);
            if (file_exists($compared_file)) {
                $compared_data['content'] = file_get_contents($compared_file);
            }

        } else {

            $original_data = GetMsgHelper::parseIni($original_file);
            if (file_exists($compared_file)) {
                $compared_data = GetMsgHelper::parseIni($compared_file);
            }

            if ($file == 'public/user_msg.ini') {

                $original_file1 = parse_ini_file($original_lang . '/user_msg.ini', 1);
                $original_file2 = parse_ini_file($original_lang . '/public/user_msg.ini', 1);
                $original_data = array_merge($original_file1, $original_file2);

                $compared_file1 = parse_ini_file($lang_compare . '/user_msg.ini', 1);
                $compared_file2 = parse_ini_file($lang_compare . '/public/user_msg.ini', 1);
                $compared_data = array_merge($compared_file1, $compared_file2);
             }
        }
        
        
        return array($original_data, $compared_data);
    }
    
    
    function countWordsToTranslate($file) {
        
        $count = ['words' => 0, 'chars' => 0];
        
        list($original_data, $compared_data) = $this->getFileData($file);
        
        foreach ($original_data as $section => $fields) {
            
            if(is_array($fields)) {
                foreach ($fields as $section2 => $fields2) {
                    if(empty($compared_data[$section][$section2])) {
                        $count['words'] += $this->wordCount($fields2);
                        $count['chars'] += $this->charCount($fields2);
                    }
                }
                
            } else {
                if(empty($compared_data[$section])) {
                    $count['words'] += $this->wordCount($fields);
                    $count['chars'] += $this->charCount($fields);
                }
            }
        }
        
        // echo '<pre>', print_r($original_data,1), '</pre>';
        // echo '<pre>', print_r($compared_data,1), '</pre>';
        // echo "<pre>Count_words: {$file} - ", print_r($count_words,1), '</pre>';
        // echo '<pre>', print_r("==========",1), '</pre>';
        
        return $count;
    }
    
    
    function downloadLanguage() {
 
        $lang = $this->compared_lang;
        $zip_file = APP_CACHE_DIR . $lang . '.zip';
        $to_zip = APP_MSG_DIR . $lang;
        
        $zipArchive = new ZipArchive();

        if ($zipArchive->open($zip_file, (ZipArchive::CREATE | ZipArchive::OVERWRITE)) !== true) {
            die("Failed to create archive\n");
        }

        $d = new MyDir;
        $files = &$d->getFilesDirs($to_zip);

        foreach ($files as $k => $v) {
            if (is_array($v)) {
                $zipArchive->addEmptyDir($k);                
                foreach ($v as $v1) {
                    $tmp_file_path = sprintf('%s/%s/%s', $to_zip, $k, $v1);
                    $zipArchive->addFromString(sprintf('%s/%s', $k, $v1), file_get_contents($tmp_file_path));
                }
                
            } else {
                $tmp_file_path = sprintf('%s/%s', $to_zip, $v);
                $zipArchive->addFromString($v, file_get_contents($tmp_file_path));
            }
        }

        if ($zipArchive->status != ZIPARCHIVE::ER_OK) {
            echo "Failed to write files to zip\n";
        }

        $zipArchive->close();
        
        
        $params['data'] = file_get_contents($zip_file);
        $params['gzip'] = false;
        $params['contenttype'] = 'application/zip';

        @unlink($file);

        return WebUtil::sendFile($params,  $lang . '.zip');
    }
}

?>