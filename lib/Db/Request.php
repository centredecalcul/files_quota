<?php
/**
 * Created by PhpStorm.
 * User: benoit
 * Date: 20/12/16
 * Time: 14:40
 */

namespace OCA\filesquota\lib\Db;


use OCP\AppFramework\Db\Entity;

class Request extends Entity implements \JsonSerializable {

	protected	$id;
	protected	$user;
	protected	$user_files;
	protected	$user_size;
	protected	$quota_files;
	protected	$quota_size;

	public function jsonSerialize() {
		return [
			'id' => $this->id,
			'user' => $this->user,
			'user_files' => $this->user_files,
			'user_size' => $this->user_size,
			'quota_files' => $this->quota_files,
			'quota_size' => $this->quota_size
		];
	}


}