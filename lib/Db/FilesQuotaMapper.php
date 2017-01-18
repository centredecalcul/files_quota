<?php
/**
 * Created by PhpStorm.
 * User: benoit
 * Date: 20/12/16
 * Time: 14:30
 */

namespace OCA\Files_Quota\Db;

use OCP\IDb;
use OCP\AppFramework\Db\Mapper;



class FilesQuotaMapper extends Mapper {

	private $default_size = 5368709120;

	private $default_nb_files = 20000;

	public function __construct(IDb $db) {
		parent::__construct($db, 'files_quota', '\OCA\FilesQuota\lib\Db\Request');
	}

	public function findUserData($user)
	{
		$sql = 'SELECT user as `user`, user_files as `nb_files`, user_size  as `user_size`,
						  	quota_files as `quota_files`, quota_size as `quota_size` FROM `*PREFIX*files_quota` ' .
			'WHERE `user` like ?';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(1, $user, \PDO::PARAM_STR);
		if (!$stmt->execute()) {
			\OCP\Util::writeLog("Files Quota","Echec lors de l'exécution : (" . $stmt->errorCode() . ") " . $stmt->errorInfo(), \OCP\Util::ERROR);
		}
		$row = $stmt->fetch();
		$stmt->closeCursor();
		return $row;
	}

	public function addNewUserQuota($parameters)
	{
		$username = $parameters['username'];
		$nb_files = $parameters['nb_files'];
		$sum_files = $parameters['sum_files'];
		$sql = "INSERT INTO `*PREFIX*files_quota` (user, user_files, user_size, quota_files, quota_size) VALUES ('$username', '$nb_files',
 				'$sum_files', '$this->default_nb_files', '$this->default_size')";
		$stmt = $this->db->prepare($sql);
		if (!$stmt->execute()) {
			\OCP\Util::writeLog("Files Quota","Echec lors de l'exécution : (" . $stmt->errorCode() . ") " . $stmt->errorInfo(), \OCP\Util::ERROR);
		}
	}

	public function	updateUserData($parameters)
	{
		$file_size = $parameters['file_size'];
		$username = $parameters['username'];
		$sql = "UPDATE `*PREFIX*files_quota` set user_files = user_files + 1, user_size = user_size + " . $file_size . " WHERE user like '$username'";
		$stmt = $this->db->prepare($sql);
		if (!$stmt->execute()) {
			\OCP\Util::writeLog("Files Quota","Echec lors de l'exécution : (" . $stmt->errorCode() . ") " . $stmt->errorInfo(), \OCP\Util::ERROR);
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


}