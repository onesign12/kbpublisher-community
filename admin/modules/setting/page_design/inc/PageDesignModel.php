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

require_once 'PageDesignData.php';


class PageDesignModel extends AppModel
{

    var $tables = array('table' => 'stuff_data');
    
    static $grid_size = 6;
    
    
    var $block_to_setting = array(
        'news' => 'news',
        'recent_files' => 'file',
        'most_downloaded' => 'file'
    );
    
    
    function __construct() {
        parent::__construct();
        
        $this->sm = new SettingModel;
    }
    
    
    function getDesign($key) {
        $setting = SettingModel::getQuick(11, 'page_design_' . $key);
        if ($setting) {
            $data = json_decode($setting, true);
            
        } else { // default
            $view_format = SettingModel::getQuick(2, 'view_format');
            $data = PageDesignData::$defaults[$view_format][$key];
        }
        
        return $data;
    }
    
    
    static function getHtmlGrid($layout, $manager) {
        
        $data = array();
        $blocks = json_decode($layout, true); // can be unsorted
        
        $max_y = 0;
        $blocks_by_y = array();
        foreach ($blocks as $block) {
            if ($block['y'] + $block['height'] > $max_y) {
                $max_y = $block['y'] + $block['height'];
            }
            
            $blocks_by_y[$block['y']][$block['x']] = $block;
        }
        
        ksort($blocks_by_y);
        
        // splitting along the y-axis
        $rows = array_fill(0, $max_y, array());
        foreach ($blocks_by_y as $blocks) {
            foreach ($blocks as $block) {
                if ($block['height'] > 1) { // rowspan
                    $index = array_search($block['y'], array_keys($rows)) + 1;
                    array_splice_assoc($rows, $index, $block['height'] - 1);
                    continue 2;
                }
            }
        }
        
        $y_vector = $rows;
        $y_vector[$max_y] = array();
        
        $y_grid = array();
        $start = 0;
        foreach ($y_vector as $k => $v) {
            if ($k == 0) {
                $start = $k;
                continue;
            }
            
            $y_grid[] = range($start, $k - 1);
            $start = $k;
        }
        
        
        $grid = array();
        $row_str = '<div class="grid-x">%s</div>';
        $block_str = '<div class="small-12 medium-%d columns">%s</div>';
        //echo 'Y Grid: <br />';var_dump($y_grid);die();
        
        foreach ($y_grid as $rows) {
            
            $block_height = count($rows);
            $blocks_by_x = array();
            
            $y = current($rows);
            $last_y = end($rows);
            reset($rows);
            
            foreach ($rows as $y1) {
                if (!empty($blocks_by_y[$y1])) {
                    foreach ($blocks_by_y[$y1] as $block) {
                        
                        // nested rowspan
                        if ($block['height'] > 1 && $block['height'] != $block_height) {
                            throw new Exception($block['id']);
                        }
                        
                        // colspan
                        if ($block_height > 1 && $block['width'] > 1 && $block['height'] != $block_height) { // rowspan
                            $x_coords = array_fill($block['x'] + 1, $block['width'] - 1, array()); // to check for intersections
                            
                            $end = ($y == 0 && $last_y == 1) ? $last_y + 1 : $last_y;
                            $colspan_y_vector = array_fill($y, $end, array());
                            
                            unset($colspan_y_vector[$block['y']]);
                            
                            foreach ($x_coords as $x_coord => $v) {
                                foreach ($colspan_y_vector as $y_coord => $v1) {
                                    if (!empty($blocks_by_y[$y_coord])) {
                                        foreach ($blocks_by_y[$y_coord] as $block1) {
                                            if ($block1['x'] == $x_coord && $block1['y'] == $y_coord) { // caught an intersection
                                                throw new Exception($block['id']);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        
                        $blocks_by_x[$block['x']][] = $block;
                    }
                }
            }
            
            ksort($blocks_by_x);
            
            $x_vector = array_fill(1, PageDesignModel::$grid_size - 1, array());
            for ($i = 0; $i < $block_height; $i ++) {
                if (!empty($blocks_by_y[$y + $i])) {
                    ksort($blocks_by_y[$y + $i]);
                    
                    foreach (array_keys($x_vector) as $x_coord) {
                        if (!array_key_exists($x_coord, $blocks_by_y[$y + $i])) {
                            if ($i == 1 && isset($blocks_by_y[$y][$x_coord])) { // rowspan
                                continue;
                            }
                            unset($x_vector[$x_coord]);
                        }
                    }
                }
            }
            
            $x_vector[PageDesignModel::$grid_size] = array();
            
            $x_grid = array();
            $start = 0;
            foreach ($x_vector as $k => $v) {
                $x_grid[] = range($start, $k - 1);
                $start = $k;
            }
            
            //var_dump('X GRID: ', $x_grid);
            
            $grid_coef = round(12 / PageDesignModel::$grid_size);
            $row_cells = array();
            
            foreach ($x_grid as $cell_coords) { // horizontal cell
                $cell_content = array();
            
                foreach ($cell_coords as $cell_coord) { // content downwards
                    
                    if (empty($blocks_by_x[$cell_coord])) {
                        continue;
                    }
                    
                    foreach ($blocks_by_x[$cell_coord] as $cell_block) {
                        $white_space = (empty($cell_block['id']));
                        
                        $attributes = array();
                        if (!$white_space && !empty(PageDesignData::$blocks[$cell_block['id']]['settings'])) {
                            foreach (PageDesignData::$blocks[$cell_block['id']]['settings'] as $key => $default_value) {
                                $attributes[] = sprintf('data-%s="%s"', $key, $cell_block['settings'][$key]);
                            }
                        }
                        
                        // title
                        $title_str = '<div class="tdTitle">%s</div>';
                        if (!$white_space && substr($cell_block['id'], 0, 6) == 'custom') { // custom block
                            $custom_block_id = substr($cell_block['id'], 7);
                            $custom_block = $manager->getById($custom_block_id);
                            
                            $options = unserialize($custom_block['data_string']);
                            $title = ($options['title']) ? sprintf($title_str, $options['title']) : '';
                            
                        } elseif (!empty($cell_block['settings']['title'])) { // new title
                            $title = $cell_block['settings']['title'];
                            $title = sprintf($title_str, $title);
                            
                        } else { // built-in
                            $skip_titles = array('search');
                            $title = ($white_space || in_array($cell_block['id'], $skip_titles)) ?
                                '' : sprintf($title_str, sprintf('{%s}', PageDesignData::$blocks[$cell_block['id']]['title']));
                        }
                        
                        $attributes = implode(' ', $attributes);
                        
                        $str = '<div %s>%s[block_%s]</div>';
                        if (!$white_space) {
                            $attributes = sprintf('data-block_id="%s" ', $cell_block['id']) . $attributes;
                        }

                        $html_id = ($white_space) ? 'white_space' : $cell_block['id'];
                        $cell_content[] = sprintf($str, $attributes, $title, $html_id);
                    }
                }
                
                $grid_num = count($cell_coords) * $grid_coef;
                $row_cells[] = sprintf($block_str, $grid_num, implode('', $cell_content));
            }

            $grid[] = sprintf($row_str, implode('', $row_cells));
        }

        $data = implode('', $grid);
        return $data;
    }
    
}
?>