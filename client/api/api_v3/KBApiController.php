<?php
class KBApiController
{

    var $host;
    var $call;
    var $method;
    var $request_method = 'get';
    var $entry_id;
    var $category_id;

    var $request_map = array(
        'get'    => 'get',
        'add'    => 'post',
        'create' => 'post',
        'update' => 'put',
        'delete' => 'delete'
    );

    var $call_map = array(
        'articles'  => 'article',
        'files'     => 'file',
        'news'      => 'news',
        'search'    => 'search',
        // 'users'     => 'user',
        // 'comments'  => 'comment',
        'articleCategories' => 'article_category',
        'fileCategories' => 'file_category'
        );


    function __construct() {

        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');

        $this->debug = $conf['debug_info'];
        $this->encoding = $conf['lang']['meta_charset'];
        $this->host = $conf['api_path'];

        $this->cc = &$reg->getEntry('controller');

        // http host
        $this->baseUrl = str_replace($conf['client_home_dir'], '', $this->cc->kb_path);
        if($conf['client_home_dir'] == '/') {
            $this->baseUrl = $this->cc->kb_path;
        }
    }


    function setDirVars(&$settings) {

        $this->api_dir = API_DIR;

        $this->kb_path   = $this->cc->kb_path;
        $this->link_path = $this->cc->link_path;
        $this->setting   = &$settings;

        $this->kb_dir      = $this->cc->kb_dir;
        $this->client_path = $this->cc->client_path;

        $this->common_dir  = $this->cc->common_dir;
        $this->working_dir = $this->cc->working_dir;
    }


    function setUrlVars() {

        $this->rq = new RequestData($_GET);
        $this->rp = new RequestData($_POST);

        $call = explode('.', $this->getRequestVar('call'));
        $this->call = (!empty($call[0])) ? urldecode($call[0]) : NULL;
        $this->method = $this->getRequestVar('method');
        $this->action = $this->getRequestVar('action');

        $this->request_method = strtolower($_SERVER['REQUEST_METHOD']);
        if(!empty($call[1])) {
            $this->request_method = urldecode($call[1]);
        }

        $this->entry_id = (int) $this->getRequestVar('id');
        $this->category_id = (int) $this->getRequestVar('cid');
        
        
        // map search        
        // to be able to call like this GET call=articles&method=search ... 
        // it worked in API v1, KBP v5.5.1 and below
        if($this->method == 'search') {
            $old_call = $this->call_map[$this->call];
            $this->method = NULL;
            $this->call = 'search';
            $this->rq->vars['in'] = $old_call;
        }
    }


    static function getRequestVar($var) {
        return (isset($_GET[$var])) ? urlencode(urldecode($_GET[$var])) : NULL;
    }


    /*
        function getRequestMethod() {

            $request_method = strtolower($_SERVER['REQUEST_METHOD']);
            $data = array();

            switch ($request_method) {
                case 'get':
                    $data = $_GET;
                    break;

                case 'post':
                    $data = $_POST;
                    break;

                case 'put':
                    // basically, we read a string from PHP's special input location,
                    // and then parse it out into an array via parse_str... per the PHP docs:
                    // Parses str  as if it were the query string passed via a URL and sets
                    // variables in the current scope.
                    parse_str(file_get_contents('php://input'), $put_vars);
                    $data = $put_vars;
                    break;
            }
        }
    */

}

?>