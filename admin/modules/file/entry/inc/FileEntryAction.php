<?php

class FileEntryAction extends AppAction
{

    function sendFile($obj, $manager, $controller, $attachment) {
        
        $data = $manager->getById($this->rq->id);
        
        if(!$manager->getFileDir($data)) { // missing
            $view = $this->fileMissing($obj, $manager, $controller);
            return $view;
            
        } else {
            
            $manager->sendFileDownload($data, $attachment);
            exit;
        }
    }


    function fileText($obj, $manager, $controller) {
        
        if(isset($this->rp->submit)) {

            $this->rp->stripVars();
            $manager->updateFileText($this->rp->filetext, $this->rq->id);

            $return = $controller->getCurrentLink();
            $controller->setCustomPageToReturn($return, false);
            $controller->go();

        } else {

            $data = $manager->getById($this->rq->id);
            $this->rp->stripVarsValues($data);
            $obj->set($data);

            $obj->text = $manager->getFileText($this->rq->id);
            $this->rp->stripVarsValues($obj->text);
        }
        
        $view = new FileEntryView_text;    
        $view = $view->execute($obj, $manager);

        return $view;
    }
    
    
    function fileMissing($obj, $manager, $controller) {
    
        $controller->removeMoreParams('show_msg2');
        
        $data = $manager->getById($this->rq->id);
        $this->rp->stripVarsValues($data);
        $obj->set($data);
    
        $view = new FileEntryView_delete;    
        $view = $view->execute($obj, $manager);

        return $view;
    }

}

?>