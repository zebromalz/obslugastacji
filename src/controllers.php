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

$app->get('/zamowienia/{rows}', function ( $rows) use ($app) {
    $sql_zamowienia = "select 
      tbl_orders.o_id , 
      tbl_orders.o_name, 
      tbl_orders.o_datetime ,
      (SELECT tbl_customer_address.a_name from tbl_customer_address where tbl_customer_address.a_id = o_c_id_shipto ) as  o_shipto,
      tbl_orders.o_c_id_shipto,
      (SELECT CONCAT(tbl_customers.c_name, \" \" ,tbl_customers.c_surname) from tbl_customers where tbl_customers.c_id = tbl_orders.o_c_id) as o_customer,
      tbl_orders.o_c_id,
      tbl_orders.o_f_id
 
      from tbl_orders where tbl_orders.o_c_id = :customer_id LIMIT :rows;
      ";

    $customer_id = 1; //dodac wczytanie id klienta ktory jest zalogowany.

    $q = $app['dbs']['mysql_read']->prepare($sql_zamowienia);
    $q->bindValue(':customer_id',$customer_id,PDO::PARAM_INT);
    $q->bindValue(':rows',(int) $rows,PDO::PARAM_INT);
    $q->execute();

    $orders = $q->fetchAll();

    $sql_products = "select * from tbl_products";

    $products = buildTree($app['dbs']['mysql_read']->fetchAll($sql_products));

    //error_log(print_r($products,true));

    // do zdebugowania
    //$results = $app['dbs']['mysql_read']->fetchAll($sql_zamowienia, array('customer_id' => (int) $customer_id,'rows' => (int) $rows) );

    return $app['twig']->render('zamowienia.html.twig', array('orders' => $orders , 'products' => $products));
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

    $customer_id = 1; //dodac wczytanie id klienta ktory jest zalogowany.

    $sql_kontodane = "select c_id,c_name,c_surname,c_phone,c_email,c_registered,c_islocked,c_isactive from tbl_customers where c_id = :customer_id LIMIT 1;";

    $q_kontodane = $app['dbs']['mysql_read']->prepare($sql_kontodane);
    $q_kontodane->bindValue(':customer_id',$customer_id,PDO::PARAM_INT);
    $q_kontodane->execute();

    $sql_cards = "SELECT card_id , card_type , CONCAT(\"*****\", SUBSTRING(card_number, -4) )as card_number , card_expiry , card_active FROM tbl_cards where card_user=:customer_id;";

    $q_cards = $app['dbs']['mysql_read']->prepare($sql_cards);
    $q_cards->bindValue(':customer_id',$customer_id,PDO::PARAM_INT);
    $q_cards->execute();

    $sql_address = "SELECT * from tbl_customer_address where a_c_id=:customer_id;";

    $q_address = $app['dbs']['mysql_read']->prepare($sql_address);
    $q_address->bindValue(':customer_id',$customer_id,PDO::PARAM_INT);
    $q_address->execute();

    return $app['twig']->render('kontodane.html.twig', array('kontodane' => $q_kontodane , 'cards' => $q_cards , 'address' => $q_address));
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
