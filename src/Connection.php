<?php

namespace AdamDBurton\RemoteCmd;

use AdamDBurton\RemoteCmd\Exceptions\AuthenticationException;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;

define('NET_SSH2_LOGGING', SSH2::LOG_SIMPLE);

class Connection
{
	private $connection;

	public function __construct($host, $port = 22, $timeout = 5)
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

	public function command($command, $success = null, $failure = null)
	{
		return new Command($this, $command, $success, $failure);
	}

	public function task()
	{
		return new Task($this);
	}

	public function fileExists($filename)
	{
		$command = $this->command(sprintf('test -f %s', $filename))->run();

		return $command->getExitCode() == 0;
	}

	public function directoryExists($directory)
	{
		$command = $this->command(sprintf('test -d %s', $directory))->run();

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
