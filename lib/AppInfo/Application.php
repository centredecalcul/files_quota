<?php
//application.php
namespace OCA\Files_Quota\AppInfo;

use OCA\Files_Quota\Wrapper\FilesQuotaWrapper;
use OCA\Files_Quota\Controller\SettingsController;
use \OC\Files\Storage\Home;
use OCP\App;
use \OC\User;


class Application extends \OCP\AppFramework\App {

	public function __construct(array $urlParams=array()){
		parent::__construct('files_quota', $urlParams);
		$container = $this->getContainer();

		/**
		 * Controllers
		 */
		$container->registerService('SettingsController', function($c) {
		$server = $c->getServer();
		return new \OCA\Files_Quota\Controller\SettingsController(
            $c->getAppName(),
            $server->getRequest(),
            $server->getL10N($c->getAppName()),
            $server->getConfig(),
            $c->query('FilesQuotaMapper'),
            $c->query('Logger')
            );
		});

		$container->registerService('FilesQuotaMapper', function($c) {
			return new \OCA\Files_Quota\Db\FilesQuotaMapper(
				$c->query('ServerContainer')->getDb(),
				$c->query('Logger')
			);
		});

		/**
		 * Core
		 */
		$container->registerService('UserSession', function ($c)
		{
			return $c->getServer()->getUserSession();
		});
		$container->registerService('Logger', function($c) {
			return $c->query('ServerContainer')->getLogger();
		});
        $container->registerService('L10N', function($c) {
            return $c->query('ServerContainer')->getL10N($c->query('AppName'));
        });
	}

    public function registerSettings() {
        // Register settings scripts
        \OCP\App::registerAdmin('files_quota', 'settings/settings-admin');
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
