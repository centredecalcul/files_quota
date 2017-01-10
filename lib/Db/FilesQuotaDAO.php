<?php

namespace OCA\FilesQuota\Db;

use OCP\IDBConnection;

class FilesQuotaDAO {

	private $db;

	public function __construct(IDBConnection $db) {
		$this->db = $db;
	}

	public function count_nb_files($id) {

		$path = "home::" . $id . "%";
		$sql = 'SELECT numeric_id as `storage` FROM `*PREFIX*storages` ' .
			'WHERE `id` like ?';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(1, $path, \PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetch();
		$stmt->closeCursor();
		$numeric_id = $row['storage'];

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