<?php

namespace AdamDBurton\RemoteCmd;

class Task
{
	private $conditionalCommand;

	private $thenCallback;
	private $elseCallback;
	private $failureCallback;

	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	public function if($conditionalCommand)
	{
		$this->conditionalCommand = $conditionalCommand;

		return $this;
	}

	public function ifFileExists($filename)
	{
		$this->conditionalCommand = sprintf(Connection::FILE_EXISTS_COMMAND, $filename);

		return $this;
	}

	public function ifDirectoryExists($directory)
	{
		$this->conditionalCommand = sprintf(Connection::DIRECTORY_EXISTS_COMMAND, $directory);

		return $this;
	}

	public function then($thenCallback)
	{
		$this->thenCallback = $thenCallback;

		return $this;
	}

	public function else($elseCallback)
	{
		$this->elseCallback = $elseCallback;

		return $this;
	}

	public function failure($failureCallback)
	{
		$this->failureCallback = $failureCallback;

		return $this;
	}

	public function run()
	{
		$command = $this->getConnection();

		return $command->run();
	}

	public function runAsSudo($password)
	{
		$command = $this->getConnection();

		return $command->runAsSudo($password);
	}

	private function getConnection()
	{
		$then = $this->thenCallback;
		$else = $this->elseCallback;
		$failure = $this->failureCallback;

		return $this->connection
			->command($this->conditionalCommand)
			->success(function($output, $exitCode) use ($then, $else)
			{
				if($exitCode == 0)
				{
					$then($this->connection);
				}
				else
				{
					$else($this->connection);
				}
			})
			->failure(function($output, $exitCode, $error) use ($failure)
			{
				$failure($this->connection, $output, $exitCode, $error);
			});
	}
}