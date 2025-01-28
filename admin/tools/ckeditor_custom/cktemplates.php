<?php
require_once '../../config.inc.php';
require_once '../../config_more.inc.php';
require_once '../../common.inc.php';


$manager = new ArticleTemplateModel();
$manager->setEntryType('knowledgebase');
$rows = $manager->getArticleTemplateActiveList();

header("Content-type: text/javascript; charset=" . $conf['lang']['meta_charset']);

$tpl = new tplTemplatez('cktemplates.js.html');
$tpl->strip_vars = true;


$template_str = "{title: '%s', description: '%s', html: '%s'}";

// list records
foreach($rows as $row) {        
    
    $title = RequestDataUtil::stripVarsXml($row['title']);
    $description = RequestDataUtil::stripVarsXml($row['description']);
    
    //$row['body'] = nl2br($row['body']);
    $body = str_replace("\n", '', $row['body']);
    $body = str_replace("\r", '', $body);
    
    if ($row['is_widget']) {
        $body = sprintf('<div class="template_widget">%s</div>', $body);
    }
    
    $data[] = sprintf($template_str, $title, $description, $body);
}

$tpl->tplAssign('templates', implode(',', $data));

$tpl->tplAssign('encoding', $conf['lang']['meta_charset']);
$tpl->tplParse();
echo $tpl->tplPrint(1);
?>