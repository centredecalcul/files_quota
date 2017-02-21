<?php
/**
 * Created by PhpStorm.
 * User: benoit
 * Date: 20/12/16
 * Time: 14:30
 */

namespace OCA\Files_Quota\Db;

use OCP\IDb;
use OCP\ILogger;
use OCP\AppFramework\Db\Mapper;



class FilesQuotaMapper extends Mapper {

	private $default_size = 5368709120;

	private $default_nb_files = 20000;

	private $log;

	public function __construct(IDb $db, ILogger $log) {
		parent::__construct($db,'files_quota', '\OCA\FilesQuota\lib\Db\Request');
		$this->log = $log;
	}

	public function findUserData($user)
	{
		$sql = 'SELECT user as `user`, user_files as `nb_files`, quota_files as `quota_files` FROM `*PREFIX*files_quota` ' .
				'WHERE `user` like ?';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(1, $user, \PDO::PARAM_STR);
		if (!$stmt->execute()) {
			\OCP\Util::writeLog("Files Quota","Fail during the execution : (" . $stmt->errorCode() . ") " . $stmt->errorInfo(), \OCP\Util::ERROR);
		}
		$row = $stmt->fetch();
		$stmt->closeCursor();
		return $row;
	}

	public function addNewUserQuota($parameters)
	{
		$username = $parameters['username'];
		$nb_files = $parameters['nb_files'];
		$sql = "INSERT INTO `*PREFIX*files_quota` (user, user_files, quota_files) VALUES ('$username', '$nb_files', '$this->default_nb_files')";
		$stmt = $this->db->prepare($sql);
		if (!$stmt->execute()) {
			\OCP\Util::writeLog("Files Quota","Fail during the execution : (" . $stmt->errorCode() . ") " . $stmt->errorInfo(), \OCP\Util::ERROR);
		}
	}

	public function	updateUserData($parameters)
	{
		$username = $parameters['username'];
		$sql = "UPDATE `*PREFIX*files_quota` set user_files = (CASE WHEN user_files < 0 THEN 1 ELSE user_files + 1 END) WHERE user like '$username'";
		$stmt = $this->db->prepare($sql);
		if (!$stmt->execute()) {
			\OCP\Util::writeLog("Files Quota","Fail during the execution : (" . $stmt->errorCode() . ") " . $stmt->errorInfo(), \OCP\Util::ERROR);
		}
	}

	public function	getUploadedFilesNumber($username)
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

		$sql = "SELECT COUNT(storage) as `nb_files` FROM `*PREFIX*filecache` WHERE `path` LIKE 'files/%' AND `storage` = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(1, $numeric_id, \PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch();
		$stmt->closeCursor();
		return $row['nb_files'];
	}


	public function suppressFile($username)
	{
		$sql = "UPDATE `*PREFIX*files_quota` set user_files = (CASE WHEN user_files <= 0 THEN 0 ELSE user_files - 1 END) WHERE user like '$username'";
		$stmt = $this->db->prepare($sql);
		if (!$stmt->execute()) {
			\OCP\Util::writeLog("Files Quota","Fail during the execution : (" . $stmt->errorCode() . ") " . $stmt->errorInfo(), \OCP\Util::ERROR);
		}
	}

	public function getUserList()
	{
		$sql = "SELECT DISTINCT uid FROM `*PREFIX*users`";
		$stmt = $this->db->prepare($sql);
		if (!$stmt->execute()) {
			\OCP\Util::writeLog("Files Quota","Fail during the execution : (" . $stmt->errorCode() . ") " . $stmt->errorInfo(), \OCP\Util::ERROR);
			return null;
		}
		else
		{
			foreach ($stmt->fetchAll() as $line) {
    			$row[] = $line;
			}
			$stmt->closeCursor();
			return $row;
		}
	}

	public function setUserQuota($quota, $username)
	{
		$sql = "UPDATE `*PREFIX*files_quota` set quota_files = '$quota' WHERE user like '$username'";
		$stmt = $this->db->prepare($sql);
		if (!$stmt->execute()) {
			\OCP\Util::writeLog("Files Quota","Fail during the execution : (" . $stmt->errorCode() . ") " . $stmt->errorInfo(), \OCP\Util::ERROR);
			return 1;
		}
		return 0;
	}
}