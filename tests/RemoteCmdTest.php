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
		$this->connection->command('whoami')
			->success(function($output)
			{
				$this->assertTrue($output == getenv('SSH_USERNAME'), 'Command `whoami` output is not equal to ' . getenv('SSH_USERNAME'));
			})
			->run();
	}

//	public function testCanRunSudoCommand()
//	{
//		$this->connection->command('touch sudo.txt')->runAsSudo(getenv('SSH_PASSWORD'));
//	}

	public function testCanRunTask()
	{
		$this->connection->task()
			->ifFileExists('test.txt')
			->then(function(Connection $connection)
			{
				$connection->command('rm test.txt')->run();

				$this->assertFalse($connection->fileExists('test.txt'), 'File text.txt exists');
			})
			->else(function(Connection $connection)
			{
				$connection->command('touch test.txt')->run();

				$this->assertTrue($connection->fileExists('test.txt'), 'File test.txt does not exist');
			})
			->run();
	}

	public function testCanReadFile()
	{
		$this->connection->command('echo "readFile" > "test.txt"')->run();

		$content = $this->connection->readFile('test.txt');

		$this->assertTrue($content == 'readFile', 'File content does not match readFile');

		$this->connection->command('rm -f test.txt')->run();
	}

	public function testCanWriteFile()
	{
		$this->connection->writeFile('test.txt', 'writeFile');

		$content = $this->connection->command('cat "test.txt"')->run()->getOutput();

		$this->assertTrue($content == 'writeFile', 'File content does not match writeFile');

		$this->connection->command('rm -f test.txt')->run();
	}

}