<?php

namespace AdamDBurton\RemoteCmd;

class Command
{
	private $connection;
	private $command;

	private $successCallback;
	private $failureCallback;

	public function __construct(Connection $connection, $command)
	{
		$this->connection = $connection;
		$this->command = $command;

		$this->successCallback = function() {};
		$this->failureCallback = function($error) {};
	}

	public function success(Callable $callback)
	{
		$this->successCallback = $callback;

		return $this;
	}

	public function failure(Callable $callback)
	{
		$this->failureCallback = $callback;

		return $this;
	}

	public function run()
	{
		$connection = $this->getConnection();

		$connection->enableQuietMode();
		$output = trim($connection->exec($this->command));
		$connection->disableQuietMode();

		$exitCode = $connection->getExitStatus();

		if($exitCode == 0)
		{
			$callback = $this->successCallback;

			return $callback($output, $exitCode);
		}
		else
		{
			$callback = $this->failureCallback;

			return $callback($output, $exitCode, $connection->getStdError());
		}
	}

	private function getConnection()
	{
		return $this->connection->getConnection();
	}
}