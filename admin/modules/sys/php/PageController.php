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

ob_start();
phpinfo();
$str = ob_get_contents();

$style = '<style type="text/css">
          <!--
          table {border-collapse: collapse;}
          td, th { border: 0px solid #000000; font-size: 100%; vertical-align: baseline;}
          -->
          </style>';

$pattern = '#<html>.*?(<style type="text\/css"><!--.*?\/\/--><\/style>).*?<body>(.*?)<\/body><\/html>#si';
preg_match($pattern, $str, $match);

$view = $style . $match[1] . $style . $match[2];

ob_end_clean();
?>