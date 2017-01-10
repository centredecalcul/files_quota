<?php

namespace OCA\FilesQuota\Wrapper;

use OC\Files\Storage\Wrapper\Wrapper;
use Sabre\DAV\Exception\InsufficientStorage;

class FilesQuotaWrapper extends Wrapper{

    private $default_size = 5368709120;

    private $default_nb_files = 20000;

	/**
	 * @var IL10N
	 */
	protected $l10n;

	/**
	 * @var ILogger;
	 */
	protected $logger;


    /**
     * @var IDBConnection;
     */
	protected $db;

    /**
     * @param array $parameters
     */
	public function __construct($parameters) {
		parent::__construct($parameters);
		$this->l10n = $parameters['l10n'];
		$this->logger = $parameters['logger'];
		$this->db = $parameters['db'];
	}


	/**
	 * Asynchronously read databases to check files quota
	 * @param string $path
	 * @param string $mode
	 * @return resource | bool
	 */
	public function	dbcheck($username, $path)
	{
        if (isset($username))
        {
            try
            {
                $this->logger->warning("TEST JE SUIS DANS DBCHECK APRES LE TRY");
                $data = null;
                $arg = null;
                $path = "home::" . $username . "%";
                $sql = 'SELECT user_files as `user_files`, user_size  as `user_size`,
                        quota_files as `quota_files`, quota_size as `quota_size` FROM `*PREFIX*files_quota` ' .
                        'WHERE `user` like ?';
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(1, $username, \PDO::PARAM_STR);
                $stmt->execute();
                $row = $stmt->fetch();
                $stmt->closeCursor();

                if (!isset($row))
                {
                    $this->logger->warning("MA DATABASE EST VIDE");

                    $arg = 'no_user_in_db';
                    $data = $this->getData($username, $arg);
                    if ($data['nb_files'] + 1 > $this->default_nb_files)
                    {
                        throw new InsufficientStorage('Not enough storage place. You have too much files');
                    }
                    if($data['sum_size'] + $this->filesize($path) > $this->default_size)
                    {
                        throw new InsufficientStorage(
                                'Not enough storage place. You have used too much bytes');
                    }
                }
            } catch (\Exception $e)
            {
                $message = 	implode(' ', [ __CLASS__, __METHOD__, $e->getMessage()]);
                $this->logger->warning($message);
            }
        }
	}


    /**
     * Take usefull datas in file_cache database and fill the application database
     * @param string $username
     * @return data | data['nb_files'],['sum_size']
     */
	private function get_data($username, $arg)
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

        $sql = 'SELECT SUM(size) as `sum_size`, COUNT(storage) as `nb_files` ' .
            ' FROM `*PREFIX*filecache` WHERE `storage` = ? AND `path` like `files/%`';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $numeric_id, \PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        $stmt->closeCursor();
        $data = $row;

        if (strcmp('no_user_in_db', $arg)) {
            $nb_files = $row['nb_files'];
            $sum_files = $row['sum_size'];
            $sql = "INSERT INTO `*PREFIX*files_quota` VALUES (`$username`, `$nb_files`, `$sum_files`)";
            $stmt = $this->db->prepare($sql);
            if (!$stmt->execute()) {
                $this->logger("Echec lors de l'exécution : (" . $stmt->errno . ") " . $stmt->error);
            }
        }
        return $data;
    }

}