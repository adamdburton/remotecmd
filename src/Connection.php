<?php

namespace AdamDBurton\RemoteCmd;

use AdamDBurton\RemoteCmd\Exceptions\AuthenticationException;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;

define('NET_SSH2_LOGGING', SSH2::LOG_SIMPLE);

class Connection
{
	private $connection;

	const FILE_EXISTS_COMMAND = 'test -f "%s"';
	const DIRECTORY_EXISTS_COMMAND = 'test -d "%s"';

	const READ_FILE_COMMAND = 'cat "%s"';
	const WRITE_FILE_COMMAND = 'echo $\'%s\' > "%s"';

	const CREATE_DIRECTORY_COMMAND = 'mkdir "%s"';
	const CREATE_DIRECTORY_FORCE_COMMAND = 'mkdir -p "%s"';

	public function __construct($host, $port = 22, $timeout = 10)
	{
		$this->connection = new SSH2($host, $port, $timeout);
	}

	public function authWithPassword($username, $password)
	{
		$loggedIn = $this->connection->login($username, $password);

		if(!$loggedIn)
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

		$loggedIn = $this->connection->login($username, $key);

		if(!$loggedIn)
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

	public function readFile($filename)
	{
		$command = $this->command(sprintf(self::READ_FILE_COMMAND, $filename))->run();

		return $command->getOutput();
	}

	public function writeFile($filename, $content, $force = false)
	{
		if(!$this->directoryExists(dirname($filename)))
		{
			$this->createDirectory(dirname($filename), $force);
		}

		$command = $this->command(sprintf(self::WRITE_FILE_COMMAND, $content, $filename))->run();

		return $command->getExitCode() == 0;
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
