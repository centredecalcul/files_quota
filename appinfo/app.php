<?php
//app.php
namespace OCA\Files_Quota\AppInfo;

use OCP\App;
use OCP\Util;


$app = new \OCA\Files_Quota\AppInfo\Application();
//register the configuration settings templates
$app->registerSettings();
//connect an hook to the preSetup
Util::connectHook('OC_Filesystem', 'preSetup', $app, 'setupWrapper');