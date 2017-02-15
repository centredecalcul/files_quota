<?php

namespace OCA\Files_Quota\Wrapper;

use OCP\IDb;
use OC\Files\Storage\Storage;

use OC\Files\Filesystem;
use OC\Files\Storage\Wrapper\Wrapper;

class FilesQuotaWrapper extends Wrapper{

	private $default_size = 5368709120;

	private $default_nb_files = 20000;

	/**
	 * @var int $quota
	 * quota size in GB
	 */
	protected	$quota;

	/**
	 * @var string $sizeRoot
	 */
	protected	$sizeRoot;

	/**
	 * @var int $nbQuotaFiles
	 * quota number for files
	 */
	protected	$nbQuotaFiles;

	/**
	 * @var IDb $db
	 * for sql request
	 */
	protected	$db;

	private		$exist = true;

	public function __construct($parameters) {
		$this->storage = 	$parameters['storage'];
		$this->quota = 		$parameters['quota'];
		$this->db =			$parameters['db'];
		$this->sizeRoot = 	isset($parameters['root']) ? $parameters['root'] : '';
	}


	/**
	 * @return int quota value
	 */
	public function getQuota() {
		return $this->quota;
	}


	/**
	 * @param string $path
	 * @param \OC\Files\Storage\Storage $storage
	 */
	protected function getSize($path, $storage = null) {
		if (is_null($storage)) {
			$cache = $this->getCache();
		} else {
			$cache = $storage->getCache();
		}
		$data = $cache->get($path);
		if (isset($data['size'])) {
			return $data['size'];
		} else {
			return \OCP\Files\FileInfo::SPACE_NOT_COMPUTED;
		}
	}


	public function check_free_space($path)
	{
		if ($this->quota < 0) {
			return $this->storage->free_space($path);
		} else {
			$used = $this->getSize($this->sizeRoot);
			if ($used < 0) {
			return \OCP\Files\FileInfo::SPACE_NOT_COMPUTED;
			} else {
				$free = $this->storage->free_space($path);
				$quotaFree = max($this->quota - $used, 0);
			// if free space is known
				if ($free >= 0) {
				$free = min($free, $quotaFree);
				} else {
				$free = $quotaFree;
				}
				return $free;
			}
		}
	}

	/**
	 * Get free space as limited by the quota
	 *
	 * @param string $path
	 * @return int
	 */
	public function free_space($path) {
		if ($this->quota < 0) {
		return $this->storage->free_space($path);
		} else {
			$used = $this->getSize($this->sizeRoot);
			if ($used < 0) {
			return \OCP\Files\FileInfo::SPACE_NOT_COMPUTED;
			} else {
				$free = $this->storage->free_space($path);
				$quotaFree = max($this->quota - $used, 0);
				// if free space is known
				if ($free >= 0) {
				$free = min($free, $quotaFree);
				} else {
				$free = $quotaFree;
				}
			}
		}

		$user = $this->storage->getUser()->getUID();
		//check first if the user exist in our DB
		$path2 = Filesystem::normalizePath($this->storage->getLocalFile($path), true, true);
		$data = $this->db->findUserData($user);
		if (!isset($data['user']))
		{
		$this->exist = false;
			$nb_files = $this->db->getUploadedFilesNumber($user);
			$data = ['user' => $user, 'nb_files' => $nb_files, 'quota_files' => $this->default_nb_files];
		}
		if (!$this->isPartFile($path)) {
		$free = $this->check_free_space('');
		if ($free >= 0 && $data['quota_files'] - $data['nb_files'] > 0) {
			// only apply quota for files, not metadata, trash or others
				if (strpos(ltrim($path, '/'), 'files/') === 0) {
				if ($this->exist == true)
					{
					$this->db->updateUserData(['username' => $user,
						'file_size' => $this->quota - ($free)]);
					}
					else
					{
					$this->db->addNewUserQuota(['username' => $user,
						'nb_files' => $data['nb_files'] + 1,
						'sum_files' => $this->quota - $free]);
					}
					return $free;
				}
			}
		}
	return \OCP\Files\FileInfo::SPACE_NOT_COMPUTED;
	}

	/**
	 * Checks whether the given path is a part file
	 *
	 * @param string $path Path that may identify a .part file
	 * @return string File path without .part extension
	 * @note this is needed for reusing keys
	 */
	private function isPartFile($path) {
		$extension = pathinfo($path, PATHINFO_EXTENSION);
		return ($extension === 'part');
	}

	public function unlink($path) {
		$user = $this->storage->getUser()->getUID();
		$data = $this->db->findUserData($user);
		if (!isset($data['user']))
		{
		$this->exist = false;
		$nb_files = $this->db->getUploadedFilesNumber($user);
		$data = ['user' => $user, 'nb_files' => $nb_files, 'quota_files' => $this->default_nb_files];
		}
		if ($this->exist)
		{
			$this->db->suppressFile($user);
		}
		else
		{
			$this->db->addNewUserQuota(['username' => $user,
						'nb_files' => $data['nb_files']]);
		}
	}
}
