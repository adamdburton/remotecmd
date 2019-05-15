<?php

namespace AdamDBurton\RemoteCmd;

use AdamDBurton\RemoteCmd\Exceptions\CommandException;

class Command
{
    private $connection;
    private $command;
    private $output;
    private $error;
    private $exitCode;

    private $successCallback;
    private $failureCallback;

    /**
     * Command constructor.
     * @param Connection $connection
     * @param null $command
     */
    public function __construct(Connection $connection, $command = null)
    {
        $this->connection = $connection;
        $this->command = $command;

        $this->successCallback = function () {
        };
        $this->failureCallback = function ($output, $exitCode, $error) {
            throw new CommandException($this->command, $output, $exitCode, $error);
        };
    }

    /**
     * @param $commands
     * @return $this
     */
    public function all($commands)
    {
        $this->command = implode("\n", is_array($commands) ? $commands : func_get_args());

        return $this;
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function success(callable $callback)
    {
        $this->successCallback = $callback;

        return $this;
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function failure(callable $callback)
    {
        $this->failureCallback = $callback;

        return $this;
    }

    /**
     * @return $this
     */
    public function run()
    {
        //echo sprintf('Running \'%s\' on \'%s\'', $this->command, $this->connection->getConnection()->host) . PHP_EOL;

        $this->exitCode = 0;
        $this->error = '';

        $connection = $this->getConnection();

        $connection->enableQuietMode();
        $this->output = trim($connection->exec($this->command));
        $connection->disableQuietMode();

        $this->exitCode = (int)$connection->getExitStatus();
        $this->error = $connection->getStdError();

        //		var_dump([
        //			'command' => $this->command,
        //			'output' => $this->output,
        //			'exitCode' => $this->exitCode,
        //			'error' => $this->error
        //		]);

        if ($this->error == '') {
            $callback = $this->successCallback;

            $callback($this->output, $this->exitCode);
        } else {
            $callback = $this->failureCallback;

            $callback($this->output, $this->exitCode, $this->error);
        }

        return $this;
    }

    /**
     * @param string $password
     * @return Command
     */
    public function runAsSudo($password)
    {
        $this->command = sprintf('echo "%s" | sudo -S "%s"', $password, $this->command);

        return $this->run();
    }

    /**
     * @return \phpseclib\Net\SSH2
     */
    private function getConnection()
    {
        return $this->connection->getConnection();
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return int
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }
}
