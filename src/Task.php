<?php

namespace AdamDBurton\RemoteCmd;

class Task
{
	private $conditionalCallback;
	private $thenCallback;
	private $elseCallback;

	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	public function if($conditionalCallback)
	{
		$this->conditionalCallback = $conditionalCallback;

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

	public function run()
	{
		$success = $this->thenCallback;
		$failure = $this->elseCallback;

		$this->connection
			->command($this->conditionalCallback)
			->success(function() use ($success)
			{
				$success($this->connection->task());
			})
			 	->failure(function() use ($failure)
			{
				$failure($this->connection->task());
			});
	}
}