<?php

/* GENERAL */
$conf['db_host']      = "{db_host}";
$conf['db_base']      = "{db_base}";
$conf['db_user']      = "{db_user}";
$conf['db_pass']      = "{db_pass}";
$conf['db_driver']    = "{db_driver}";             // no other were tested 
$conf['tbl_pref']     = '{tbl_pref}';  
  
$conf['session_name'] = '{session_name}';        // session name
$conf['allow_setup']  = 0;                 // allow or not setup wizard, redirect to setup wizard if 1


/* PATHS */
$_SERVER['DOCUMENT_ROOT'] = '{document_root}';
$_SERVER['HTTP_HOST']     = '{http_host}';

// admin path
$conf['admin_home_dir']   = '{admin_home_dir}';         // path to admin dir, relative to DOCUMENT_ROOT

// client path
$conf['client_home_dir']  = '{client_home_dir}';               // path to kb dir, relative to DOCUMENT_ROOT

// other path
$conf['site_address']     = $_SERVER['HTTP_HOST'];
$conf['cache_dir']        = '{cache_dir}';        // full path to cache dir, it should be writeable


/* SECURITY */
$conf['auth_check_ip']    = {auth_check_ip};        // on every request IP will be checked with saved one on login
$conf['ssl_admin']        = {ssl_admin};        // 0 = OFF, 1 = ON (default port 443) or use concrete port number
$conf['ssl_client']       = {ssl_client};        // 0 = OFF, 1 = ON (default port 443) or use concrete port number
$conf['auth_remote']      = {auth_remote};        // remote auth 0 - disabled, 1 - enabled 

/* DEBUG */                           // some configs 1 = yes, 0 = no
$conf['debug_info']       = 0;        // display $_GET, $_SESSION, $_POST and also set displaying all errors
$conf['debug_speed']      = 0;        // display page generating speed
$conf['debug_db_error']   = 0;        // 0 - just a notice about db error, 1 - real error short format, 2 - full format 
$conf['debug_db_sql']     = 0;        // display all sent sql (adodb format)
$conf['debug_sphinx_sql'] = 0;        // display all sent sphinx sql (adodb format)
$conf['config_local']     = 0;        // 0 - disabled, or set full path to config file


/* OTHER */
$conf['php_dir']          = '/usr/local/bin/php'; // probably could be required (upd: is not used in cron job)
$conf['use_ob_gzhandler'] = {use_ob_gzhandler};                    // on some instalations(php version) it should be set to 0
$conf['timezone']         = '{timezone}';                   // if empty php.ini setting (date.timezone) will be used 
                                                  // if you need special one or have php errors set it here
                                                  // available timezones see at http://www.php.net/manual/en/timezones.php

$conf['db_names']         = '{db_names}';        // if set to UTF8, it will force to send sql "SET NAMES 'UTF8'"

$conf['web_service_url'] = '45.33.115.56/web_service/api.php';
?>