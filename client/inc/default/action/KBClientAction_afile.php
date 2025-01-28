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

// afile - for attached files
// file - for files in files area


class KBClientAction_afile extends KBClientAction_common
{

    function execute($controller, $manager) {
        $embed = (!empty($_GET['embed']));
        if($embed) {
            return $this->executeEmbed($controller, $manager);
        } else {
            return $this->executeAttached($controller, $manager);
        }
    }
    
    
    function executeAttached($controller, $manager) {
        
        // before 5.0, to make it compattible with old versions
        // index.php?View=afile&CategoryID=2&EntryID=1 
        if(empty($_GET['AttachID'])) {
            $entry_id = (int) $controller->getRequestVar('category_id'); // here it is entry to what file attached
            $file_id = (int) $controller->getRequestVar('entry_id');
            $category_ids = $manager->getCategoryIdsByEntryId($entry_id);
            $category_ids = array_intersect($category_ids, array_keys($manager->categories)); // remove not allowed
            $category_id = current($category_ids);
        
        // after 5.0
        // index.php?View=afile&EntryID=10494&AttachID=83
        } else {
            $entry_id = (int) $this->entry_id; // entry to what file attached
            $file_id = (int) $_GET['AttachID'];
            $category_id =  $this->category_id;
        }
        
        // get article to check if we have access to it
        $row = $manager->getEntryById($entry_id, $category_id);
        

        // does not matter why no article, deleted, or inactive or private
        if(!$row) {
            
            // new private policy, check if entry exists 
            // in attached files we do not have private files 
            if($manager->is_registered) { 
                if($manager->isEntryExistsAndActive($entry_id, $category_id)) {
                    $controller->goAccessDenied('index');
                }
            }
            
            $controller->goStatusHeader('404');
        }
        
        $data = $manager->getAttachment($entry_id, $file_id);
        $file_dir = $manager->getSettings(1, 'file_dir');
        
                
        // not attached or not active
        if(!$data) {
            $controller->goStatusHeader('404');
        }
        
        // no file in diretory
        if(!FileEntryUtil::getFileDir($data, $file_dir)) {
            $link = $controller->getLink('entry', $this->category_id, $entry_id, 'file_notfound');
            $controller->goUrl($link);
        }
        
        
        $attachment = true; // download
		if(!empty($_GET['f'])) { // open
            $attachment = false;
            $url = $controller->getLink('file', false, $file_id);
            $controller->setCanonicalHeader($url);
        }
        
        
        $file_manager = &KBClientLoader::getManager($manager->setting, $controller, 'files');        
        if($file_manager->isUserViewed($file_id) === false) {
            $file_manager->addDownload($file_id);
            $file_manager->setUserViewed($file_id);
        }

        FileEntryUtil::sendFileDownload($data, $file_dir, $attachment);    
        UserActivityLog::add('file', 'view', $file_id);
        exit();
    }    
    
    
    function executeEmbed($controller, $manager) {
        
        $preview = false;
        if(isset($_SERVER['HTTP_REFERER'])){
            $ref = parse_url($_SERVER['HTTP_REFERER']);
            $preview = (strpos($ref['path'], '/admin/index.php') !== false);
        }
        
        $entry_id = (int) $this->entry_id; // entry to what file attached
        $file_id = (int) $_GET['AttachID'];
        $category_id =  $this->category_id;
        
        // get article to check if we have access to it
        $row = $manager->getEntryById($entry_id, $category_id);
        
        // does not matter why no article, deleted, or inactive or private
        if(!$row && !$preview) {
            
            // new private policy, check if entry exists 
            // in attached files we do not have private files 
            if($manager->is_registered) { 
                if($manager->isEntryExistsAndActive($entry_id, $category_id)) {
                    $controller->goAccessDenied('index');
                }
            }
            
            $controller->goStatusHeader('404');
        }
        
        
        if($preview && !$entry_id) { // preview not saved article
            $data = $manager->getFileById($file_id);
        } else {
            $data = $manager->getAttachment($entry_id, $file_id);
        }
        
        $file_dir = $manager->getSettings(1, 'file_dir');
                
                
        // not attached or not active
        if(!$data) {
            echo $this->getEmbedError();
            exit();
        }
        
        // no file in diretory
        if(!$file_path = FileEntryUtil::getFileDir($data, $file_dir)) {
            echo $this->getEmbedError();
            exit();
        }
        
        $name = urlencode($data['filename']);
        $content = file_get_contents($file_path);
        header('Content-Type: application/pdf');
        header('Content-Length: '.strlen( $content ));
        header('Content-disposition: inline; filename="' . $name . '"');
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        // header("Content-Security-Policy: frame-src 'self'");
        // header('Cache-Control: public, must-revalidate, max-age=0');
        // header('Pragma: public');
        // header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        // header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');j
        echo $content;
        exit();
    }
    
    
    function getEmbedError() {
        $css = '<link rel="stylesheet" href="client/skin/box.css">';
        $msg = AppMsg::getMsg('after_action_msg.ini', 'public', 'file_notfound');
        return $css . BoxMsg::factory('error', $msg);        
    }
}
?>