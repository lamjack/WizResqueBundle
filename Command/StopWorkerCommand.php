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
use Wiz\ResqueBundle\Model\Worker;

/**
 * Class StopWorkerCommand
 * @package Wiz\ResqueBundle\Command
 */
class StopWorkerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('resque:worker-stop')
            ->setDescription('Stop a resque worker')
            ->addArgument('id', InputArgument::OPTIONAL, 'Worker id')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Should kill all workers');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resque = $this->getContainer()->get('wiz_resque.service.resque');

        if ($input->getOption('all')) {
            $workers = $resque->getWorkers();
        } else {
            $worker = $resque->getWorker($input->getArgument('id'));
            if (is_null($worker)) {
                $running_workers = $resque->getWorkers();
                if (count($running_workers) > 0) {
                    $output->writeln('<error>You need to give an existing worker.</error>');
                    $output->writeln('Running workers are:');
                    foreach ($resque->getWorkers() as $worker) {
                        $output->writeln('<info>' . $worker->getId() . '</info>');
                    }
                } else {
                    $output->writeln('<error>There is no running worker.</error>');
                }

                return 1;
            }
            $workers = array($worker);
        }

        foreach ($workers as $worker) {
            /** @var Worker $worker */
            if (version_compare(phpversion(), '7.0.0', '<')) {
                $output->writeln(sprintf('<info>Stoping worker %s...</info>', $worker->getId()));
                $status = $worker->stop();
            } else {
                $output->writeln(sprintf('<info>Killing worker %s...</info>', $worker->getId()));
                $status = $worker->kill();
            }
            if (false === $status)
                $output->writeln(sprintf('<error>Stop or Kill worker %s failed</error>', $worker->getId()));
        }

        $resque->pruneDeadWorkers();

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return false;
    }
}