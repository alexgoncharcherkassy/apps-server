<?php

namespace Ekreative\TestBuild\CoreBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class EkreativeTestBuildCoreExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        if (!isset($config['amazon_s3']['aws_key'])) {
            throw new \InvalidArgumentException('The option "ekreative_test_build_core.amazon_s3.aws_key" must be set.');
        }
        $container->setParameter('ekreative_test_build_core.aws_key', $config['amazon_s3']['aws_key']);

        if (!isset($config['amazon_s3']['aws_secret_key'])) {
            throw new \InvalidArgumentException('The option "ekreative_test_build_core.amazon_s3.aws_secret_key" must be set.');
        }
        $container->setParameter('ekreative_test_build_core.aws_secret', $config['amazon_s3']['aws_secret_key']);

        if (!isset($config['amazon_s3']['aws_region'])) {
            throw new \InvalidArgumentException('The option "ekreative_test_build_core.amazon_s3.aws_region" must be set.');
        }
        $container->setParameter('ekreative_test_build_core.aws_region', $config['amazon_s3']['aws_region']);

        if (!isset($config['amazon_s3']['base_url'])) {
            throw new \InvalidArgumentException('The option "ekreative_test_build_core.amazon_s3.base_url" must be set.');
        }
        $container->setParameter('ekreative_test_build_core.amazon_s3.base_url', $config['amazon_s3']['base_url']);

        $container->setParameter('ekreative_test_build_core.amazon_s3.bucket', $config['amazon_s3']['bucket']);
    }
}
