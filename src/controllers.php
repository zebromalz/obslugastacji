<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app->get('/', function () use ($app) {

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $user = $token->getUser();

        $q_user = "SELECT c_id , c_name from tbl_customers where c_email=:customer_email limit 1;";

        $q_customer = $app['dbs']['mysql_read']->prepare($q_user);
        $q_customer->bindValue(':customer_email', $user->getUsername(), PDO::PARAM_STR);
        $q_customer->execute();

        $customer = $q_customer->fetch();

    }else{
        return "Invalid Session";
    }

    return $app['twig']->render('index.html.twig', array('customer' => $customer['c_id'] , 'customer_name' => $customer['c_name']));
})
->bind('glowna')
;

$app->post('/zamowienia_show', function (Request $request) use ($app) {

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $user = $token->getUser();

        $q_user = "SELECT c_id , c_name from tbl_customers where c_email=:customer_email limit 1;";

        $q_customer = $app['dbs']['mysql_read']->prepare($q_user);
        $q_customer->bindValue(':customer_email', $user->getUsername(), PDO::PARAM_STR);
        $q_customer->execute();

        $customer = $q_customer->fetch();

        $customer_id = $customer['c_id'];

        // Nie podano id zamowienia , tworzenie nowego lu otwarcie juz rozpoczetego zamowienia.
        if($request->get('order_id') == NULL){

            $q_order_sql = "SELECT Count(*)as cc , o_id from tbl_orders where o_c_id = :c_id and o_c_isbasket = 1 ;";

            $open_order = $app['dbs']['mysql_read']->prepare($q_order_sql);
            $open_order->bindValue(':c_id', $customer_id, PDO::PARAM_STR);
            $open_order->execute();

            $order = $open_order->fetch();

            return $order['cc']."<=Orders ".$customer_id."<= Customer ".$order['o_id']."<=Order_id";

            assert($order['cc'] > 1, "Sprawdzenie ilosci otwartych zamownien : Uszkodzenie struktury zamowien klienta o ".$customer_id.", wiecej niz jedno otwarte zamowienie");

            if($order[cc] < 1){

                return "123";

                $app['dbs']['mysql_read']->insert('tbl_orders',array('o_c_id' => $customer_id, 'o_c_isbasket' => 1));
                $app['dbs']['mysql_read']->commit();

                $open_order = $app['dbs']['mysql_read']->prepare($q_order_sql);
                $open_order->bindValue(':c_id', $customer_id, PDO::PARAM_STR);
                $open_order->execute();

                $order = $open_order->fetch();

            }
            $order_id = $order['o_id'];

            assert($order_id <> NULL ,"Sprawdzenie poprawnosci zapisu");

        }else{
            $order_id = $request->get('order_id');
        }


        $q_ordered_items_sql = "SELECT 
	
    (SELECT product_name from tbl_products where product_id = oi_item_id ) as product_name ,
    oi_amount ,
    oi_price ,
    oi_order_id,
    (SELECT o_c_id from tbl_orders where o_id = oi_order_id  ) as order_owner_id,
    (SELECT o_c_isbasket from tbl_orders where o_id = oi_order_id) as basket,
    (SELECT product_name from tbl_products where product_id = (SELECT product_parent from tbl_products where product_id = tbl_order_items.oi_item_id ) )as product_parent,
    (SELECT concat(product_parent, \" -> \" , product_name)) as product
    
FROM obslugastacji.tbl_order_items where oi_order_id = :order_id;";

        $q_ordered_items = $app['dbs']['mysql_read']->prepare($q_ordered_items_sql);
        $q_ordered_items->bindValue(':order_id', $order_id, PDO::PARAM_STR);
        $q_ordered_items->execute();

        $items = $q_ordered_items->fetchAll();

        if(assert($customer_id <> $items[0]['order_owner_id'],
            'Sprawdzenie uprawnien : Wykryto probe nieautoryzowanego dostepu klienta '.$customer_id.' do zamownienia na koncie innego uzytkownika'))
        {
            return "Nieoczekiwany Błąd A1789";
        }

        return $app['twig']->render('zamowienia_show.html.twig', array('ordered_items' => $items));
    }else{
        return "Nieoczekiwany Błąd A1790";
    }
})
    ->bind('zamowienia_show')
;

$app->get('/zamowienia/{rows}', function ($rows) use ($app) {

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $user = $token->getUser();

    $q_user = "SELECT c_id , c_name from tbl_customers where c_email=:customer_email limit 1;";

    $q_customer = $app['dbs']['mysql_read']->prepare($q_user);
    $q_customer->bindValue(':customer_email',$user->getUsername(),PDO::PARAM_STR);
    $q_customer->execute();

    $customer = $q_customer->fetch();

    $customer_id = $customer['c_id'];

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

    $q = $app['dbs']['mysql_read']->prepare($sql_zamowienia);
    $q->bindValue(':customer_id',$customer_id,PDO::PARAM_INT);
    $q->bindValue(':rows',(int) $rows,PDO::PARAM_INT);
    $q->execute();

    $orders = $q->fetchAll();

    $sql_products = "select * from tbl_products";

    $products = buildTree($app['dbs']['mysql_read']->fetchAll($sql_products));

    $q_locations = "SELECT a_id , a_name from tbl_customer_address where a_c_id=:customer_id;";

    $q_locations = $app['dbs']['mysql_read']->prepare($q_locations);
    $q_locations->bindValue(':customer_id',$customer_id,PDO::PARAM_INT);
    $q_locations->execute();

    $locations = $q_locations->fetchAll();

    return $app['twig']->render('zamowienia.html.twig', array(
        'orders' => $orders ,
        'products' => $products ,
        'customer' => $customer_id ,
        'customer_name' => $customer['c_name'],
        'locations' => $locations

        ));
    }else{
        return "ERROR MISSING USER ID";
    }
})
    ->bind('zamowienia')
;

$app->get('/pomoc', function () use ($app) {
    return $app['twig']->render('pomoc.html.twig', array());
})
    ->bind('pomoc')
;

$app->get('/login', function (Request $request) use ($app) {
    return $app['twig']->render('logowanie.html.twig', array(
        'error'         => $app['security.last_error']($request),
        'last_username' => $app['session']->get('_security.last_username'),
    ));
})
    ->bind('login')
;

$app->get('/konto', function () use ($app) {
    return $app['twig']->render('konto.html.twig', array());
})
    ->bind('konto')
;

$app->get('/kontodane', function () use ($app) {

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $user = $token->getUser();

        $q_user = "SELECT c_id , c_name from tbl_customers where c_email=:customer_email limit 1;";

        $q_customer = $app['dbs']['mysql_read']->prepare($q_user);
        $q_customer->bindValue(':customer_email', $user->getUsername(), PDO::PARAM_STR);
        $q_customer->execute();

        $customer = $q_customer->fetch();

        $customer_id = $customer['c_id']; //dodac wczytanie id klienta ktory jest zalogowany.

        $sql_kontodane = "select c_id,c_name,c_surname,c_phone,c_email,c_registered,c_islocked,c_isactive from tbl_customers where c_id = :customer_id LIMIT 1;";

        $q_kontodane = $app['dbs']['mysql_read']->prepare($sql_kontodane);
        $q_kontodane->bindValue(':customer_id', $customer_id, PDO::PARAM_INT);
        $q_kontodane->execute();

        $sql_cards = "SELECT card_id , card_type , CONCAT(\"*****\", SUBSTRING(card_number, -4) )as card_number , card_expiry , card_active FROM tbl_cards where card_user=:customer_id;";

        $q_cards = $app['dbs']['mysql_read']->prepare($sql_cards);
        $q_cards->bindValue(':customer_id', $customer_id, PDO::PARAM_INT);
        $q_cards->execute();

        $sql_address = "SELECT * from tbl_customer_address where a_c_id=:customer_id;";

        $q_address = $app['dbs']['mysql_read']->prepare($sql_address);
        $q_address->bindValue(':customer_id', $customer_id, PDO::PARAM_INT);
        $q_address->execute();

        return $app['twig']->render('kontodane.html.twig', array('kontodane' => $q_kontodane, 'cards' => $q_cards, 'address' => $q_address,'customer' => $customer_id , 'customer_name' => $customer['c_name']));
    }else{
        return "ERROR MISSING USER ID";
    }
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
