<?php


namespace OCA\Files_Quota\AppInfo;

$application = new Application();

$application->registerRoutes($this, [
	'routes' => [
	['name' => 'settings/settings-admin#set_default_quota', 'url' => '/default-quota', 'verb' => 'POST'],
	]

]);