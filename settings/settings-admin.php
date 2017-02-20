<?php

namespace OCA\Files_Quota\Settings;

use OCA\Files_Quota\AppInfo\Application;
use OCA\Files_Quota\Controller\SettingsController;

$app = new Application();
$container = $app->getContainer();
$response = $container->query('\OCA\Files_Quota\Controller\SettingsController')->index();
return $response->render();