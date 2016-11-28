<?php
/**
 * SupervisorConfigGenerateCommand.php
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author    jack <linjue@wilead.com>
 * @copyright 2007-2016/11/22 WIZ TECHNOLOGY
 * @link      https://wizmacau.com
 * @link      https://lamjack.github.io
 * @link      https://github.com/lamjack
 * @version
 */

namespace Wiz\ResqueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Class SupervisorConfigGenerateCommand
 * @package Wiz\ResqueBundle\Command
 */
class SupervisorConfigGenerateCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('resque:generate:supervisor')
            ->setDescription('创建supervisor配置文件')
            ->addArgument('projectName', InputArgument::REQUIRED, '项目名')
            ->addArgument('queues', InputArgument::REQUIRED, '要處理的隊列名稱,多個請用,分開')
            ->addOption('user', '', InputOption::VALUE_REQUIRED, '运行用户身份', 'webuser')
            ->addOption('numprocs', null, InputOption::VALUE_REQUIRED, '进程数量', '1')
            ->addOption('interval', null, InputOption::VALUE_REQUIRED, '没有任务时候的等待时间', '3')
            ->addOption('autostart', null, InputOption::VALUE_REQUIRED, '是否随supervisor启动', 'true')
            ->addOption('autorestart', null, InputOption::VALUE_REQUIRED, '是否随supervisor重新启动', 'true');

    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $filesystem = $container->get('filesystem');
        $projectName = strtolower($input->getArgument('projectName'));
        $queueNames = explode(',', $input->getArgument('queues'));
        $numprocs = intval($input->getOption('numprocs'));
        $interval = intval($input->getOption('interval'));

        if (!is_array($queueNames)) {
            throw new InvalidArgumentException();
        }

        $projectPath = realpath($container->getParameter('kernel.root_dir') . '/..');
        $phpExecutablePath = (new PhpExecutableFinder())->find();
        $bootstrapCachePath = $container->getParameter('kernel.root_dir') . '/bootstrap.php.cache';
        $resqueCmdPath = $projectPath . '/vendor/wiz/resque-bundle/bin/resque';
        $resqueSchedulerCmdPath = $projectPath . '/vendor/wiz/resque-bundle/bin/resque-scheduler';
        $logsPath = $container->getParameter('kernel.logs_dir') . '/resque';
        $resquePrefix = sprintf('%s:resque', $container->getParameter('project_prefix'));

        // 创建日志存放路径
        if (!$filesystem->exists($logsPath))
            $filesystem->mkdir($logsPath);

        $style = new OutputFormatterStyle('green');
        $output->getFormatter()->setStyle('config', $style);

        // 通用配置
        $commonConfig = [
            '%%USER%%' => $input->getOption('user'),
            '%%LOGS_PATH%%' => $logsPath,
            '%%AUTOSTART%%' => $input->getOption('autostart'),
            '%%AUTORESTART%%' => $input->getOption('autorestart')
        ];

        // queue program部分
        $programGroup = [];
        $configContent = [];
        foreach ($queueNames as $queueName) {
            // 程序名
            $programName = sprintf('%s-queue-%s-worker', $projectName, $queueName);

            $configStr = strtr($this->getProgramSectionTemplate($numprocs), array_merge(
                $commonConfig,
                [
                    '%%PROGRAM_NAME%%' => $programName,
                    '%%COMMAND%%' => sprintf('%s %s', $phpExecutablePath, $resqueCmdPath),
                    '%%ENVIRONMENT%%' => sprintf(
                        'APP_INCLUDE=\'%s\',VERBOSE=\'1\',QUEUE=\'%s\',PREFIX=\'%s\',REDIS_HOST=\'%s\',REDIS_PORT=\'%s\',REDIS_BACKEND=\'%s\',INTERVAL=\'%d\'',
                        $bootstrapCachePath,
                        $queueName,
                        $resquePrefix,
                        $container->getParameter('redis_host'),
                        $container->getParameter('redis_port'),
                        "{$container->getParameter('redis_host')}:{$container->getParameter('redis_port')}",
                        $interval
                    )
                ]
            ));

            array_push($programGroup, $programName);
            array_push($configContent, "<config>{$configStr}</config>");
        }

        // scheduled program部分
        $scheduledProgramName = sprintf('%s-scheduled-worker', $projectName);
        $scheduledConfigStr = strtr($this->getProgramSectionTemplate(1), array_merge(
            $commonConfig,
            [
                '%%PROGRAM_NAME%%' => $scheduledProgramName,
                '%%COMMAND%%' => sprintf('%s %s', $phpExecutablePath, $resqueSchedulerCmdPath),
                '%%ENVIRONMENT%%' => sprintf(
                    'APP_INCLUDE=\'%s\',VERBOSE=\'1\',PREFIX=\'%s\',REDIS_BACKEND=\'%s\',RESQUE_PHP=\'%s\',INTERVAL=\'%d\'',
                    $bootstrapCachePath,
                    $resquePrefix,
                    "{$container->getParameter('redis_host')}:{$container->getParameter('redis_port')}",
                    $projectPath . '/vendor/chrisboulton/php-resque/lib/Resque.php',
                    $interval
                )
            ]
        ));
        array_push($programGroup, $scheduledProgramName);
        array_push($configContent, "<config>{$scheduledConfigStr}</config>");

        // Group部分
        $groupConfigStr = strtr($this->getGroupSectionTemplate(), [
            '%%GROUP_NAME%%' => sprintf('%s-resque', $projectName),
            '%%GROGRAMS%%' => implode(',', $programGroup)
        ]);
        array_push($configContent, "<config>{$groupConfigStr}</config>");

        $output->writeln($configContent);
        $output->writeln([
            '',
            "<fg=white;bg=blue>请复制配置内容至 /etc/supervisord.d/{$projectName}.conf</>",
            "<fg=white;bg=blue>并执行 supervisorctl reload</>"
        ]);
        return 0;
    }

    /**
     * 获取Program配置模板
     *
     * @param int $numprocs 进程数
     *
     * @return string
     */
    protected function getProgramSectionTemplate($numprocs = 1)
    {
        if ($numprocs > 1) {
            return <<<EOD
[program:%%PROGRAM_NAME%%]
command= %%COMMAND%%
user = %%USER%%
numprocs = {$numprocs}
process_name = "%(program_name)s-%(process_num)s"
environment = %%ENVIRONMENT%%
autostart = %%AUTOSTART%%
autorestart = %%AUTORESTART%%
loglevel = debug
redirect_stderr = true
logfile_maxbytes = 5MB
stopsignal = QUIT
stdout_logfile = %%LOGS_PATH%%/%%PROGRAM_NAME%%.log
stderr_logfile = %%LOGS_PATH%%/%%PROGRAM_NAME%%.log

EOD;
        } else {
            return <<<EOD
[program:%%PROGRAM_NAME%%]
command= %%COMMAND%%
user = %%USER%%
environment = %%ENVIRONMENT%%
autostart = %%AUTOSTART%%
autorestart = %%AUTORESTART%%
loglevel = debug
redirect_stderr = true
logfile_maxbytes = 5MB
stopsignal = QUIT
stdout_logfile = %%LOGS_PATH%%/%%PROGRAM_NAME%%.log
stderr_logfile = %%LOGS_PATH%%/%%PROGRAM_NAME%%.log

EOD;
        }
    }

    /**
     * 获取Group配置模板
     *
     * @return string
     */
    protected function getGroupSectionTemplate()
    {
        return <<<EOD
[group:%%GROUP_NAME%%]
programs = %%GROGRAMS%%
EOD;
    }
}