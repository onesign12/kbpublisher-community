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

// file and afile actions differ
// file goes after download action
// afile for files inserted as attachments or for inline files


class KBClientAction_file extends KBClientAction_common
{

    function execute($controller, $manager) {
        
        $data = $manager->getEntryById($this->entry_id, $this->category_id);
        
        // does not matter why no file, deleted, or inactive or private
        if(!$data) {
            
            // new private policy, check if entry exists 
            if($manager->is_registered) { 
                if($manager->isEntryExistsAndActive($this->entry_id, $this->category_id)) {
                    $controller->goAccessDenied('files');
                }
            }
            
            $controller->goStatusHeader('404');
        }
        
        
        $file_dir = $manager->getSetting('file_dir');
        
        if(!FileEntryUtil::getFileDir($data, $file_dir)) {
            $controller->go('files', false, false, 'file_notfound');
        }
        
		if(!empty($_GET['f'])) { // open
            $attachment = false;
            $url = $controller->getLink('file', false, $this->entry_id);
            $controller->setCanonicalHeader($url);
        
        } else { // download
            $attachment = true;
            // $url = $controller->getLink('file', false, $this->entry_id, false, array('f'=>1));
            // $controller->setCanonicalHeader($url);
        }

        if($manager->isUserViewed($this->entry_id) === false) {
            $manager->addDownload($this->entry_id);
            $manager->setUserViewed($this->entry_id);
        }
        
        //unset($data['filetext']);
        FileEntryUtil::sendFileDownload($data, $file_dir, $attachment);
        
        UserActivityLog::add('file', 'view', $this->entry_id);
        
        // if enabled output_compression then sometimes it does not sent download
        // try to comment it
        //@$controller->go('files', $this->category_id);
        exit();
    }
}
?>