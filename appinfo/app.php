<?php
//app.php
namespace OCA\FilesQuota\AppInfo;

\OC_App::registerAdmin('filesquota', 'admin');
$appli = new Application();
$appli->getContainer()->query('RootHooks')->register();
\OC_Hook::connect('OC_Filesystem', 'preSetup', $appli, 'setupWrapper');