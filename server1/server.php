<?php

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory;
use Swoole\Coroutine\Http\Server;
use function Swoole\Coroutine\run;

require_once __DIR__ . '/vendor/autoload.php';

run(function () {

    $registry = new CollectorRegistry(new InMemory());

    $server = new Server('0.0.0.0', 9502, false);

    $server->set([
        'mode' => SWOOLE_BASE,
        'worker_num' => 1
    ]);

    $GLOBALS['gauge'] = 1;

    $server->handle('/metrics', function ($request, $response)use ($registry) {
        $registry->getOrRegisterCounter('','some_quick_counter','just a quick counter')
            ->inc();
        $registry->getOrRegisterGauge('','some_gauge','it sets',['type'])
            ->set($GLOBALS['gauge'],['blue']);
        $registry->getOrRegisterHistogram('','some_histogram','it observes',['color'],[0.1,2,3,3.5,4,5,6,7,8,9])
            ->observe(random_int(1,10),['red']);

        $render = new RenderTextFormat();
        $metrics = $render->render($registry->getMetricFamilySamples());

        $response->header('Content-Type',RenderTextFormat::MIME_TYPE);
        $response->end($metrics);
    });

    $server->handle('/reset',function ($request,$response)use ($registry){
        $GLOBALS['gauge'] = 0;
        $response->end($GLOBALS['gauge']);
    });

    $server->start();
});
