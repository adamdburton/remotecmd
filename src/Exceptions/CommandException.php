<?php

namespace AdamDBurton\RemoteCmd\Exceptions;

use Throwable;

class CommandException extends \Exception
{
    protected $command;
    protected $output;
    protected $exitCode;

    /**
     * CommandException constructor.
     * @param string $command
     * @param string $output
     * @param int $exitCode
     * @param string $error
     * @param Throwable|null $previous
     */
    public function __construct($command, $output, $exitCode, $error, Throwable $previous = null)
    {
        $this->command = $command;
        $this->output = $output;
        $this->exitCode = $exitCode;

        parent::__construct('Command error: ' . $error, 0, $previous);
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
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
}
