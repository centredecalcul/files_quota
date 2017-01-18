<?php
//app.php
namespace OCA\Files_Quota\AppInfo;

use OCP\App;
use OCP\Util;

App::registerAdmin('files_quota', 'admin');


$app = new \OCA\Files_Quota\AppInfo\Application();
Util::connectHook('OC_Filesystem', 'preSetup', $app, 'setupWrapper');

