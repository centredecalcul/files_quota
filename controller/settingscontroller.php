<?php


namespace OCA\Files_Quota\Controller;
use OCP\AppFramework\Controller;
use	OCP\IL10N;
use	OCP\IRequest;
use	OCP\IConfig;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCA\Files_Quota\Db\FilesQuotaMapper;
use OCP\ILogger;

class	SettingsController extends Controller {
	/** @var IL10N */
	private	$l10n;
	/* configuration object */
	private	$config;
	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IL10N $l10n
	 */
	

	// default nb files
	private $default_nb_files = 20000;

	

	/* our mapper to access db */
	private $db;

	private $log;

	public function __construct($appName, IRequest $request, IL10N $l10n,
                IConfig $config, FilesQuotaMapper $db, ILogger $log) {
		parent::__construct($appName, $request);
		$this->l10n = $l10n;
        $this->config = $config;
        $this->db = $db;
        $this->log = $log;
	}

	/**
	 * Admin page
	 */
	public function index() {
		$params = [
			'userList' => $this->db->getUserList(),
			'defaultNbFiles' => $this->getValue("quotaDefault"),
		];
		return new TemplateResponse($this->appName, 'settings-admin', $params, 'blank');  // templates/settings-admin.php
	}

	/**
	 * Set a configuration value in the twofactor_privacyidea app config.
	 *
	 * @param string $key configuration key
	 * @param string $value configuration value
	 */
	public function setValue($key, $value) {
		$this->config->setAppValue("files_quota", $key, $value);
	}

	/**
	 * Retrive a configuration from the twofactor_privacyidea app config.
	 *
	 * @param string $key configuration key
	 * @return string
	 */
	public function getValue($key) {
		$value = $this->config->getAppValue("files_quota", $key);
		if ($value === "")
		{
			$this->setValue("quotaDefault", $this->default_nb_files);
			return $this->default_nb_files;
		}
		return $value;
	}

	public function setDefaultQuota($quota)
	{
		$val = (int) $quota;
		$this->setValue("quotaDefault", $quota);
		return new DataResponse(['error' => 0]);
	}

	public function setUserQuota($quota, $username)
	{
		$return = $this->db->setUserQuota($quota, $username);
		return new DataResponse(['error' => $return]);
	}
}