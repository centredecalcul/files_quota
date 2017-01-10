<?php
namespace OCA\FilesQuota\Hooks;

use OCP\Files\IRootFolder;
use OCP\IUser;
use OC\Files\Filesystem;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\IUserSession;

class RootHooks {

	private $rootFolder;
	private $userSession;
	private $logger;
	private $appName;
	private $db;
	private	$view;
	protected $currentFilesystemUser;
	private	$TOO_MUCH_FILES = false;
	private $nb_files;

	private $default_size = 5368709120;

	private $default_nb_files = 20000;

	/**
	 * RootHooks constructor.
	 *
	 * @param $rootFolder
	 * @param $userSession
	 * @param ILogger $logger
	 * @param $appName
	 * @param IDBConnection $myDb
	 */
	public function __construct(IRootFolder $rootFolder, IUserSession $userSession, ILogger $logger, $appName, IDBConnection $myDb) {
		$this->rootFolder = $rootFolder;
		$this->userSession = $userSession;
		$this->logger = $logger;
		$this->appName = $appName;
		$this->db = $myDb;
	}

	public function register() {
		/**
		 * @param \OCP\Files\Node $node
		 */
		$callback = function (\OCP\Files\Node $node) {

			$owner = \OC::$server->getUserManager()->get($this->userSession->getUser()->getUID());
			$this->logger->warning($owner->getUID());
			$this->logger->warning($node->getPath());
			$this->initFilesystemForUser($owner);
			$this->view = Filesystem::getView();
			if (!is_object($this->view)) {
				$this->logger->error('Can\'t init filesystem view.');
				throw new \RuntimeException();
			}
			// your code that executes before $node is writed
			$this->log("In PreWrite Hooks " . $this->userSession->getUser()->getUID());
			$username = $this->userSession->getUser()->getUID();
			if (isset($username)) {
				try {
					$this->logger->warning("TEST JE SUIS DANS DBCHECK APRES LE TRY");
					$data = null;
					$arg = null;
					$sql = 'SELECT user_files as `user_files`, user_size  as `user_size`,
						  	quota_files as `quota_files`, quota_size as `quota_size` FROM `*PREFIX*files_quota` ' .
							'WHERE `user` like ?';
					$stmt = $this->db->prepare($sql);
					$stmt->bindParam(1, $username, \PDO::PARAM_STR);
					$stmt->execute();
					$row = $stmt->fetch();
					$stmt->closeCursor();
					if (!isset($row['user_files'])) {
						$this->logger->warning("MA DATABASE EST VIDE");

						$arg = 'no_user_in_db';
						$data = $this->get_data($username, $arg);
						$this->logger->warning("valeur de nb_files = " . $data['nb_files']);
						if ($data['nb_files'] + 1 >=  9 /*$this->default_nb_files*/) {
							$this->logger->warning("NB_FILES TROP IMPORTANT");
							$this->TOO_MUCH_FILES = true;
						}
					}
					else
					{
						$data = $row;
						$this->logger->warning("MA DATABASE CONTIENT LES INFOS");
						$this->logger->warning("valeur de nb_files = " . $data['user_files']);
						if ($data['user_files'] + 1 >=  9 /*$this->default_nb_files*/) {
							$this->TOO_MUCH_FILES = true;
							$this->view->unlink($this->view->getPath($node->getId()));
						}
					}
				} catch (\Exception $e) {
					$message = implode(' ', [__CLASS__, __METHOD__, $e->getMessage() . "prout"]);
					$this->logger->warning($message);
				}
			}
		};
		$this->rootFolder->listen('\OC\Files', 'preWrite', $callback);

		$callback = function (\OCP\Files\Node $node) {
			\OC_Hook::emit('\OCP\Files', 'preDelete', ['path' => $this->view->getPath($node->getId())]);

			$this->logger->warning("INSIDE POSTWRITE");
			$username = $this->userSession->getUser()->getUID();
			if ($this->TOO_MUCH_FILES)
			{
				$this->view->unlink($this->view->getPath($node->getId()));
			}
			else
			{
				$file_size = $node->getSize();
				$sql = "UPDATE `*PREFIX*files_quota` set (user_files = user_files + 1, user_size = user_size + '$file_size' WHERE user = $username" ;
				$stmt = $this->db->prepare($sql);
				if (!$stmt->execute()) {
					$this->logger("Echec lors de l'exécution : (" . $stmt->errno . ") " . $stmt->error);
				}
			}
			\OC_Hook::emit('\OC\Files', 'postDelete', ['path' => $this->view->getPath($node->getId())]);

		};
		$this->rootFolder->listen('\OC\Files', 'postCreate', $callback);
	}


	public function log($message) {
		$this->logger->error($message, array('app' => $this->appName));
	}

	/**
	 * Take usefull datas in file_cache database and fill the application database
	 * @param string $username
	 * @return data | data['nb_files'],['sum_size']
	 */
	private function get_data($username, $arg)
	{
		$path = "home::" . $username . "%";
		$sql = 'SELECT numeric_id as `storage` FROM `*PREFIX*storages` ' .
			'WHERE `id` like ?';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(1, $path, \PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetch();
		$stmt->closeCursor();
		$numeric_id = $row['storage'];

		$sql = "SELECT SUM(size) as `sum_size`, COUNT(storage) as `nb_files` FROM `*PREFIX*filecache` WHERE `path` LIKE 'files/%' AND `storage` = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(1, $numeric_id, \PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch();
		$stmt->closeCursor();
		$data = $row;
		if ('no_user_in_db' == $arg) {
			$this->logger->warning("JE SUIS DANS LE IF STRCMP");
			if ($row['nb_files'] >= $this->default_nb_files)
				$nb_files = $row['nb_files'];
			else
				$nb_files = $row['nb_files'] + 1;
			$sum_files = $row['sum_size'];
			$sql = "INSERT INTO `*PREFIX*files_quota` (user, user_files, user_size, quota_files, quota_size) VALUES ('$username', '$nb_files', '$sum_files', '$this->default_nb_files', '$this->default_size')" ;
			$stmt = $this->db->prepare($sql);
			if (!$stmt->execute()) {
				$this->logger("Echec lors de l'exécution : (" . $stmt->errno . ") " . $stmt->error);
			}
		}
		return $data;
	}

	/**
	 * @param IUser $user
	 */
	protected function initFilesystemForUser(IUser $user) {
		if ($this->currentFilesystemUser !== $user->getUID()) {
			if ($this->currentFilesystemUser !== '') {
				$this->tearDownFilesystem();
			}
			Filesystem::init($user->getUID(), '/' . $user->getUID() . '/files');
			$this->userSession->setUser($user);
			$this->currentFilesystemUser = $user->getUID();
			Filesystem::initMountPoints($user->getUID());
		}
	}

	protected function tearDownFilesystem(){
		$this->userSession->setUser(null);
		\OC_Util::tearDownFS();
	}

}


