<?php
//application.php
namespace OCA\FilesQuota\AppInfo;

use OCA\FilesQuota\Wrapper\FilesQuotaWrapper;
use OC\Files\Storage\Storage;
use \OCP\AppFramework\App;



class Application extends App {

	public function __construct(array $urlParams=array()){
		parent::__construct('filesquota', $urlParams);
		$container = $this->getContainer();
		/**
		 * Controllers
		 */
		$container->registerService('FilesQuotaMapper', function($c) {
			return new RuleMapper(
				$c->query('ServerContainer')->getDb()
			);
		});

		/**
		 * Core
		 */
	}

	/**
	 * Add wrapper for local storages
	 */
	public function setupWrapper(){
		\OC\Files\Filesystem::addStorageWrapper(
			'oc_fquota',
			function ($mountPoint, $storage) {
				/**
				 * @var \OC\Files\Storage\Storage $storage
				 */
				if ($storage instanceof \OC\Files\Storage\Home ||
					$storage->instanceOfStorage('\OC\Files\ObjectStore\HomeObjectStoreStorage'))
				{
					if (is_object($storage->getUser()))
					{
						$user = $storage->getUser()->getUID();
						$db = $this->getContainer()->query('ServerContainer')->getDb();
						$quota = \OC_Util::getUserQuota($user);
						if ($quota !== \OCP\Files\FileInfo::SPACE_UNLIMITED) {
							return new FilesQuotaWrapper([
								'storage' => $storage,
								'db' => $db,
								'quota' => $quota,
								'root' => 'files'
							]);
						}
					}
				}
				return $storage;
			});
	}
}
