<?php

namespace OCA\FilesQuota;

use \OCA\FilesQuota\AppInfo\Application;

$app = new Application();
$container = $app->getContainer();
$response = $container->query('\OCA\FilesQuota\Controller\SettingsController')->index();
return $response->render();