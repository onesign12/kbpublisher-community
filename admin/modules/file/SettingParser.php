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

namespace File;

use SettingParserCommon;


class SettingParser extends SettingParserCommon
{	
	
	// options parse
	function parseSelectOptions($key, $values, $range = array()) {
		
		// added later filename sort order, manipulation to place it on top
		if($key == 'entry_sort_order') {
			$ption = array();
			$option['opion_10'] = 'Filename (sorted alphabetically)';
			if(isset($values['option_10'])) {
				$option['opion_10'] = $values['option_10'];
				unset($values['option_10']);
			}
			
			$values = $option + $values;
		}
		
		//echo "<pre>"; print_r($values); echo "</pre>";
		return $values;
	}	
	
}
?>