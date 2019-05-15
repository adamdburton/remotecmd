<?php

namespace AdamDBurton\RemoteCmd;

class Task
{
    protected $connection;
    protected $command;

    /**
     * Task constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return mixed
     */
    public function run()
    {
        $command = $this->getCommand();

        return $command->run();
    }

    /**
     * @param string $password
     * @return mixed
     */
    public function runAsSudo($password)
    {
        $command = $this->getCommand();

        return $command->runAsSudo($password);
    }

    /**
     * @return Command
     */
    protected function getCommand()
    {
        $command = $this->connection->command($this->command);

        return $command;
    }
}
