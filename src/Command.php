<?php

namespace AdamDBurton\RemoteCmd;

use AdamDBurton\RemoteCmd\Exceptions\CommandException;

class Command
{
	private $connection;
	private $command;
	private $output;
	private $error;
	private $exit;

	private $successCallback;
	private $failureCallback;

	public function __construct(Connection $connection, $command)
	{
		$this->connection = $connection;
		$this->command = $command;

		$this->successCallback = function() {};
		$this->failureCallback = function($output, $exitCode, $error) { throw new CommandException($error, $exitCode); };
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

	public function runAsSudo($password)
	{
		$this->command = sprintf('echo %s | sudo -S %s', $password, $this->command);

		return $this->run();
	}

	public function run()
	{
		echo sprintf('Running "%s" on "%s"', $this->command, $this->connection->getConnection()->host) . PHP_EOL;

		$this->exit = 0;
		$this->error = '';

		$connection = $this->getConnection();

		$connection->enableQuietMode();
		$this->output = trim($connection->exec($this->command));
		$connection->disableQuietMode();

		$this->exit = $connection->getExitStatus();
		$this->error = $connection->getStdError();

//		var_dump([
//			'command' => $this->command,
//			'output' => $this->output,
//			'exit' => $this->exit,
//			'error' => $this->error
//		]);

		if($this->error == '')
		{
			$callback = $this->successCallback;

			$callback($this->output, $this->exit);
		}
		else
		{
			$callback = $this->failureCallback;

			$callback($this->output, $this->exit, $this->error);
		}

		return $this;
	}

	private function getConnection()
	{
		return $this->connection->getConnection();
	}

	public function getOutput()
	{
		return $this->output;
	}

	public function getExitCode()
	{
		return $this->exit;
	}

	public function getError()
	{
		return $this->error;
	}
}