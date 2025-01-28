<?php
class KBApiRequest_articles extends KBApiRequest
{
    
    var $call = 'articles';
 
 
    function getById($id) {
        
        $call = array();
        $call['id'] = $id;
        
        $responce = $this->request($call);
    }


    function getList($category_id, $limit = 10, $page = 1) {
        
        $call = array();
        $call['cid'] = $category_id;
        $call['limit'] = $limit;
        $call['page'] = $page;
        
        $responce = $this->request($call);
    }
}


?>