<?php

namespace Campru\GuzzleBundle\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;


/**
 * Web Profiler provider for guzzle.
 *
 * @author David Camprubí <david.camprubi@gmail.com>
 */
class GuzzleProfilerServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given app.
     *
     * @param Application $app Silex application.
     */
    public function register(Container $app)
    {
        if (!isset($app['data_collector.templates'])) {
            throw new \LogicException(
                'The provider: "'.__CLASS__.'" must be registered after the "WebProfilerServiceProvider"'
            );
        }

        $app['data_collector.templates'] = $app->extend('data_collector.templates', function ($tpls) {
            $tpls[] = ['guzzle', '@CampruGuzzle/views/Collector/guzzle.html.twig'];

            return $tpls;
        });

        $app['guzzle_bundle.subscriber.profiler'] = function () {
            return new \GuzzleHttp\Subscriber\History;
        };

        $app['guzzle_bundle.subscriber.storage'] = function () {
            return new \Campru\GuzzleBundle\Subscriber\Stopwatch(new \SplObjectStorage);
        };

        $app['data_collectors'] = $app->extend('data_collectors', function ($collectors, $app) {
            $collectors['guzzle'] = function ($app) {
                return new \Campru\GuzzleBundle\DataCollector\GuzzleDataCollector(
                    $app['guzzle_bundle.subscriber.profiler'],
                    $app['guzzle_bundle.subscriber.storage']
                );
            };

            return $collectors;
        });

        $app['guzzle_bundle.profiler.templates_path'] = function () {
            $class = new \ReflectionClass('Campru\GuzzleBundle\DataCollector\GuzzleDataCollector');

            return dirname(dirname($class->getFileName())).'/../resources';
        };

        $app['twig.loader.filesystem'] = $app->extend('twig.loader.filesystem', function ($loader, $app) {
            $loader->addPath($app['guzzle_bundle.profiler.templates_path'], 'CampruGuzzle');

            return $loader;
        });
    }
}
