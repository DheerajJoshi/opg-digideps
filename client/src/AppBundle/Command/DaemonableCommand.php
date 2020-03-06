<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

const SECONDS_IN_A_MINUTE = 1;

abstract class DaemonableCommand extends Command
{
    private $shutdownRequested = false;

    protected function configure()
    {
        $this
            ->addOption('daemon', 'd', InputOption::VALUE_NONE, 'Whether to run in daemon mode');
        ;
    }

    protected function daemonize(InputInterface $input, OutputInterface $output, callable $callback)
    {
        if ($input->getOption('daemon')) {
            $stopCommand = function() use ($output) {
                $output->writeln('Stopping...');
                $this->shutdownRequested = true;
            };

            pcntl_signal(SIGTERM, $stopCommand);
            pcntl_signal(SIGINT, $stopCommand);
        } else {
            $this->shutdownRequested = true;
        }

        do {
            call_user_func($callback, $input, $output);

            pcntl_signal_dispatch();

            if (!$this->shutdownRequested) {
                sleep(SECONDS_IN_A_MINUTE * 10);
            }

        } while (!$this->shutdownRequested);

        return 0;
    }
}
