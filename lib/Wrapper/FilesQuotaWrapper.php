<?php

namespace OCA\FilesQuota\Wrapper;

use OCP\IDb;
use OC\Files\Storage\Storage;

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
		if ($data instanceof ICacheEntry and isset($data['size'])) {
			return $data['size'];
		} else {
			return \OCP\Files\FileInfo::SPACE_NOT_COMPUTED;
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
				return $free;
			}
		}
	}

	/**
	 * see http://php.net/manual/en/function.fopen.php
	 *
	 * @param string $path
	 * @param string $mode
	 * @return resource
	 */
	public function fopen($path, $mode) {
		$source = $this->storage->fopen($path, $mode);
		$user = $this->storage->getUser()->getUID();
		//check first if the user exist in our DB
		if (!is_object($data = $this->db->findUserData($user)));
		{
			$this->exist = false;
			$nb_files = $this->db->getUploadedFilesNumber($user);
			$used = $this->quota - $this->free_space($path);
			$data = ['user' => $user, 'nb_files' => $nb_files, 'user_size' => $used,
				'quota_files' => $this->default_nb_files, 'quota_size' => $this->default_size];
		}
		// don't apply quota for part files
		if (!$this->isPartFile($path)) {
			$free = $this->free_space('');
			if ($source && $free >= 0 && $data['quota_files'] - $data['nb_files'] >= 0 && $mode !== 'r' && $mode !== 'rb') {
				// only apply quota for files, not metadata, trash or others
				if (strpos(ltrim($path, '/'), 'files/') === 0) {
					if ($this->exist)
					{
						$this->db->updateUserData(['username' => $user,
							'file_size' => $this->quota - $free]);
					}
					else
					{
						$this->db->addNewUserQuota(['username' => $user,
							'nb_files' => $data['nb_files'],
							'sum_files' => $this->quota - $free]);
					}
					return \OC\Files\Stream\Quota::wrap($source, $free);
				}
			}
		}
		return $source;
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
	/**
	 * @param \OCP\Files\Storage $sourceStorage
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @return bool
	 */
	public function copyFromStorage(\OCP\Files\Storage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		$free = $this->free_space('');
		if ($free < 0 or $this->getSize($sourceInternalPath, $sourceStorage) < $free) {
			return $this->storage->copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		} else {
			return false;
		}
	}
	/**
	 * @param \OCP\Files\Storage $sourceStorage
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @return bool
	 */
	public function moveFromStorage(\OCP\Files\Storage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		$free = $this->free_space('');
		if ($free < 0 or $this->getSize($sourceInternalPath, $sourceStorage) < $free) {
			return $this->storage->moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		} else {
			return false;
		}
	}
}