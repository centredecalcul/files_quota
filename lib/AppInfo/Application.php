<?php
//application.php
namespace OCA\Files_Quota\AppInfo;

use OCA\Files_Quota\Wrapper\FilesQuotaWrapper;
use \OC\Files\Storage\Home;
use \OCP\AppFramework\App;
use \OC\User;



class Application extends App {

	public function __construct(array $urlParams=array()){
		parent::__construct('filesquota', $urlParams);
		$container = $this->getContainer();
		/**
		 * Controllers
		 */
		$container->registerService('FilesQuotaMapper', function($c) {
			return new \OCA\Files_Quota\Db\FilesQuotaMapper(
				$c->query('ServerContainer')->getDb()
			);
		});

		/**
		 * Core
		 */
		$container->registerService('Logger', function($c) {
			return $c->query('ServerContainer')->getLogger();
		});

		$container->registerService('UserSession', function ($c)
		{
			return $c->getServer()->getUserSession();
		});
	}

	/**
	 * Add wrapper for local storages
	 */
	public function setupWrapper(){
		\OC\Files\Filesystem::addStorageWrapper(
			'oc_fquota',
			function ($mountPoint, $storage) {
				$userSession = $this->getContainer()->query('UserSession');
				$logger = $this->getContainer()->query('Logger');
				/**
				 * @var \OC\Files\Storage\Storage $storage
				 */
				if ($storage->instanceOfStorage('\OC\Files\Storage\Storage'))
				{
					$logger->error("IN INSTANCEOFSTORAGE");
					$user = $userSession->getUser()->getUID();
					$db = $this->getContainer()->query('FilesQuotaMapper');
					$quota = \OC_Util::getUserQuota($user);
					if ($quota !== \OCP\Files\FileInfo::SPACE_UNLIMITED) {
						return new FilesQuotaWrapper([
								'storage' => $storage,
								'db' => $db,
								'quota' => $quota,
								'root' => 'files',
								'logger' => $logger
							]);
						}
				}
				return $storage;
			});
	}
	}
