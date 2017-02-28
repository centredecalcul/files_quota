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
use	OCP\IConfig;




class FilesQuotaMapper extends Mapper {

	public $DEFAULT_QUOTA_FILES = -2;

	public $UNLIMITED_QUOTA_FILES = -1;

	private $log;

	private $conf;

	public function __construct(IDb $db, ILogger $log, IConfig $config) {
		parent::__construct($db,'files_quota', '\OCA\FilesQuota\lib\Db\Request');
		$this->log = $log;
		$this->conf = $config;
		$value = $this->conf->getAppValue("files_quota", "quotaDefault");
		if ($value === "")
		{
			$this->conf->setAppValue("files_quota", "quotaDefault", 20000);
		}
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
		//-2 is the default quota
		$sql = "INSERT INTO `*PREFIX*files_quota` (user, user_files, quota_files) VALUES ('$username', '$nb_files', '$this->DEFAULT_QUOTA_FILES')";
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
		$sql = "SELECT DISTINCT uid, quota_files FROM `*PREFIX*users` left join `*PREFIX*files_quota` on `uid` = `user`";
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

	public function getDefaultQuota()
	{
		$return = $this->conf->getAppValue("files_quota", "defaultQuota");
	
		if ($return === "")
		{
			$this->conf->setAppValue("files_quota", "defaultQuota", 20000);
			return 20000;
		}
		return $this->conf->getAppValue("files_quota", "defaultQuota");
	}

	public function getAllUsersFiles()
	{
		$sql = "SELECT DISTINCT storage, COUNT(storage) as `nb_files`, id FROM `*PREFIX*filecache` join `*PREFIX*storages` on `storage` = `numeric_id` WHERE `path` LIKE 'files/%' GROUP BY storage";
		$stmt = $this->db->prepare($sql);
		if (!$stmt->execute()) {
			\OCP\Util::writeLog("Files Quota","Fail during the execution : (" . $stmt->errorCode() . ") " . $stmt->errorInfo(), \OCP\Util::ERROR);
			return null;
		}
		else
		{
			foreach ($stmt->fetchAll() as $line)
			{
				$row[] = $line;
			}
			$stmt->closeCursor();
			return $row;
		}		
	}

	public function updateNewDatas($data)
	{

		$sql = "SELECT * FROM `*PREFIX*files_quota`;";
		$stmt = $this->db->prepare($sql);
		if (!$stmt->execute()) {
			\OCP\Util::writeLog("Files Quota","Fail during the execution : (" . $stmt->errorCode() . ") " . $stmt->errorInfo(), \OCP\Util::ERROR);
			return null;
		}
		foreach ($stmt->fetchAll() as $line)
		{
			foreach ($data as $row)
			{	
				if ($row['id'] === $line['user'])
				{
					$row['quota'] = $line['quota_files'];
				}
			}
		}
		foreach ($data as $row)
		{
			$id = $row['id'];
			$nb_files = $row['nb_files'];
			$quota = $row['quota'];
			$sql = "INSERT INTO `*PREFIX*files_quota` (user, user_files, quota_files) VALUES
			('$id', '$nb_files', '$quota') ON DUPLICATE KEY UPDATE user_files = VALUES(`user_files`), quota_files = VALUES(`quota_files`)";
			$stmt = $this->db->prepare($sql);
			if (!$stmt->execute()) {
				\OCP\Util::writeLog("Files Quota","Fail during the execution : (" . $stmt->errorCode() . ") " . $stmt->errorInfo(), \OCP\Util::ERROR);
				return null;
			}			
		}
		return 0;
	}
}