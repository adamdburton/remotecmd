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
		//var_dump($this->connection->getLog());
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
		})->run();
	}

	public function testCanRunTask()
	{
		$this->connection->task()
			->if('test -f test.txt')
			->then(function(Connection $connection)
			{
				$connection->command('rm test.txt')->run();

				$this->assertFalse($connection->fileExists('test.txt'), 'File text.txt exists');
			})
			->else(function(Connection $connection)
			{
				$connection->command('touch test.txt')->run();

				$this->assertTrue($connection->fileExists('test.txt'), 'File text.txt does not exist');
			})
			->failure(function(Connection $connection, $output, $exitCode, $error)
			{
				$this->fail('Command error: ' . $error);
			})
			->run();
	}

}