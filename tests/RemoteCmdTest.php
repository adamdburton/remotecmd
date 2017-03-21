<?php

use PHPUnit\Framework\TestCase;

use AdamDBurton\RemoteCmd\Connection;

class RemoteCmdTest extends TestCase
{
	/**
	 * @var Connection
	 */
	private $connection;

	protected function setUp()
	{
		$this->connection = new Connection(getenv('SSH_HOSTNAME'), getenv('SSH_PORT') ?: 22, getenv('SSH_TIMEOUT') ?: 5);

		$this->connection->authWithPassword(getenv('SSH_USERNAME'), getenv('SSH_PASSWORD'));
	}

	protected function tearDown()
	{
		var_dump($this->connection->getLog());
	}

	protected function assertPreConditions()
	{
		$this->assertTrue($this->connection->getIsConnected(), 'SSH is not connected');
		$this->assertTrue($this->connection->getIsAuthenticated(), 'SSH is not authenticated');
	}

	public function testCanRunCommand()
	{
		$this->connection->command('whoami')->success(function($output)
		{
			$this->assertTrue($output == getenv('SSH_USERNAME'), 'Command `whoami` output is not equal to ' . getenv('SSH_USERNAME'));
		})->failure(function($output)
		{
			$this->fail('Command `whoami` failed to be executed');
		});
	}

}