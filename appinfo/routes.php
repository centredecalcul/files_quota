<?php


namespace OCA\Files_Quota\AppInfo;

$application = new Application();

$application->registerRoutes($this, [
	'routes' => [
	['name' => 'settings#setDefaultQuota', 'url' => '/setDefaultQuota', 'verb' => 'POST'],
	['name' => 'settings#setUserQuota', 'url' => '/setUserQuota', 'verb' => 'POST'],
	]

]);