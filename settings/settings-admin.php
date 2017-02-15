<?php

namespace OCA\Files_Quota\Settings;

// $tmpl = new \OCP\Template('files_quota', 'settings-admin');
// return $tmpl->fetchPage();


use OCA\Files_Quota\AppInfo\Application;
use OCA\Files_Quota\Controller\SettingsController;

$app = new Application();
$container = $app->getContainer();
$response = $container->query('\OCA\Files_Quota\Controller\SettingsController')->index();
return $response->render();