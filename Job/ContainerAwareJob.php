<?php
/**
 * Author: jack<linjue@wilead.com>
 * Date: 15/7/6
 */

namespace Wiz\ResqueBundle\Job;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class ContainerAwareJob
 * @package Wiz\ResqueBundle\Job
 */
abstract class ContainerAwareJob extends Job
{
    /**
     * @var KernelInterface|null
     */
    private $kernel = null;

    /**
     * @param array $kernel_options
     */
    public function setKernelOptions(array $kernel_options)
    {
        $this->args = array_merge($this->args, $kernel_options);
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected function getContainer()
    {
        if (is_null($this->kernel)) {
            $this->kernel = $this->createKernel();
            $this->kernel->boot();
        }
        return $this->kernel->getContainer();
    }

    /**
     * @return KernelInterface
     */
    private function createKernel()
    {
        $finder = new Finder();
        $finder->name('*Kernel.php')->depth(0)->in($this->args['kernel.root_dir']);
        $results = iterator_to_array($finder);
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        $file = current($results);
        $class = $file->getBasename();

        require_once $file;

        return new $class(
            isset($this->args['kernel.environment']) ? $this->args['kernel.environment'] : 'dev',
            isset($this->args['kernel.debug']) ? $this->args['kernel.debug'] : true
        );
    }
}