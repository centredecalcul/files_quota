<?php
/**
 * Created by PhpStorm.
 * User: benoit
 * Date: 20/12/16
 * Time: 14:30
 */

namespace OCA\filesquota\lib\Db;


class RequestMapper extends Mapper {
	private $default_size = 5368709120;

	private $default_nb_files = 20000;

	public function __construct(IDb $db) {
		parent::__construct($db, 'files_quota', '\OCA\FilesQuota\lib\Db\Request');
	}


	public function	find($user)
	{
		$sql = 'SELECT * FROM `*PREFIX*files_quota` ' .
			'WHERE `user` like ?';
		return $this->findEntity($sql, array($user));
	}

	public function getNumericID($user)
	{
		$path = "home::" . $user . "%";
		$sql = 'SELECT numeric_id as `storage` FROM `*PREFIX*storages` ' .
			'WHERE `id` like ?';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(1, $path, \PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetch();
		$stmt->closeCursor();
		return $row['storage'];
	}


	public function getStorageDatas($numeric_id)
	{
		$sql = 'SELECT SUM(size) as `sum_size`, COUNT(storage) as `nb_files` ' .
			' FROM `*PREFIX*filecache` WHERE `storage` = ? AND `path` like `files/%`';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(1, $numeric_id, \PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch();
		$stmt->closeCursor();
		return $row;
	}


}