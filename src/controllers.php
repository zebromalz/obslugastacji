<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html.twig', array());
})
->bind('glowna')
;

$app->get('/zamowienia', function () use ($app) {
    return $app['twig']->render('zamowienia.html.twig', array());
})
    ->bind('zamowienia')
;

$app->get('/pomoc', function () use ($app) {
    return $app['twig']->render('pomoc.html.twig', array());
})
    ->bind('pomoc')
;

$app->get('/logowanie', function () use ($app) {
    return $app['twig']->render('logowanie.html.twig', array());
})
    ->bind('logowanie')
;

$app->get('/konto', function () use ($app) {
    return $app['twig']->render('konto.html.twig', array());
})
    ->bind('konto')
;

$app->get('/kontodane', function () use ($app) {
    return $app['twig']->render('kontodane.html.twig', array());
})
    ->bind('kontodane')
;

$app->get('/kontostacje', function () use ($app) {
    return $app['twig']->render('kontostacje.html.twig', array());
})
    ->bind('kontostacje')
;

$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html.twig',
        'errors/'.substr($code, 0, 2).'x.html.twig',
        'errors/'.substr($code, 0, 1).'xx.html.twig',
        'errors/default.html.twig',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});
