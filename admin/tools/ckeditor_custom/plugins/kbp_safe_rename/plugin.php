<?php

if (!defined('IN_CKFINDER')) exit;

require_once CKFINDER_CONNECTOR_LIB_DIR . "/CommandHandler/XmlCommandHandlerBase.php";

class CKFinder_Connector_CommandHandler_SafeRename extends CKFinder_Connector_CommandHandler_XmlCommandHandlerBase
{    
    
    function buildXml() {
        if (empty($_POST['file'])) {
          $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_INVALID_REQUEST);
        }
        
        $this->checkConnector();
        $this->checkRequest();

        if (!$this->_currentFolder->checkAcl(CKFINDER_CONNECTOR_ACL_FILE_VIEW)) { // CKFINDER_CONNECTOR_ACL_FILE_RENAME needed
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_UNAUTHORIZED);
        }
        
        // file name
        $name = CKFinder_Connector_Utils_FileSystem::convertToFilesystemEncoding($_POST['file']['name']);
        $newName = CKFinder_Connector_Utils_FileSystem::convertToFilesystemEncoding($_POST['file']['newName']);
        
        // resource type
        $type = $_POST['file']['type'];
        
        // client path
        $path = CKFinder_Connector_Utils_FileSystem::convertToFilesystemEncoding($_POST['file']['folder']);
        
        
        $_config = & CKFinder_Connector_Core_Factory::getInstance('Core_Config');
        $resourceTypeInfo = $this->_currentFolder->getResourceTypeConfig();
        
        if (!$resourceTypeInfo->checkExtension($name, false)) {
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_INVALID_REQUEST);
        }
        
        if (!$resourceTypeInfo->checkExtension($newName)) {
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_INVALID_EXTENSION);
        }
        
        if (!CKFinder_Connector_Utils_FileSystem::checkFileName($name) || $resourceTypeInfo->checkIsHiddenFile($name)) {
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_INVALID_REQUEST);
        }

        if (!CKFinder_Connector_Utils_FileSystem::checkFileName($newName) || $resourceTypeInfo->checkIsHiddenFile($newName)) {
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_INVALID_NAME);
        }
        
        if ($_config->forceAscii()) {
            $newName = CKFinder_Connector_Utils_FileSystem::convertToAscii($newName);
        }
        
        
        $filePath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getServerPath(), $name);
        $newFilePath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getServerPath(), $newName);
        
        $oRenamedFileNode = new Ckfinder_Connector_Utils_XmlNode('SafeRename');
        $this->_connectorNode->addChild($oRenamedFileNode);
        
        // checking
        $path_to_search = $resourceTypeInfo->getUrl() . $path . $name;
        $path_to_search = str_replace('//', '/', $path_to_search);
        
        $entries = CKFinderPluginCommon::getImageUsage($path_to_search);
        
        if (empty($entries)) {
            $bMoved = false;

            if (!file_exists($filePath)) {
                $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_FILE_NOT_FOUND);
            }
    
            if (!is_writable(dirname($newFilePath))) {
                $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_ACCESS_DENIED);
            }
    
            if (!is_writable($filePath)) {
                $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_ACCESS_DENIED);
            }
    
            if (file_exists($newFilePath)) {
                $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_ALREADY_EXIST);
            }
    
            $bMoved = @rename($filePath, $newFilePath);
    
            if (!$bMoved) {
                $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_UNKNOWN, "File " . CKFinder_Connector_Utils_FileSystem::convertToConnectorEncoding($fileName) . "has not been renamed");
                
            } else {
                $oRenamedFileNode->addAttribute('renamed', 1);
    
                $thumbPath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getThumbsServerPath(), $name);
                CKFinder_Connector_Utils_FileSystem::unlink($thumbPath);
            }
            
        } else {
            $oRenamedFileNode->addAttribute('renamed', 0);
            
            $message = 'This file could not be renamed! <br />It is being used in:<br />%s';
            $entry_str = '<b>%s</b>: %s<br />';
            $details = '';
            
            $list = CKFinderPluginCommon::getImageUsageValidationList();
            foreach ($entries as $k => $type) {
                $details .= sprintf($entry_str, $list[$k]['name'], implode(', ', array_keys($type)));
            }
            
            $message = sprintf($message, $details);
            $oRenamedFileNode->addAttribute('message', $message);
        }
    }
 
 
    function onBeforeExecuteCommand(&$command) {
        
        if ($command == 'SafeRename') {
            $this->sendResponse();
            return false;
        }

        return true;
    }
}
 
$CommandHandler_SafeRename = new CKFinder_Connector_CommandHandler_SafeRename();
$config['Hooks']['BeforeExecuteCommand'][] = array($CommandHandler_SafeRename, 'onBeforeExecuteCommand');