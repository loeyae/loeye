<?php

/**
 * RunApp.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/5 12:16
 */

namespace loeye\commands;


use loeye\console\Command;
use loeye\server\Factory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunApp extends Command
{

    protected $name = 'loeye:run-app';
    protected $desc = 'run application';
    /**
     * @inheritDoc
     */
    public function process(InputInterface $input, OutputInterface $output)
    {
        $server = Factory::create();
        $server->run();
    }
}