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


class ListBuilder
{
    
    private $customize_link;
    private $columns;
    private $columns_sort; //saved in settings to be able to sort available columns in customize view added 26-10-2022
    private $eview;
    private $col_options;
    
    
    public function __construct($view, $page = false) {
        
        // by this key we save it in list_columns settings 
        $key = $page;
        if(!$key) {
            $key = $view->controller->page;
            if($view->controller->action) {
                $key .= '-' . $view->controller->action;
            }
        }
        
        $list_options = array();
        $list_columns = $view->getListColumns();
        
        foreach($list_columns as $k => $v) {
            if(!is_array($v)) { // not array
                unset($list_columns[$k]);
                $list_columns[$v] = array();
            } elseif(empty($v)) { // empty array
                // unset($list_columns[$k]);
            } else {
                $list_options[$k] = $v;
            }
        }
        
        // remove plugins
        $pluginable = AppPlugin::getPluginsFiltered('column', true);
        foreach($pluginable as $k => $plugin) {
            if(!AppPlugin::isPlugin($plugin)) {
                unset($list_columns[$k]);
            }
        }
        
        $setting_columns = SettingModel::getQuickUser(AuthPriv::getUserId(), 0, 'list_columns');        
        $setting_columns = unserialize($setting_columns);
        $setting_columns_disp = (isset($setting_columns[$key])) ? $setting_columns[$key] : array();
        $setting_columns_sort = (isset($setting_columns[$key.'_sort'])) ? $setting_columns[$key.'_sort'] : array();

        $columns = $view->columns; // default columns
        $columns_sort = [];
        // $columns_sort = $view->columns;
        
        if(!empty($setting_columns_disp)) {
            // always reduce user's fields to available fields
            $columns = array_intersect($setting_columns_disp, array_keys($list_columns));
            if(!empty($setting_columns_sort)) {
                $columns_sort = array_intersect($setting_columns_sort, array_keys($list_columns));
            }
        } else {
            $columns = array_intersect($columns, array_keys($list_columns));
        }
        
        $this->setColumns($columns);
        $this->setColumnsSort($columns_sort);
        $this->col_options = array_replace(ListBuilderData::$options, $list_options);
                
        $search = sprintf("#%s|%s#", APP_MODULE_DIR, APP_PLUGIN_DIR);
        $_module = $view->controller->module;
        $_page = urlencode($key);
        // echo '<pre>' . print_r($view->controller->working_dir, 1) . '</pre>';
        $_view = preg_replace($search, '', $view->controller->working_dir);
        $_view .= urlencode('inc/' . get_class($view));
        $_d = (strpos($view->controller->working_dir, 'plugin')) ? 'plugin' : 'module';
        $more = ['_module'=>$_module, '_page'=>$_page, '_view'=>$_view, '_d'=>$_d];
        $this->customize_link = $view->getLink('stuff', 'list_builder', false, 'customize_list', $more);
     
        $reg =& Registry::instance();
        $reg->setEntry('customize_list_link', $this->customize_link);
        
        $this->eview =& $view;
    }
    
    
    function getListVars($sort, $msg) {
        $v = array();
        $v['customize_list_link'] = $this->customize_link;
        $v['list_title'] = $this->getTitles($sort, $msg);
        return $v;
    }
    
    
    function setColumns($arr) {
        $this->columns = array_map('trim', $arr);
    }
    
    
    function getColumns() {
        return $this->columns;
    }


    function setColumnsSort($arr) {
        $this->columns_sort = array_map('trim', $arr);
    }
    
    
    function getColumnsSort() {
        return $this->columns_sort;
    }
    
        
    function getListInTemplate() {
        $tmpl = APP_MODULE_DIR . 'stuff/list_builder/template/list_in.html';
        return $tmpl;
    }
    
    
    function getTemplate() {
        
        $tmpl = (!empty($this->eview->template)) ? $this->eview->template : 'list.html';
        $tmpl = APP_MODULE_DIR . 'stuff/list_builder/template/' . $tmpl;
        
        $popup = $this->eview->controller->getMoreParam('popup');
        
        // reset customize link not to generate customize link in [...] button
        if(strpos($tmpl, 'list_no_customize') !== false) {
            $reg =& Registry::instance();
            $reg->destroyEntry('customize_list_link');
        }
        
        // echo '<pre>', print_r($this->eview,1), '<pre>';
        // exit;
        
        if($popup) {            
            if(!empty($this->eview->template_popup)) {
                
                if(is_array($this->eview->template_popup)) {
                    if(!isset($this->eview->template_popup[$popup])) {
                        if(isset($this->eview->template_popup[1])) { // defaults to 1
                            $tmpl = $this->eview->template_dir . $this->eview->template_popup[1];
                        } else {
                            trigger_error("Unable to find popup template.");
                            exit();
                        }
                    } else {
                        $tmpl = $this->eview->template_popup[$popup];
                        $tmpl = $this->eview->template_dir . $this->eview->template_popup[$popup];
                    }
                
                } else {
                    $tmpl = $this->eview->template_dir . $this->eview->template_popup;
                }
                
            } //else {
                //trigger_error("Popup template not defined.");
                //exit();
                //}
            
            // popup columns
            if(!empty($this->eview->columns_popup)) {
                $columns_popup = $this->eview->columns_popup;
                $multi_arr = (count($columns_popup) != count($columns_popup, COUNT_RECURSIVE));
                if($multi_arr) {
                    if(!isset($columns_popup[$popup])) {
                        trigger_error("Unable to find popup collums.");
                        exit();
                    } else {
                        $columns_popup = $columns_popup[$popup];
                    }
                }
                
                $this->setColumns($columns_popup);
            }
            
        }
        
        // echo $tmpl;
        return $tmpl;
    }
    
    
    public function getTitles($sort_html, $msg) {
        
        $row_tr = array();
        
        foreach ($this->columns as $column) {
            
            $title = $this->getColumnTitle($column, $msg);
            
            $shorten = $this->getColumnField($column, 'shorten_title');
            if ($shorten !== false) {
                if(is_numeric($shorten)) {
                    $title = ($shorten) ? AppView::shortenTitle($title, $shorten) : '';
                } else {
                    $title = AppView::shortenTitle($title, 1000000, $shorten);
                }
            }
            
            // style
            $td_style = array();
            
            $width = $this->getColumnField($column, 'width');
            if($width !== false) {
                $width = ($width === 'min') ? 25 : $width;
                $width = (is_numeric($width)) ? sprintf('%dpx', $width) : $width;
                $td_style[] = sprintf('width: %s;', $width);
            }
            
            $padding = $this->getColumnField($column, 'padding');
            if($padding !== false) {
                $padding = (is_numeric($padding)) ? sprintf('%dpx', $padding) : $padding;
                $td_style[] = sprintf('padding: %s;', $padding);
            }
            
            if($align = $this->getColumnField($column, 'align')) {
                $td_style[] = sprintf('text-align: %s;', $align);
            }
            
            $td_style = ($td_style) ? sprintf(' style="%s"', implode(' ', $td_style)) : '';
            
            // add class if any
            if($class = $this->getColumnField($column, 'class')) {
                $td_style .= sprintf(' class="%s"', $class);
            }
            
            $title_key = $this->getColumnTitleKey($column);
            if(strpos($title_key, '%') === 0) {
                $title_key = trim($title_key, '%');
            }
            
            if (isset($sort_html[$title_key])) {
                if ($shorten !== false) { // shorten sorting msg 
                    $title = preg_replace("#(<a[^>]*>)(\w+)(</a>)#u", "\\1{$title}\\3", $sort_html[$title_key]);
                } else {
                    $title = $sort_html[$title_key];
                }
            }
            
            $row_tr[] = sprintf('<td%s>%s</td>', $td_style, $title);
        }
        
        return implode("\n", $row_tr);
    }
    
    
    public function getRow($row) {

        $replacer = new Replacer();
        $list_type = WebUtil::parseMultiIni(APP_MODULE_DIR . 'stuff/list_builder/template/list_type.ini');
        
        $row_tr = array();

        foreach ($this->columns as $column) {
            
            $type = $this->getColumnField($column, 'type');
            $type = ($type) ? $type : 'text';
            
            $v = array();
            $v['style'] = $row['style'];
            $v['base_href'] = APP_CLIENT_PATH;
        
            $cparams = $this->getColumnParam($column);
            if($cparams) {
                foreach($cparams as $ckey => $cparam) {
                    $cparam = trim($cparam);
                    if(strpos($cparam, '%') === 0) {
                        $v[$ckey] = trim($cparam, '%');
                    } elseif(isset($row[$cparam])) {
                        $v[$ckey] = $row[$cparam];
                    }
                }
            }
        
            // if param text is empty trying to find in in row by column name
            if(empty($v['text']) && empty($cparams['text'])  && !empty($row[$column])) {
                $v['text'] = $row[$column];
            }
            
            // make $type empty
            if($type == 'icon' && (empty($v['title']) || empty($v['img']))) {
                $type = 'empty';
            } elseif($type == 'bullet' && empty($v['text'])) {
                $type = 'empty';
            }

            $td_style = array();
            
            $padding = $this->getColumnField($column, 'padding');
            if($padding !== false) {
                $padding = (is_numeric($padding)) ? sprintf('%dpx', $padding) : $padding;
                $td_style[] = sprintf('padding: %s;', $padding);
            }
            
            if($align = $this->getColumnField($column, 'align')) {
                $td_style[] = sprintf('text-align: %s;', $align);
            }
            
            if($options = $this->getColumnOptions($column)) {
                $td_style[] = $options;
            }
            
            $td_style = ($td_style) ? sprintf(' style="%s"', implode(' ', $td_style)) : '';
            
            // add class if any
            if($class = $this->getColumnField($column, 'class')) {
                $td_style .= sprintf(' class="%s"', $class);
            }
            
            $str = '<td%s>%s</td>';
            if ($type != 'empty') {
                $row_tr[] = sprintf($str, $td_style, $replacer->parse($list_type[$type], $v));
            } else {
                $row_tr[] = sprintf($str, $td_style, '');
            }
        }

        // echo '<pre>', print_r($row_tr,1), '<pre>';
        // exit;

        return implode("\n\t", $row_tr);
    }
        
    
    public function getColumnTitleKey($key) {
        $msg_key = ($t = self::getColumnField($key, 'title')) ? $t : $key . '_msg';
        return $msg_key;
    }
        

    public function getColumnTitle($key, $msg) {
        $tkey = $this->getColumnTitleKey($key);
        if(strpos($tkey, '%') === 0) {
            $title = trim($tkey, '%');
        } else {
            $title = $msg[$tkey];
        }
        
        return $title;
    }
    
    
    public function getColumnParam($key, $lkey = false) {
        if($lkey) {
              $params = self::getColumnField($key, 'params');
              return isset($params[$lkey]) ? $params[$lkey] : false;
        } else {
            return self::getColumnField($key, 'params');
        }
    }
    
    
    public function getColumnOptions($key) {
        return self::getColumnField($key, 'options');
    }

    
    public function getColumnField($key, $field) {
        return (isset($this->col_options[$key][$field])) ? $this->col_options[$key][$field] : false;
    }
}
?>