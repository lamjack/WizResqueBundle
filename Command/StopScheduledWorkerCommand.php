<?php
/**
 * Author: jack<linjue@wilead.com>
 * Date: 15/7/22
 */

namespace Wiz\ResqueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wiz\ResqueBundle\Constant;

/**
 * Class StopScheduledWorkerCommand
 * @package Wiz\ResqueBundle\Command
 */
class StopScheduledWorkerCommand extends ContainerAwareCommand
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('resque:scheduledworker-stop')
            ->setDescription('Stop a scheduled resque worker');
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
        $fs = $this->getContainer()->get('filesystem');
        $pid_file = $this->getContainer()->get('kernel')->getCacheDir() . DIRECTORY_SEPARATOR . Constant::PID_FILE;
        if (!$fs->exists($pid_file)) {
            $output->writeln('<error>No PID file found</error>');
            return -1;
        }
        $pid = file_get_contents($pid_file);
        $output->writeln(sprintf('Killing process <info>%d</info>', $pid));
        posix_kill($pid, SIGTERM);
        $fs->remove($pid_file);
        return 0;
    }

}