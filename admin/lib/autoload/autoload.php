<?php

require_once 'autoload/AppLoader.php';
AppLoader::register(['app']);

require_once 'core/base/BaseApp.php';
require_once 'core/base/BaseModel.php';
require_once 'core/app/AppModel.php';
require_once 'core/base/BaseView.php';
require_once 'core/app/AppView.php';
require_once 'core/app/AppMsg.php';

require_once 'eleontev/HTML/tplTemplatez.php';
require_once 'eleontev/URL/RequestData.php';
require_once 'eleontev/Assorted.inc.php';
require_once 'eleontev/Auth/AuthPriv.php';
// require_once 'eleontev/Auth/AuthRemote.php';
// require_once 'eleontev/Auth/AuthProvider.php';

?>