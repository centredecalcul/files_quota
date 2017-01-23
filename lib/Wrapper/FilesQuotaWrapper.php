<?php

namespace OCA\Files_Quota\Wrapper;

use OC\Log;
use OCP\IDb;
use OC\Files\Storage\Storage;

use OC\Files\Filesystem;
use OC\Files\Storage\Wrapper\Wrapper;
use \OCP\ILogger;

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

	protected	$logger;

	public function __construct($parameters) {
		$this->storage = 	$parameters['storage'];
		$this->quota = 		$parameters['quota'];
		$this->db =			$parameters['db'];
		$this->sizeRoot = 	isset($parameters['root']) ? $parameters['root'] : '';
		$this->logger =		$parameters['logger'];
		$this->logger->error("________________________________________________________________________");

		$this->logger->error("IN THE CONSTRUCTOR");
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
		$this->logger->error("IN THE getsize");

		if (is_null($storage)) {
			$cache = $this->getCache();
		} else {
			$cache = $storage->getCache();
		}
		$data = $cache->get($path);
		if (isset($data['size'])) {
			$this->logger->error("SIZE of " . $path . " = " . $data['size']);
			return $data['size'];
		} else {
			return \OCP\Files\FileInfo::SPACE_NOT_COMPUTED;
		}
	}


	public function check_free_space($path)
	{
		if ($this->quota < 0) {
			$this->logger->error("quota < 0");
			return $this->storage->free_space($path);
		} else {
			$used = $this->getSize($this->sizeRoot);
			if ($used < 0) {
				$this->logger->error("used < 0");
				return \OCP\Files\FileInfo::SPACE_NOT_COMPUTED;
			} else {
				$free = $this->storage->free_space($path);
				$quotaFree = max($this->quota - $used, 0);
				$this->logger("USED = " . $used);
				// if free space is known
				if ($free >= 0) {
					$this->logger->error("free >= 0");
					$free = min($free, $quotaFree);
				} else {
					$this->logger->error("free < 0");
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

		$this->logger->error("IN THE freespace");
		$this->logger->error("PATH = " . $path);

		if ($this->quota < 0) {
			$this->logger->error("quota < 0");
			return $this->storage->free_space($path);
		} else {
			$used = $this->getSize($this->sizeRoot);
			if ($used < 0) {
				$this->logger->error("used < 0");
				return \OCP\Files\FileInfo::SPACE_NOT_COMPUTED;
			} else {
				$free = $this->storage->free_space($path);
				$quotaFree = max($this->quota - $used, 0);
				// if free space is known
				if ($free >= 0) {
					$this->logger->error("free >= 0");
					$free = min($free, $quotaFree);
				} else {
					$this->logger->error("free < 0");
					$free = $quotaFree;
				}
			}
		}

		$user = $this->storage->getUser()->getUID();
		//check first if the user exist in our DB
		$path2 = Filesystem::normalizePath($this->storage->getLocalFile($path), true, true);
		$filesize = $this->getSize($path2);
		$this->logger->error("FILESIZE ============== " . $filesize['size']);
		$data = $this->db->findUserData($user);
		if (!isset($data['user']))
		{
			$this->logger->error("don't find in db");
			$this->exist = false;
			$nb_files = $this->db->getUploadedFilesNumber($user);
			$used = $this->quota - $free;
			$data = ['user' => $user, 'nb_files' => $nb_files, 'user_size' => $used,
				'quota_files' => $this->default_nb_files, 'quota_size' => $this->default_size];
		}
		$this->logger->error("SIZEROOT = " . $this->sizeRoot);
		if (!$this->isPartFile($path)) {
			$this->logger->error("is not a part files");
			$free = $this->check_free_space('');
			$this->logger->error("TEST DES VALUES !!!");
			if ($free >= 0 && $data['quota_files'] - $data['nb_files'] > 0) {
				$this->logger->error("after the big check");
				// only apply quota for files, not metadata, trash or others
				if (strpos(ltrim($path, '/'), 'files/') === 0) {
					$this->logger->error("je suis la dedans ?");
					if ($this->exist == true)
					{
						$this->logger->error("it exist");
						$this->db->updateUserData(['username' => $user,
						'file_size' => $this->quota - ($free)]);
					}
					else
					{
						$this->logger->error("don't exist");
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
	 * see http://php.net/manual/en/function.copy.php
	 *
	 * @param string $source
	 * @param string $target
	 * @return bool
	 */
	public function copy($source, $target) {
		$this->logger->error("copy copy copy");
		$free = $this->free_space('');
		if ($free < 0 or $this->getSize($source) < $free) {
			return $this->storage->copy($source, $target);
		} else {
			return false;
		}
	}


	/**
	 * see http://php.net/manual/en/function.file_put_contents.php
	 *
	 * @param string $path
	 * @param string $data
	 * @return bool
	 */
	public function file_put_contents($path, $data) {
		$this->logger->error("QUAND EST CE QUE JE RENTRE LA DEDANS ?");
		$free = $this->free_space('');
		if ($free < 0 or strlen($data) < $free) {
			return $this->storage->file_put_contents($path, $data);
		} else {
			return false;
		}
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
		$this->logger->error("IN THE copyfromstorage");

		$free = $this->free_space('');
		if ($free < 0 or $this->getSize($sourceInternalPath, $sourceStorage) < $free) {
			return $this->storage->copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		} else {
			return false;
		}
	}

	public function unlink($path) {
		$this->logger->error("IN UNLINK PLSSSS");
		$this->logger->error("PATH = " . $path);


		if ($this->quota < 0) {
			$this->logger->error("quota < 0");
			return $this->storage->free_space($path);
		} 
		else 
		{
			$used = $this->getSize($this->sizeRoot);
			if ($used < 0) {
				$this->logger->error("used < 0");
				return \OCP\Files\FileInfo::SPACE_NOT_COMPUTED;
			} else {
				$free = $this->storage->free_space($path);
				$quotaFree = max($this->quota - $used, 0);
				// if free space is known
				if ($free >= 0) {
					$this->logger->error("free >= 0");
					$free = min($free, $quotaFree);
				} else {
					$this->logger->error("free < 0");
					$free = $quotaFree;
				}
			}
		}

		$user = $this->storage->getUser()->getUID();
		$data = $this->db->findUserData($user);
		$this->logger->error("before isset");
		if (!isset($data['user']))
		{
			$this->logger->error("don't find in db");
			$this->exist = false;
			$nb_files = $this->db->getUploadedFilesNumber($user);
			$this->logger->error('nb files = ' . $nb_files);
			$used = $this->quota - $free;
			$data = ['user' => $user, 'nb_files' => $nb_files, 'user_size' => $used,
				'quota_files' => $this->default_nb_files, 'quota_size' => $this->default_size];
		}
		if ($this->exist)
		{
			$this->db->suppressFile($user);
		}
		else
		{
			$this->db->addNewUserQuota(['username' => $user,
						'nb_files' => $data['nb_files'],
						'sum_files' => $this->quota - $free]);
		}
	}

	/**
	 * @param \OCP\Files\Storage $sourceStorage
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @return bool
	 */
	public function moveFromStorage(\OCP\Files\Storage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		$this->logger->error("IN THE move from storage");

		$free = $this->free_space('');
		if ($free < 0 or $this->getSize($sourceInternalPath, $sourceStorage) < $free) {
			return $this->storage->moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		} else {
			return false;
		}
	}
}
