<?php

namespace AdamDBurton\RemoteCmd;

class ConditionalTask extends Task
{
    protected $conditionalCommands;

    protected $thenCallback;
    protected $elseCallback;
    protected $failureCallback;

    /**
     * @param string $conditionalCommand
     * @return $this
     */
    public function if($conditionalCommand)
    {
        $this->conditionalCommands[] = $conditionalCommand;

        return $this;
    }

    /**
     * @param string $filename
     * @return $this
     */
    public function ifFileExists($filename)
    {
        $this->conditionalCommands[] = sprintf(Connection::FILE_EXISTS_COMMAND, $filename);

        return $this;
    }

    /**
     * @param string $directory
     * @return $this
     */
    public function ifDirectoryExists($directory)
    {
        $this->conditionalCommands[] = sprintf(Connection::DIRECTORY_EXISTS_COMMAND, $directory);

        return $this;
    }

    /**
     * @param Callable $thenCallback
     * @return $this
     */
    public function then($thenCallback)
    {
        $this->thenCallback = $thenCallback;

        return $this;
    }

    /**
     * @param Callable $failureCallback
     * @return $this
     */
    public function failure($failureCallback)
    {
        $this->failureCallback = $failureCallback;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getCommand()
    {
        $then = $this->thenCallback;
        $else = $this->elseCallback;
        $failure = $this->failureCallback;

        $command = $this->connection->command()->all($this->conditionalCommands);

        $command->success(function ($output, $exitCode) use (
          $then,
          $else
        ) {
            if ($exitCode === 0) {
                $then($this->connection);
            } else {
                $else($this->connection);
            }
        });

        $command->failure(function ($output, $exitCode, $error) use ($failure) {
            $failure($this->connection, $output, $exitCode, $error);
        });

        return $command;
    }
}
