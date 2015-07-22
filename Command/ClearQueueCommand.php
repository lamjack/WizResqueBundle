<?php
/**
 * Author: jack<linjue@wilead.com>
 * Date: 15/7/22
 */

namespace Wiz\ResqueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ClearQueueCommand
 * @package Wiz\ResqueBundle\Command
 */
class ClearQueueCommand extends ContainerAwareCommand
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('resque:clear-queue')
            ->setDescription('Clear a queue with queue name')
            ->addArgument('queue', InputArgument::REQUIRED, 'Queue name');
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|int null or 0 if everything went fine, or an error code
     *
     * @throws \LogicException When this abstract method is not implemented
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resque = $this->getContainer()->get('wiz_resque.service.resque');
        $queue = $input->getArgument('queue');

        $count = $resque->clearQueue($queue);
        $output->writeln(sprintf("<info>Cleared queue '%s' [removed %d entries]</info>", $queue, $count));

        return 0;
    }
}