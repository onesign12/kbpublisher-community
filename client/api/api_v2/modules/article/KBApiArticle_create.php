<?php


class KBApiArticleAction extends KBApiCommon
{
    
    var $eobj;
    
    
    // function __construct() {
    //
    //
    // }
    
    
    function getEntryObject($obj) {
        if(isset($this->eobj[$obj])) {
            return $this->eobj[$obj]; 
        }
        
        $this->eobj[$obj] = new $obj;
        return $this->eobj[$obj]; 
    }
    
    
    function validate($controller, $manager) {
        
        // $reg =& Registry::instance();
        // $auth = $reg->getEntry('auth');
        
        $auth = new AuthPriv;
        
        if(!$auth->getPrivId()) {
            KBApiError::error(5);
        }
        
        $this->checkPriv($auth);
        
        $this->validateInput();
    }
    
    
    function saveTags($tags, $manager) {
        
        $titles = $manager->tag_manager->parseTagString($tags);
        $manager->tag_manager->saveTag($titles);

        $tags = $manager->tag_manager->getTagArray($titles);
        $tags = RequestDataUtil::stripVars($tags, array(), true);

        $tags = $manager->getValuesArray($tags, 'id');
        
        return $tags;
    }
    
}


class KBApiArticle_create extends KBApiArticleAction
{
    
    
	
	function &execute($controller, $manager) {
        
        $entry_id = $this->save();

        $ra = $this->getResultAttributesFromAction($entry_id, 'success');
        $this->setRootAttributes($ra);

        $a = array();

        return $a;
	}

    
    function save() {
        
        $values =& $this->rp->vars; 
        
        $manager = $this->getEntryObject('KBEntryModel');
        $obj = $this->getEntryObject('KBEntry');
        
        if(!empty($values['tag'])) {
            $values['tag'] = $this->saveTags($values['tag'], $manager);
        }
        
        $obj->set($obj->get());
        $obj->populate($values, $manager);
        $obj->set('body_index', $manager->getIndexText($values['body']));
        
        $entry_id = $manager->save($obj);
        
        return $entry_id;
    }

    
    function checkPriv($auth) {
        $manager = $this->getEntryObject('KBEntryModel');
        $manager->checkPriv($auth, 'insert');
    }


    // function array_filter_recursive($array, $callback = '') {
    //     $func = function ($item) use (&$func, &$callback) {
    //         return is_array($item) ? array_filter($item, $func) : call_user_func($callback, $item);
    //      };
    //
    //      return array_map($func, $array);
    // }


    function validateInput() {

        $key_to_int = array(
            'category', 'related', 'attachment', 'entry_type',
            'role_read', 'role_write', 'active'
        );
        
        $this->rp->setIntKeys('qweqwe');
        
        $this->rp->toInt($key_to_int);
        $this->rp->stripVars();
        
        
        // echo '<pre>', print_r($this->rp->vars,1), '<pre>';
        
        $vars = ExtFunc::array_filter_recursive($this->rp->vars);
        // echo '<pre>', print_r($vars,1), '<pre>';
        // exit;
        
        $manager = $this->getEntryObject('KBEntryModel');
        
        if(!$manager->isCategoryExists($this->rp->vars['category'])) {
            KBApiError::error(26, 'There are missed categories in request');
        }

        
        $obj = $this->getEntryObject('KBEntry');
        $obj->validate($this->rp->vars, $manager);

        if($obj->errors) {
            KBApiError::error(26, KBApiError::parseObjErrorMsg($obj->errors));
        }
        
    }
    
}
?>