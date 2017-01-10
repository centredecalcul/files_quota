<?php


namespace OCA\FilesQuota;

use OC\Files\Filesystem;
use OC\Files\Storage\Wrapper\Wrapper;
use OC\Files\View;
use OCP\IUserManager;


class Storage extends Wrapper {
	private		$mountPoint;
	private 	$rootFolder;
	private 	$userSession;


	function	__construct($parameters, $rootFolder = null, $userSession = null) {
		$this->mountPoint = $parameters['mountPoint'];
		$this->rootFolder = $rootFolder;
		$this->userSession = $userSession;
		parent::__construct($parameters);
	}

	public function	unlink($path)
	{
//		return this->doDelete($path, 'unlink');
	}


	private	function	doDelete($path, $method)
	{
	}
}

