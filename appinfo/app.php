<?php
//app.php
namespace OCA\FilesQuota\AppInfo;

use OCP\Util;


$app = new \OCA\FilesQuota\AppInfo\Application();
Util::connectHook('OC_Filesystem', 'preSetup', $app, 'setupWrapper');

