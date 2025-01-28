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

require_once 'PEAR/HTTP/Request2.php';


class KBHelpView_updates extends AppView
{
    
    var $tmpl = 'check_updates.html';
    
    function execute(&$obj, &$manager) {

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
    
        $ret = $this->sendRequest();
        $tpl->tplAssign('response', $ret);
        $tpl->tplAssign('current_version', $this->conf['product_version']);
        $tpl->tplAssign('back_link', $this->controller->getLink('help', 'help'));
    
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function sendRequest() {
        
        $page = 'https://www.kbpublisher.com/rpc/request.php';
        
        $req = new HTTP_Request2($page);
        $req->setMethod(HTTP_Request2::METHOD_POST);
        
        $req->setConfig(array(
            'follow_redirects' =>  true,
            'ssl_verify_peer'  => false,
            'ssl_verify_host'  => false
        ));
        
        $req->addPostParameter('a', 'check_update');
        $req->addPostParameter('v', $this->conf['product_version']);
        $req->addPostParameter('http_host', $_SERVER['HTTP_HOST']);
        $req->addPostParameter('remote_addr', $_SERVER['REMOTE_ADDR']);
        $req->addPostParameter('dir', getcwd());
        
        try {
            $response = $req->send();
            
            if ($response->getStatus() == 200) {
                return $response->getBody();
                
            } else {
                $msg = AppMsg::getMsgs();
                return 'Service Unavailable';
            }
            
        } catch (HTTP_Request2_Exception $e) {
            return 'Error: ' . $e->getMessage();
        }

        return false;
    }
}
?>
