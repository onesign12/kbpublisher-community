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


class ListBuilderData
{
    
    // common columns options
    static $options = array(
        
        'id' => array(
            'type' => 'text',
            'width' => 1,
            'options' => 'text-align: right; font-weight: bold; padding-right: 8px;'
        ),
        
        'title' => array(
            'type' => 'link_tooltip',
            'params' => array(
                'link' => 'entry_link', 
                'options' => 'entry_link_option', 
                'title' => 'title_title', 
                'text' => 'title_entry')
        ),
        
        'author' => array(
            'type' => 'link_tooltip',
            'title' => 'author_msg',
            'width' => 150,
            'params' => array(
                'link' => 'author_link', 
                'text' => 'author',
                'title' => 'author_title')
        ),
        
        'updater' => array(
            'type' => 'link_tooltip',
            'title' => 'updater_msg',
            'width' => 150,
            'params' => array(
                'link' => 'updater_link', 
                'text' => 'updater',
                'title' => 'updater_title')
        ),
        
        'user_num' => array(
            'type' => 'link',
            'title' => 'users_msg',
            'shorten_title' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><path fill="#fff" d="M10.118 16.064c2.293-.529 4.428-.993 3.394-2.945-3.146-5.942-.834-9.119 2.488-9.119 3.388 0 5.644 3.299 2.488 9.119-1.065 1.964 1.149 2.427 3.394 2.945 1.986.459 2.118 1.43 2.118 3.111l-.003.825h-15.994c0-2.196-.176-3.407 2.115-3.936zm-10.116 3.936h6.001c-.028-6.542 2.995-3.697 2.995-8.901 0-2.009-1.311-3.099-2.998-3.099-2.492 0-4.226 2.383-1.866 6.839.775 1.464-.825 1.812-2.545 2.209-1.49.344-1.589 1.072-1.589 2.333l.002.619z"/></svg>',
            'width' => 'min',
            'align' => 'center',
            'params' => array(
                'text' => 'user_num',
                'link' => 'user_link')
        ),
                           
        'active' => array(
            'type' => 'text',
            'title' => 'status_active_msg',
            'shorten_title' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><path fill="white" d="M20 12.194v9.806h-20v-20h18.272l-1.951 2h-14.321v16h16v-5.768l2-2.038zm.904-10.027l-9.404 9.639-4.405-4.176-3.095 3.097 7.5 7.273 12.5-12.737-3.096-3.096z"/></svg>',
            'width' => 'min',
            'align' => 'center',
            'params' => array(
                'text' => 'active_img')
        ),  
                                 
        'published' => array(
            'type' => 'text',
            'title' => 'status_published_msg',
            'shorten_title' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><path fill="white" d="M20 12.194v9.806h-20v-20h18.272l-1.951 2h-14.321v16h16v-5.768l2-2.038zm.904-10.027l-9.404 9.639-4.405-4.176-3.095 3.097 7.5 7.273 12.5-12.737-3.096-3.096z"/></svg>',
            'width' => 'min',
            'align' => 'center',
            'params' => array(
                'text' => 'active_img')
        ),
    
        'status' => array(
            'type' => 'color_box',
            'title' => 'entry_status_msg',
            'shorten_title' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><path fill="white" d="M20 12.194v9.806h-20v-20h18.272l-1.951 2h-14.321v16h16v-5.768l2-2.038zm.904-10.027l-9.404 9.639-4.405-4.176-3.095 3.097 7.5 7.273 12.5-12.737-3.096-3.096z"/></svg>',
            'width' => 'min',
            'align' => 'center',
            'params' => array(
                'title' => 'status',
                'color' => 'color')
        ),
        
        'hits' => array(
            'type' => 'text',
            'title' => 'hits_num_msg',
            'shorten_title' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="#fff" d="M12.015 7c4.751 0 8.063 3.012 9.504 4.636-1.401 1.837-4.713 5.364-9.504 5.364-4.42 0-7.93-3.536-9.478-5.407 1.493-1.647 4.817-4.593 9.478-4.593zm0-2c-7.569 0-12.015 6.551-12.015 6.551s4.835 7.449 12.015 7.449c7.733 0 11.985-7.449 11.985-7.449s-4.291-6.551-11.985-6.551zm-.015 3c-2.21 0-4 1.791-4 4s1.79 4 4 4c2.209 0 4-1.791 4-4s-1.791-4-4-4zm-.004 3.999c-.564.564-1.479.564-2.044 0s-.565-1.48 0-2.044c.564-.564 1.479-.564 2.044 0s.565 1.479 0 2.044z"/></svg>',
            'width' => 'min',
            'align' => 'center'
        ),
        
        'schedule' => array(
            'type' => 'icon',
            'shorten_title' => 0,
            'width' => 1,
            'padding' => 0,
            'class' => 'tdSchedule',
            'params' => array(
                'img' => '%clock.svg%', 
                'title' => 'schedule_title')
        ),
    
        'private' => array(
            'type' => 'icon',
            'shorten_title' => 0,
            'width' => 1,
            'padding' => 0,
            'class' => 'tdPrivate',
            'params' => array(
                'img' => 'private_img', 
                'title' => 'private_title')
        ),
        
        'mustread' => array(
            'type' => 'icon',
            'shorten_title' => 0,
            'width' => 1,
            'padding' => 0,
            'class' => 'tdMustread',
            'params' => array(
                'img' => '%mustread.svg%', 
                'title' => 'mustread_title')
        ),
        
        'sort_order' => array(
            'type' => 'text',
            'shorten_title' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><path fill="white" d="M8 10v4h4l-6 7-6-7h4v-4h-4l6-7 6 7h-4zm16 5h-10v2h10v-2zm0 6h-10v-2h10v2zm0-8h-10v-2h10v2zm0-4h-10v-2h10v2zm0-4h-10v-2h10v2z"/></svg>',
            'width' => 'min',
            'align' => 'center'
        ),
    );
    
    
}
?>