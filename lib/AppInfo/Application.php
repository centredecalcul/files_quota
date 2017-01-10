<?php
//application.php
namespace OCA\FilesQuota\AppInfo;

use OCA\FilesQuota\Hooks\RootHooks;
use OCA\FilesQuota\Wrapper\FilesQuotaWrapper;
use \OCP\AppFramework\App;
use OCA\FilesQuota\Controller\SettingsController;



class Application extends App {

	public function __construct(array $urlParams=array()){
		parent::__construct('filesquota', $urlParams);

		$container = $this->getContainer();

		/**
		 * Controllers
		 */

		$container->registerService('RootHooks', function ($c){
			return new RootHooks(
				$c->query('ServerContainer')->getRootFolder(),
				$c->query('ServerContainer')->getUserSession(),
                $c->query('Logger'),
				$c->query('AppName'),
                $c->query('ServerContainer')->getDatabaseConnection()
				);
		});
//$rootFolder, $userSession,ILogger $logger, $appName,IDBConnection $myDb

//		$container->registerService('SettingsController', function($c) {
//			return new SettingsController(
//				$c->query('Request'),
//				$c->query('AppConfig'),
//				$c->query('L10N')
//			);
//		});
		/**
		 * Core
		 */
		$container->registerService('Logger', function($c) {
			return $c->query('ServerContainer')->getLogger();
		});
		$container->registerService('CoreConfig', function($c) {
			return $c->query('ServerContainer')->getConfig();
		});
		$container->registerService('L10N', function($c) {
			return $c->query('ServerContainer')->getL10N($c->query('AppName'));
		});


	}

	public function setupWrapper(){
		\OC\Files\Filesystem::addStorageWrapper(
			'oc_fquota',
			function ($mountPoint, $storage)
			{
				/**
				 * @var \OC\Files\Storage\Storage $storage
				 */
				if ($storage instanceof \OC\Files\Storage\Storage)
				{
					$l10n = $this->getContainer()->query('L10N');
					$logger = $this->getContainer()->query('Logger');
					$db = $this->getContainer('ServerContainer')->getServer()->getDatabaseConnection();
                    return new FilesQuotaWrapper([
                        'storage' => $storage,
                        'l10n' => $l10n,
                        'logger' => $logger,
                        'db' => $db
                    ]);
                } else {
                    return $storage;
                }
			},
            1
		);
	}
}
