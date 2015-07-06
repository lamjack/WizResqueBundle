<?php
/**
 * Author: jack<linjue@wilead.com>
 * Date: 15/7/6
 */

namespace Wiz\ResqueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StartWorkerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('wiz:resque:worker-start')
            ->setDescription('Start a resque worker')
            ->addArgument('queues', InputArgument::REQUIRED, 'Queue names')
            ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'How many workers to fork', 1)
            ->addOption('interval', 'i', InputOption::VALUE_REQUIRED, 'How often to check for new jobs across the queues', 5)
            ->addOption('foreground', '-f', InputOption::VALUE_NONE, 'Should the worker run in foreground')
            ->addOption('memory-limit', '-m', InputOption::VALUE_REQUIRED, 'Force cli memory_limit (expressed in Mbytes)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $env = array();

        // here to work around issues with pcntl and cli_set_process_title in PHP > 5.5
        if (version_compare(PHP_VERSION, '5.5.0') >= 0) {
            $env = $_SERVER;
            unset(
                $env['_'],
                $env['PHP_SELF'],
                $env['SCRIPT_NAME'],
                $env['SCRIPT_FILENAME'],
                $env['PATH_TRANSLATED'],
                $env['argv']
            );
        }

        $env['APP_INCLUDE'] = $this->getContainer()->getParameter('kernel.root_dir') . '/bootstrap.php.cache';
        $env['COUNT'] = $input->getOption('count');
        $env['INTERVAL'] = $input->getOption('interval');
        $env['QUEUE'] = $input->getArgument('queues');
        $env['VERBOSE'] = 1;


        $redisHost = $this->getContainer()->getParameter('bcc_resque.resque.redis.host');
        $redisPort = $this->getContainer()->getParameter('bcc_resque.resque.redis.port');
        $redisDatabase = $this->getContainer()->getParameter('bcc_resque.resque.redis.database');
    }

}