<?php
/**
 * Author: jack<linjue@wilead.com>
 * Date: 15/7/22
 */

namespace Wiz\ResqueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Wiz\ResqueBundle\Constant;

/**
 * Class StartScheduledWorkerCommand
 * @package Wiz\ResqueBundle\Command
 */
class StartScheduledWorkerCommand extends ContainerAwareCommand
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('resque:scheduledworker-start')
            ->setDescription('Start a scheduled resque worker')
            ->addOption('foreground', 'f', InputOption::VALUE_NONE, 'Should the worker run in foreground')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force creation of a new worker if the PID file exists')
            ->addOption('interval', 'i', InputOption::VALUE_REQUIRED, 'How often to check for new jobs across the queues', 5);
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface $input   An InputInterface instance
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
        $pid_file = $this->getContainer()->get('kernel')->getLogDir() . DIRECTORY_SEPARATOR . Constant::PID_FILE;

        if ($fs->exists($pid_file) && !$input->getOption('force')) {
            throw new \LogicException('PID file exists - use --force to override');
        }

        // force
        if ($fs->exists($pid_file)) {
            $fs->remove($pid_file);
        }

        // Set env
        $env = array(
            'APP_INCLUDE' => $this->getContainer()->getParameter('kernel.root_dir') . DIRECTORY_SEPARATOR . Constant::BOOTSTRAP_FILE,
            'VVERBOSE' => 1,
            'RESQUE_PHP' => $this->getContainer()->getParameter('wiz_resque.vendor_dir') . '/chrisboulton/php-resque/lib/Resque.php',
            'INTERVAL' => $input->getOption('interval')
        );

        $prefix = $this->getContainer()->getParameter('wiz_resque.resque.prefix');
        if (!empty($prefix)) {
            $env['PREFIX'] = $prefix;
        }

        $redis_host = $this->getContainer()->getParameter('wiz_resque.resque.redis.host');
        $redis_port = $this->getContainer()->getParameter('wiz_resque.resque.redis.port');
        $redis_database = $this->getContainer()->getParameter('wiz_resque.resque.redis.database');
        if (!is_null($redis_host) && !is_null($redis_port)) {
            $env['REDIS_BACKEND'] = $redis_host . ':' . $redis_port;
        }
        if (isset($redis_database)) {
            $env['REDIS_BACKEND_DB'] = $redis_database;
        }

        if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
            $php_executable = PHP_BINARY;
        } else {
            $php_executable = PHP_BINDIR . '/php';
            if (defined('PHP_WINDOWS_VERSION_BUILD')) {
                $php_executable = 'php';
            }
        }

        $bin_path = $this->getContainer()->getParameter('kernel.root_dir') . '/../bin';
        $worker_command = sprintf('%s %s', $php_executable, $bin_path . '/resque-scheduler');

        if (!$input->getOption('foreground')) {
            $logFile = $this->getContainer()->getParameter('kernel.logs_dir') . '/resque-scheduler_' . $this->getContainer()->getParameter('kernel.environment') . '.log';
            $worker_command = 'nohup ' . $worker_command . ' > ' . $logFile . ' 2>&1 & echo $!';
        }

        // In windows: When you pass an environment to CMD it replaces the old environment
        // That means we create a lot of problems with respect to user accounts and missing vars
        // this is a workaround where we add the vars to the existing environment.
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            foreach ($env as $key => $value) {
                putenv($key . "=" . $value);
            }
            $env = null;
        }

        $process = new Process($worker_command, null, $env, null, null);
        $output->writeln(sprintf('Starting worker <info>%s</info>', $process->getCommandLine()));

        if ($input->getOption('foreground')) {
            $process->run(function ($type, $buffer) use ($output) {
                $output->write($buffer);
            });
        } else {
            $process->run();
            $pid = trim($process->getOutput());
            if (function_exists('gethostname')) {
                $hostname = gethostname();
            } else {
                $hostname = php_uname('n');
            }
            $output->writeln(\sprintf('<info>Worker started</info> %s:%s', $hostname, $pid));
            file_put_contents($pid_file, $pid);
        }

        return 0;
    }
}