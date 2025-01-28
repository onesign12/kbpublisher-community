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


class PageDesignData
{
    
    static $blocks = array(
        'news' => array(
            'title' => 'news_title_msg',
            'pages' => array(
                'index'
            ),
            'settings' => array(
                'num_entries' => 1
            ),
            // 'post_actions' => array(
            //     'activateNews'
            // )
        ),
        
        'featured' => array(
            'title' => 'featured_entries_title_msg',
            'pages' => array(
                'index'
            ),
            'settings' => array(
                'num_entries' => 5
            )
        ),
        
        'recent' => array(
            'title' => 'recently_posted_entries_title_msg',
            'pages' => array(
                'index'
            ),
            'settings' => array(
                'num_entries' => 5
            )
        ),
        
        'most_viewed' => array(
            'title' => 'most_viewed_entries_title_msg',
            'pages' => array(
                'index'
            ),
            'settings' => array(
                'num_entries' => 5
            )
        ),
        
        'recent_files' => array(
            'title' => 'recently_posted_files_title_msg',
            'settings' => array(
                'num_entries' => 5
            ),
            // 'post_actions' => array(
            //     'activateFiles'
            // )
        ),
        
        'most_downloaded' => array(
            'title' => 'most_downloaded_files_title_msg',
            'settings' => array(
                'num_entries' => 5
            ),
            // 'post_actions' => array(
            //     'activateFiles'
            // )
        ),
        
        'search' => array(
            'title' => 'search_msg',
            // 'editable' => false,
            'pages' => array(
                'index'
            ),
        ),
        
        'top_category' => array(
            'title' => 'category_title_msg',
            'settings' => array(
                'num_columns' => 3
            )
        )
    );
    
    
    static $defaults = array(
        'default' => array(
            'index' => array(
                array(
                    'id' => 'search',
                    'x' => 0,
                    'y' => 0,
                    'width' => 6,
                    'height' => 1,
                    'settings' => array()
                ),
                array(
                    'id' => 'news',
                    'x' => 0,
                    'y' => 1,
                    'width' => 6,
                    'height' => 1,
                    'settings' => array(
                        'num_entries' => 1
                    )
                ),
                array(
                    'id' => 'top_category',
                    'x' => 0,
                    'y' => 2,
                    'width' => 6,
                    'height' => 1,
                    'settings' => array(
                        'num_columns' => 3
                    )
                ),
                array(
                    'id' => 'featured',
                    'x' => 0,
                    'y' => 3,
                    'width' => 6,
                    'height' => 1,
                    'settings' => array(
                        'num_entries' => 5
                    )
                ),
                array(
                    'id' => 'most_viewed',
                    'x' => 0,
                    'y' => 4,
                    'width' => 3,
                    'height' => 1,
                    'settings' => array(
                        'num_entries' => 5
                    )
                ),
                array(
                    'id' => 'recent',
                    'x' => 3,
                    'y' => 4,
                    'width' => 3,
                    'height' => 1,
                    'settings' => array(
                        'num_entries' => 5
                    )
                )
             ),
             
             'files' => array(
                 array(
                    'id' => 'recent_files',
                    'x' => 0,
                    'y' => 0,
                    'width' => 3,
                    'height' => 1,
                    'settings' => array(
                        'num_entries' => 5
                    )
                ),
                array(
                    'id' => 'most_downloaded',
                    'x' => 3,
                    'y' => 0,
                    'width' => 3,
                    'height' => 1,
                    'settings' => array(
                        'num_entries' => 5
                    )
                )
            )
        ),
        
        'left' => array(
            'index' => array(
                array(
                    'id' => 'search',
                    'x' => 0,
                    'y' => 0,
                    'width' => 6,
                    'height' => 1
                ),
                array(
                    'id' => 'news',
                    'x' => 0,
                    'y' => 1,
                    'width' => 6,
                    'height' => 1,
                    'settings' => array(
                        'num_entries' => 1
                    )
                ),
                array(
                    'id' => 'featured',
                    'x' => 0,
                    'y' => 2,
                    'width' => 6,
                    'height' => 1,
                    'settings' => array(
                        'num_entries' => 5
                    )
                ),
                array(
                    'id' => 'most_viewed',
                    'x' => 0,
                    'y' => 3,
                    'width' => 3,
                    'height' => 1,
                    'settings' => array(
                        'num_entries' => 5
                    )
                ),
                array(
                    'id' => 'recent',
                    'x' => 3,
                    'y' => 3,
                    'width' => 3,
                    'height' => 1,
                    'settings' => array(
                        'num_entries' => 5
                    )
                )
             ),
             
             'files' => array(
                 array(
                    'id' => 'recent_files',
                    'x' => 0,
                    'y' => 0,
                    'width' => 3,
                    'height' => 1,
                    'settings' => array(
                        'num_entries' => 5
                    )
                ),
                array(
                    'id' => 'most_downloaded',
                    'x' => 3,
                    'y' => 0,
                    'width' => 3,
                    'height' => 1,
                    'settings' => array(
                        'num_entries' => 5
                    )
                )
            )
        ),
        'fixed' => array(
            'index' => array(
                array(
                    'id' => 'search',
                    'x' => 0,
                    'y' => 0,
                    'width' => 6,
                    'height' => 1,
                    'settings' => array()
                ),
                array(
                    'id' => 'news',
                    'x' => 0,
                    'y' => 1,
                    'width' => 6,
                    'height' => 1,
                    'settings' => array(
                        'num_entries' => 1
                    )
                ),
                array(
                    'id' => 'featured',
                    'x' => 0,
                    'y' => 2,
                    'width' => 6,
                    'height' => 1,
                    'settings' => array(
                        'num_entries' => 5
                    )
                ),
                array(
                    'id' => 'most_viewed',
                    'x' => 0,
                    'y' => 3,
                    'width' => 3,
                    'height' => 1,
                    'settings' => array(
                        'num_entries' => 5
                    )
                ),
                array(
                    'id' => 'recent',
                    'x' => 3,
                    'y' => 3,
                    'width' => 3,
                    'height' => 1,
                    'settings' => array(
                        'num_entries' => 5
                    )
                )
             ),
             
             'files' => array(
                 array(
                    'id' => 'recent_files',
                    'x' => 0,
                    'y' => 0,
                    'width' => 3,
                    'height' => 1,
                    'settings' => array(
                        'num_entries' => 5
                    )
                ),
                array(
                    'id' => 'most_downloaded',
                    'x' => 3,
                    'y' => 0,
                    'width' => 3,
                    'height' => 1,
                    'settings' => array(
                        'num_entries' => 5
                    )
                )
            )
        )
    );
    
}

?>