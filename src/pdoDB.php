<?php

declare(strict_types=1);

namespace GAState\Tools\pdoDB;

use PDO;
use InvalidArgumentException;
use Exception;
use PDOException;



class pdoDB
{
    public PDO $pdo;
    private bool $bConnected;
	private array $parameters;
    # @object, PDO statement object
	/** @phpstan-ignore-next-line */
	private $sQuery;

    public function __construct(
        private string $database,
	    private string $username,
	    private string $password,
        private string $key = '',
        private string $certificate = '',
        private string $cacert = '',
        private string $type = 'mysql',
        private string $hostname = '127.0.0.1',
        private bool $tls = FALSE,
        private int $port = 3306,
		private string $dsnName = '',
        private string $sqliteFile = ''
    )
		{ 			
			$this->Connect($this->type, $this->hostname, $this->database, $this->username, $this->password, $this->port);
		}

    /**
     * @param string $type
     * @param string $hostname
	 * @param string $database
	 * @param string $username
	 * @param string $password
	 * @param int $port
     * @return void
     */	

    private function Connect($type, $hostname, $database, $username, $password, $port) : void
	//oci:dbname=//host:port/SID/INSTANCE_NAME
		{
			global $settings;
            $this->CheckPDODriver($type);
			$dsn = match ($type) {
                'mysql'  => "mysql:host=$hostname;dbname=$database;port=$port;charset=utf8mb4",
                'oci'    => "oci:dbname=$hostname/orcl;charset=utf8mb4'",
                'mssql'  => "dblib:host=$hostname;dbname=$database;charset=UTF-8",
                'sqlite' => "sqlite:$this->sqliteFile",
                'odbc'   => "odbc:$this->dsnName",
                'pgsql'  => "pgsql:host=$hostname;port=$port;dbname=$database",
                default  => throw new InvalidArgumentException("Unknown Database Connection")
            };
			try 
			{
				$this->pdo = new PDO($dsn, $username, $password);
				$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

                if ($type === 'mysql') {
                    $this->pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES utf8');
                    $this->pdo->setAttribute(PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT, false);
                    if ($this->tls === true) {
                        $this->pdo->setAttribute(PDO::MYSQL_ATTR_SSL_KEY, $this->key);
                        $this->pdo->setAttribute(PDO::MYSQL_ATTR_SSL_CERT, $this->certificate);
                        $this->pdo->setAttribute(PDO::MYSQL_ATTR_SSL_CA, $this->cacert);
                    }
                }
				# Connection succeeded, set the boolean to true.
				$this->bConnected = true;
			}
			catch (PDOException $e) 
			{
				$this->pdo_exception($e);
			}
            $this->parameters = array();
			return;
	    }

        /**
     * @param string $databasetype
     * 
     * @return void
     */	
    public function CheckPDODriver(string $databasetype) : void
	{
        try 
		{
        	if (!in_array("$databasetype",PDO::getAvailableDrivers(),TRUE))
        		{
                	throw new PDOException ("PDO Driver for the database is not setup.");
            	}
        }
        catch (PDOException $e)
        {
            $this->pdo_exception($e);
        }
		return;
    }

    /**
     * @param PDOException $e
     * @param string $query
     * @return void
     */
    function pdo_exception(PDOException $e, string $query = '') :void
    {
	    echo 'PDO exception. Error message: "' . $e->getMessage() . '". Error code: ' . strval($e->getCode()) . ' / ' . $query  ;
	    die();
    }

    /**
     * 
     * @return void
     */	

    public function CloseConnection() : void
	 	{
	 		unset($this->pdo);
			return;
	 	}

    public function ShowPDODriver() : void 
    {
        print_r(PDO::getAvailableDrivers());
    }

    /*
	 * @param string $query
	 * @param mixed $parameters
     * @return bool
     */	

    private function Init(string $query, array $parameters = []) : void
		{
		# Connect to database
		if(!$this->bConnected) { $this->Connect($this->type, $this->hostname, $this->database, $this->username, $this->password, $this->port); }
		try {
				# Prepare query
				$this->sQuery = $this->pdo->prepare($query);
				
				# Add parameters to the parameter array	
				$this->bindMore($parameters);

				# Bind parameters
				if($this->parameters != []) {
					foreach($this->parameters as $param)
					{
						$pstring = explode("\x7F",$param);
						$this->sQuery->bindParam($pstring[0],$pstring[1]);
					}		
				}

				# Execute SQL 
				$success = $this->sQuery->execute();
		
			}
			catch(PDOException $e)
			{
				$this->pdo_exception($e, $query);
			}

			# Reset the parameters
			$this->parameters = array();
			return;
		}
    
    /*
	 * @param string $para
	 * @param string $value
     * @return void
     */	
    public function bind(string $para, string $value) : void
	{	
	    $this->parameters[sizeof($this->parameters)] = ":" . $para . "\x7F" . utf8_encode($value);
		return;
	}

    /*
	 * @param array<string,string> $parray
     * @return void
     */	
    public function bindMore(array $parray) : void
	{
		if($this->parameters === [] && count($parray) > 0) {
			$columns = array_keys($parray);
			foreach($columns as $i => &$column)	{
				$this->bind($column, $parray[$column]);
			}
		}
		return;
	}

    /*
	 * @param string $query
	 * @param mixed $fetchmode
	 * @param iterable<Key, Value> $params
     * @return null | array | obj
     */	
    public function query(string $query, array $params = [], mixed $fetchmode = PDO::FETCH_ASSOC) : mixed
	{
		$query = trim($query);

		$this->Init($query,$params);

		$rawStatement = explode(" ", $query);
			
		# Which SQL statement is used 
		$statement = strtolower($rawStatement[0]);
			
		if ($statement === 'select' || $statement === 'show') {
			return $this->sQuery->fetchAll($fetchmode);
		}
		elseif ( $statement === 'insert' ||  $statement === 'update' || $statement === 'delete' ) {
			return $this->sQuery->rowCount();	
		}	
		else {
			return NULL;
		}
	}

    /*
     * @return mixed
     */	
    public function lastInsertId() : mixed
    {
        return $this->pdo->lastInsertId();
    }

	/*
	 * @param string $query
	 * @param mixed $fetchmode
	 * @param iterable<Key, Value> $params
     * @return null | array | obj
     */	
	public function row(string $query, array $params = [], mixed $fetchmode = PDO::FETCH_ASSOC ) : mixed
	{				
		$this->Init($query,$params);
		return $this->sQuery->fetch($fetchmode);			
	}

	/*
	 * @param string $query
	 * @param array<TKey, TValue> $params
     * @return null | array | obj
     */	
    public function single(string $query, array $params = []) : mixed
	{
			$this->Init($query,$params);
			return $this->sQuery->fetchColumn();
	}


}