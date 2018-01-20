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

$app->get('/zamowienia/{rows}/{customer}', function ($rows,$customer_id) use ($app) {
    $sql_zamowienia = "select 
      tbl_orders.o_id , 
      tbl_orders.o_name, 
      tbl_orders.o_datetime ,
      (SELECT tbl_customer_address.a_name from tbl_customer_address where tbl_customer_address.a_id = o_c_id_shipto ) as  o_shipto,
      tbl_orders.o_c_id_shipto,
      (SELECT CONCAT(tbl_customers.c_name, \" \" ,tbl_customers.c_surname) from tbl_customers where tbl_customers.c_id = tbl_orders.o_c_id) as o_customer,
      tbl_orders.o_c_id,
      tbl_orders.o_f_id
 
      from tbl_orders where tbl_orders.o_c_id = ? LIMIT ?;
      ";



    if ( (!is_null($rows)) and $rows < 0){
        $rows = 10;
    }else{
       // assert("Zamówienia : Brak zmiennej ilosci wierszy lub zmienna posiada wartość ujemną");
    }

    $results = $app['dbs']['mysql_read']->fetchAll($sql_zamowienia, array ((int) $rows , (int) $customer_id)  );

    return $app['twig']->render('zamowienia.html.twig', array('orders' => $results));
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

$app->get('/user/{id}', function ($id) use ($app) {
    $sql = "SELECT * FROM tbl_customers WHERE c_id = ?";
    $post = $app['dbs']['mysql_read']->fetchAssoc($sql, array((int) $id));

    //$sql = "UPDATE posts SET value = ? WHERE id = ?";
    //$app['dbs']['mysql_write']->executeUpdate($sql, array('newValue', (int) $id));

    return  "<h1>{$post['c_name']}</h1>".
        "<p>{$post['c_phone']}</p>";
});

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
