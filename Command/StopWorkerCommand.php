<?php
/**
 * Author: jack<linjue@wilead.com>
 * Date: 15/7/7
 */

namespace Wiz\ResqueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StopWorkerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('wiz:resque:worker-stop')
            ->setDescription('Stop a resque worker')
            ->addArgument('id', InputArgument::OPTIONAL, 'Worker id')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Should kill all workers');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resque = $this->getContainer()->get('wiz_resque.service.resque');
        if ($input->getOption('all')) {
            $workers;
        } else {

        }
    }
}