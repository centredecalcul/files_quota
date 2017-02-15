<?php


namespace OCA\Files_Quota\Controller;
use OCP\AppFramework\Controller;
use	OCP\IL10N;
use	OCP\IRequest;
use	OCP\IConfig;
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
		];
		$this->log->error('AVANT FOREACH');
		foreach ($params['userList'] as $row)
		{
			$this->log->error("NOM : " . $row['uid']);
		}
		return new TemplateResponse($this->appName, 'settings-admin', $params);  // templates/settings-admin.php
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
		return $this->config->getAppValue("files_quota", $key);
	}


}