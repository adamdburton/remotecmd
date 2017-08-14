<?php

namespace AdamDBurton\RemoteCmd;

use AdamDBurton\RemoteCmd\Exceptions\AuthenticationException;
use AdamDBurton\RemoteCmd\Exceptions\ConnectionException;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SCP;
use phpseclib\Net\SSH2;

//define('NET_SSH2_LOGGING', SSH2::LOG_SIMPLE);

class Connection
{
	private $connection;

	const FILE_EXISTS_COMMAND = 'test -f "%s"';
	const DIRECTORY_EXISTS_COMMAND = 'test -d "%s"';

	const CREATE_DIRECTORY_COMMAND = 'mkdir "%s"';
	const CREATE_DIRECTORY_FORCE_COMMAND = 'mkdir -p "%s"';

	public function __construct($host, $port = 22, $timeout = 10)
	{
		$this->connection = new SSH2($host, $port, $timeout);

//		if(!$this->connection->isConnected())
//		{
//			throw new ConnectionException($this->connection->getLastError());
//		}
	}

	public function authWithPassword($username, $password)
	{
		$authenticated = $this->connection->login($username, $password);

		if(!$authenticated)
		{
			throw new AuthenticationException($this->connection->getLastError());
		}

		return $this;
	}

	public function authWithKey($username, $keyString, $keyPassword = null)
	{
		$key = new RSA();

		if($keyPassword !== null)
		{
			$key->setPassword($keyPassword);
		}

		$key->loadKey($keyString);

		$authenticated = $this->connection->login($username, $key);

		if(!$authenticated)
		{
			throw new AuthenticationException($this->connection->getLastError());
		}

		return $this;
	}

	public function command($command)
	{
		return new Command($this, $command);
	}

	public function task()
	{
		return new Task($this);
	}

	public function fileExists($filename)
	{
		$command = $this->command(sprintf(self::FILE_EXISTS_COMMAND, $filename))->run();

		return $command->getExitCode() == 0;
	}

	public function readFile($filename, $destination)
	{
		$scp = new SCP($this->connection);
		$scp->get($filename, $destination);
	}

	public function writeFile($filename, $content, $force = false)
	{
		if(!$this->directoryExists(dirname($filename)))
		{
			$this->createDirectory(dirname($filename), $force);
		}

		$scp = new SCP($this->connection);
		$scp->put($filename, $content);
	}

	public function directoryExists($directory)
	{
		$command = $this->command(sprintf(self::DIRECTORY_EXISTS_COMMAND, $directory))->run();

		return $command->getExitCode() == 0;
	}

	public function createDirectory($directory, $force = false)
	{
		$command = $this->command(sprintf($force ? self::CREATE_DIRECTORY_FORCE_COMMAND : self::CREATE_DIRECTORY_COMMAND, $directory))->run();

		return $command->getExitCode() == 0;
	}

	public function getConnection()
	{
		return $this->connection;
	}

	public function getIsConnected()
	{
		return $this->connection->isConnected();
	}

	public function getIsAuthenticated()
	{
		return $this->connection->isAuthenticated();
	}

	public function getLog()
	{
		return $this->connection->getLog();
	}
}
