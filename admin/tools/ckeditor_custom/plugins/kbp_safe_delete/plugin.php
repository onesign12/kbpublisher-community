<?php

if (!defined('IN_CKFINDER')) exit;

require_once CKFINDER_CONNECTOR_LIB_DIR . "/CommandHandler/XmlCommandHandlerBase.php";

class CKFinder_Connector_CommandHandler_SafeDelete extends CKFinder_Connector_CommandHandler_XmlCommandHandlerBase
{
    
    function buildXml() {
        if (empty($_POST['file'])) {
          $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_INVALID_REQUEST);
        }
        
        $this->checkConnector();
        $this->checkRequest();

        if (!$this->_currentFolder->checkAcl(CKFINDER_CONNECTOR_ACL_FILE_VIEW)) { // CKFINDER_CONNECTOR_ACL_FILE_DELETE needed
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_UNAUTHORIZED);
        }
        
        // file name
        $name = CKFinder_Connector_Utils_FileSystem::convertToFilesystemEncoding($_POST['file']['name']);
        
        // resource type
        $type = $_POST['file']['type'];
        
        // client path
        $path = CKFinder_Connector_Utils_FileSystem::convertToFilesystemEncoding($_POST['file']['folder']);
        
        
        $_config = & CKFinder_Connector_Core_Factory::getInstance('Core_Config');
        $_resourceTypeConfig = $_config->getResourceTypeConfig($type);
        
        if (is_null($_resourceTypeConfig) || !CKFinder_Connector_Utils_FileSystem::checkFileName($name) || preg_match(CKFINDER_REGEX_INVALID_PATH, $path)) {
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_INVALID_REQUEST);
        }

        if (!$_resourceTypeConfig->checkExtension($name, false)) {
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_INVALID_REQUEST);
        }
        
        if ($_resourceTypeConfig->checkIsHiddenPath($path)) {
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_INVALID_REQUEST);
        }
        
        $currentResourceTypeConfig = $this->_currentFolder->getResourceTypeConfig();
        if ($currentResourceTypeConfig->checkIsHiddenFile($name)) {
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_INVALID_REQUEST);
        }
        
        $_aclConfig = $_config->getAccessControlConfig();
        $aclMask = $_aclConfig->getComputedMask($type, $path);

        $isAuthorized = (($aclMask & CKFINDER_CONNECTOR_ACL_FILE_DELETE) == CKFINDER_CONNECTOR_ACL_FILE_DELETE);
        if (!$isAuthorized) { // should be uncommented
            //$this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_UNAUTHORIZED);
        }
        
        $filePath = $_resourceTypeConfig->getDirectory() . $path . $name;
        $oErrorsNode = new CKFinder_Connector_Utils_XmlNode('Errors');

        if (!file_exists($filePath) || !is_file($filePath) ) {
            $errorCode = CKFINDER_CONNECTOR_ERROR_FILE_NOT_FOUND;
            $this->appendErrorNode($oErrorsNode, $errorCode, $name, $type, $path);
            return;
        }
        
        
        $oDeleteFilesNode = new Ckfinder_Connector_Utils_XmlNode('SafeDelete');
        $this->_connectorNode->addChild($oDeleteFilesNode);
        
        // checking
        $path_to_search = $_resourceTypeConfig->getUrl() . $path . $name;
        $path_to_search = str_replace('//', '/', $path_to_search);
        
        $entries = CKFinderPluginCommon::getImageUsage($path_to_search);
        
        if (empty($entries)) {
            if (!CKFinder_Connector_Utils_FileSystem::unlink($filePath)) {
                $errorCode = CKFINDER_CONNECTOR_ERROR_ACCESS_DENIED;
                $this->appendErrorNode($oErrorsNode, $errorCode, $name, $type, $path);
                return;
              
            } else {
                $thumbPath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getThumbsServerPath(), $name);
                @unlink($thumbPath);
            }
       
            $oDeleteFilesNode->addAttribute('deleted', 1);
            
        } else {
            $oDeleteFilesNode->addAttribute('deleted', 0);
            
            $message = 'This file could not be deleted!<br /> It is being used in:<br />%s';
            $entry_str = '<b>%s</b>: %s<br />';
            $details = '';
            
            $list = CKFinderPluginCommon::getImageUsageValidationList();
            foreach ($entries as $k => $type) {
                $details .= sprintf($entry_str, $list[$k]['name'], implode(', ', array_keys($type)));
            }
            
            $message = sprintf($message, $details);
            $oDeleteFilesNode->addAttribute('message', $message);
        }
    }
 
 
    function onBeforeExecuteCommand(&$command) {
        
        if ($command == 'SafeDelete') {
            $this->sendResponse();
            return false;
        }

        return true;
    }
}
 
$CommandHandler_SafeDelete = new CKFinder_Connector_CommandHandler_SafeDelete();
$config['Hooks']['BeforeExecuteCommand'][] = array($CommandHandler_SafeDelete, 'onBeforeExecuteCommand');