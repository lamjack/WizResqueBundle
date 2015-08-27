<?php
/**
 * Author: jack<linjue@wilead.com>
 * Date: 15/7/6
 */

namespace Wiz\ResqueBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\PropertyAccess\PropertyAccess;

class WizResqueExtension extends Extension
{
    /**
     * Loads a specific configuration.
     *
     * @param array $configs An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     *
     * @api
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $pa = PropertyAccess::createPropertyAccessor();
        if (!is_null($config['prefix'])) {
            $container->setParameter('wiz_resque.resque.prefix', $pa->getValue($config, '[prefix]') . ':resque');
        }

        $vendor_dir = $container->getParameter('kernel.root_dir') . '/../vendor';

        $container->setParameter('wiz_resque.resque.redis.host', $pa->getValue($config, '[redis][host]'));
        $container->setParameter('wiz_resque.resque.redis.port', $pa->getValue($config, '[redis][port]'));
        $container->setParameter('wiz_resque.resque.redis.database', $pa->getValue($config, '[redis][database]'));
        $container->setParameter('wiz_resque.vendor_dir', $vendor_dir);
        $container->setParameter('wiz_resque.resque.kernel_options', array(
            'kernel.root_dir' => $container->getParameter('kernel.root_dir'),
            'kernel.debug' => $container->getParameter('kernel.debug'),
            'kernel.environment' => $container->getParameter('kernel.environment')
        ));
    }
}