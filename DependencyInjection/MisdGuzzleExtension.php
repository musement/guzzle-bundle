<?php

/*
 * This file is part of the Symfony2 GuzzleBundle.
 *
 * (c) University of Cambridge
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Misd\GuzzleBundle\DependencyInjection;

use Guzzle\Common\Version;
use Guzzle\Service\Builder\ServiceBuilder;
use Guzzle\Service\Command\OperationResponseParser;
use Guzzle\Service\Description\ServiceDescription;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Set up the MisdGuzzleBundle.
 *
 * @author Chris Wilkinson <chris.wilkinson@admin.cam.ac.uk>
 */
class MisdGuzzleExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('plugin.xml');
        $loader->load('log.xml');

        if ($config['serializer']) {
            $loader->load('serializer.xml');
        }

        if (interface_exists('Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface')) {
            // choose a ParamConverterInterface implementation that is compatible
            // with the version of SensioFrameworkExtraBundle being used
            $parameter = new \ReflectionParameter(
                array(
                    'Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface',
                    'supports',
                ),
                'configuration'
            );
            if ('Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter' === $parameter->getClass()->getName()) {
                $container->setParameter(
                    'misd_guzzle.param_converter.class',
                    'Misd\GuzzleBundle\Request\ParamConverter\GuzzleParamConverter3x'
                );
            } else {
                $container->setParameter(
                    'misd_guzzle.param_converter.class',
                    'Misd\GuzzleBundle\Request\ParamConverter\GuzzleParamConverter2x'
                );
            }
            $loader->load('param_converter.xml');
        }

        if ($config['service_builder']['enabled']) {
            $loader->load('service_builder.xml');
            $container->setParameter(
                'guzzle.service_builder.class',
                $config['service_builder']['class']
            );
            $container->setParameter(
                'guzzle.service_builder.configuration_file',
                $config['service_builder']['configuration_file']
            );
        }

        if ($config['filesystem_cache']['enabled']) {
            $loader->load('cache.xml');
            $container->setParameter('misd_guzzle.cache.filesystem.path', $config['filesystem_cache']['path']);
        }

        $logFormat = strtolower($config['log']['format']);
        if (in_array($logFormat, array('default', 'debug', 'short'), true)) {
            $logFormat = constant(sprintf('Guzzle\Log\MessageFormatter::%s_FORMAT', strtoupper($logFormat)));
        }
        $container->setParameter('misd_guzzle.log.format', $logFormat);
        $container->setParameter('misd_guzzle.log.enabled', $config['log']['enabled']);

        if (
            version_compare(Version::VERSION, '3.6', '>=')
            && $container->hasParameter('kernel.debug')
        ) {
            Version::$emitWarnings = $container->getParameter('kernel.debug');
        }

        $responseParserClass = OperationResponseParser::class;
        $responseParserDefinition = new Definition($responseParserClass);
        $guzzleServiceBuilderDefinition = $container->getDefinition('guzzle.service_builder');
        $guzzleServiceBuilderClass = ServiceBuilder::class;
        if (method_exists(Definition::class, 'setFactory')) {
            $responseParserDefinition->setFactory([$responseParserClass, 'getInstance']);
            $guzzleServiceBuilderDefinition->setFactory([$guzzleServiceBuilderClass, 'factory']);
        } else {
            $responseParserDefinition
                ->setClass($responseParserClass)
                ->setFactoryMethod('getInstance')
            ;
            $guzzleServiceBuilderDefinition
                ->setClass($guzzleServiceBuilderClass)
                ->setFactoryMethod('factory')
            ;
        }

        $container->set('misd_guzzle.response.parser.fallback', $responseParserDefinition);

        $responseDefinition = $container->getDefinition('misd_guzzle.response.parser');
        $responseDefinition->replaceArgument(1, $responseParserDefinition);

    }
}
