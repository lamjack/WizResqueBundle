<?php
/**
 * Author: jack<linjue@wilead.com>
 * Date: 15/7/6
 */

namespace Wiz\ResqueBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 * @package Wiz\ResqueBundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $tree_builder = new TreeBuilder();
        $root = $tree_builder->root('wiz_resque');

        $root
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('prefix')
                    ->isRequired()
                ->end()
                ->arrayNode('redis')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('host')
                            ->defaultValue('127.0.0.1')
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('port')
                            ->defaultValue(6379)
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('database')
                            ->defaultValue(0)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('auto_retry')
                    ->beforeNormalization()
                        ->ifArray()
                        ->then(function($config) {
                            if (array_key_exists(0, $config)) {
                                return array($config);
                            }
                            return $config;
                        })
                    ->end()
                    ->prototype('array')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end();

        return $tree_builder;
    }
}