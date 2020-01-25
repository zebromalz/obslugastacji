<?php

use Doctrine\DBAL\Connection;
use Service\OrdersFilter;
use Service\UsersFilter;
use Service\StatusService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Service\Helper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\User;

function generateRandomString($length = 6) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
/**
 * @param Connection $connection
 * @param User $user
 *
 * @return object|null
 * @throws \Doctrine\DBAL\DBALException
 */
function getCustomer(Connection $connection, User $user)
{
    $customer = null;

    $q_user = "SELECT c_id , c_name from tbl_customers where c_email=:customer_email limit 1;";

    $stmt = $connection->prepare($q_user);
    $stmt->execute(['customer_email' => $user->getUsername()]);

    if ($stmt->rowCount() > 0) {
        $customer = $stmt->fetch(PDO::FETCH_OBJ);
    }

    return $customer;
}
/**
 * @param Connection $connection
 * @param User $user
 * @param int $user_id
 * @param bool $isAdmin
 *
 * @return object|null
 * @throws \Doctrine\DBAL\DBALException
 */
function getCustomerId(Connection $connection, User $user, int $user_id, bool $isAdmin)
{
    
    if($isAdmin && $user_id <> -1){
        $q_user = "SELECT c_id , c_name from tbl_customers where c_id=:customer_id limit 1;";
        $q_customer = $connection->prepare($q_user);
        $q_customer->bindValue(':customer_id', $user_id, PDO::PARAM_STR);
        $q_customer->execute();
    }else{
        $q_user = "SELECT c_id , c_name from tbl_customers where c_email=:customer_email limit 1;";
        $q_customer = $connection->prepare($q_user);
        $q_customer->bindValue(':customer_email', $user->getUsername(), PDO::PARAM_STR);
        $q_customer->execute();
    }                

    $customer = $q_customer->fetch();

    return $customer['c_id'];

}

/**
 * @param Connection $connection
 * @param int $user_id
 *
 * @return object|null
 * @throws \Doctrine\DBAL\DBALException
 */
function getCustomerbyId(Connection $connection, int $user_id)
{
    $customer = null;

    $q_user = "SELECT c_id , c_name , c_surname, c_phone, c_email, c_registered, c_islocked, c_isactive, c_activationcode, c_roles  from tbl_customers where c_id=:customer_id limit 1;";

    $stmt = $connection->prepare($q_user);
    $stmt->execute(['customer_id' => $user_id]);

    if ($stmt->rowCount() > 0) {
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    return $customer;
}

/**
 * @param Connection $connection
 * @param stdClass $customer
 *
 * @return mixed|null
 * @throws \Doctrine\DBAL\DBALException
 */
function getOrderByUser(Connection $connection, \stdClass $customer, int $orderId)
{
    $orderSql = <<<SQL
            SELECT o.o_id, 
                   o.o_name, 
                   o.o_datetime, 
                   o.o_status, 
                   o.o_f_id, 
                   o.o_c_id_shipto, 
                   o.o_c_id, 
                   o.o_c_isbasket,
                   a.a_city,
                   a.a_city,
                   a.a_country,
                   a.a_country_code,
                   a.a_street_address,
                   a.a_postcode,
                   a.a_street,
                   a.a_name
            FROM tbl_orders o
            LEFT JOIN tbl_customer_address a ON a.a_id = o.o_c_id_shipto
            WHERE o.o_c_id = :customer_id
            AND o.o_id = :order_id
SQL;

    $stmt = $connection->prepare($orderSql);
    $stmt->execute(['customer_id' => $customer->c_id, 'order_id' => $orderId]);
    $order = null;

    if ($stmt->rowCount() > 0) {
        $order = $stmt->fetch(PDO::FETCH_OBJ);
    }

    return $order;
}

/**
 * @param Connection $connection
 * @param int $orderId
 *
 * @return mixed|null
 * @throws \Doctrine\DBAL\DBALException
 */
function getOrderById(Connection $connection, int $orderId)
{
    $orderSql = <<<SQL
            SELECT o.o_id, 
                   o.o_name, 
                   o.o_datetime, 
                   o.o_status, 
                   o.o_f_id, 
                   o.o_c_id_shipto, 
                   o.o_c_id, 
                   o.o_c_isbasket,
                   a.a_city,
                   a.a_city,
                   a.a_country,
                   a.a_country_code,
                   a.a_street_address,
                   a.a_postcode,
                   a.a_street,
                   a.a_name
            FROM tbl_orders o
            LEFT JOIN tbl_customer_address a ON a.a_id = o.o_c_id_shipto
            WHERE o.o_id = :order_id
SQL;

    $stmt = $connection->prepare($orderSql);
    $stmt->execute(['order_id' => $orderId]);
    $order = null;

    if ($stmt->rowCount() > 0) {
        $order = $stmt->fetch(PDO::FETCH_OBJ);
    }

    return $order;
}

/**
 * @param Connection $connection
 * @param int $orderId
 *
 * @return mixed|null
 * @throws \Doctrine\DBAL\DBALException
 */
function getUserById(Connection $connection, int $userId)
{
    $userSql = <<<SQL
            SELECT c_id, 
                   c_name, 
                   c_surname, 
                   c_secret, 
                   c_phone, 
                   c_email, 
                   c_registered, 
                   c_islocked,
                   c_isactive,
                   c_activationcode,
                   c_roles
            FROM tbl_customers
            WHERE c_id = :user_id
SQL;

    $stmt = $connection->prepare($userSql);
    $stmt->execute(['user_id' => $userId]);
    $user = null;
    

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_OBJ);
    }

    return $user;
}

$app->get(
    '/admin',
    function () use ($app) {
        /** @var TokenInterface $token */
        $token = $app['security.token_storage']->getToken();
        if (null === $token) {
            throw new AccessDeniedException();
        }
        /** @var User $user */
        $user = $token->getUser();
        /** @var Connection $connection */
        $connection = $app['dbs']['mysql_read'];

        $customer = getCustomer($connection, $user);

        return $app['twig']->render(
            'admin_panel.html.twig',
            array(
                'customer' => $customer->c_id,
                'customer_name' => $customer->c_name
            )
        );
    }
)->bind('admin_panel');

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

    assert(null !== $token, "Sprawdzenie poprawnosci sesji uzytkownia oraz jego tokenu");

    if (null !== $token) {
        $user = $token->getUser();
        $isAdmin = $app['security.authorization_checker']->isGranted('ROLE_ADMIN');

        $q_user = "SELECT c_id , c_name from tbl_customers where c_email=:customer_email limit 1;";

        $q_customer = $app['dbs']['mysql_read']->prepare($q_user);
        $q_customer->bindValue(':customer_email', $user->getUsername(), PDO::PARAM_STR);
        $q_customer->execute();

        $customer = $q_customer->fetch();

        $customer_id = $customer['c_id'];

        // Nie podano id zamowienia , tworzenie nowego lub otwarcie juz rozpoczetego zamowienia.
        if($request->get('order_id') == NULL){

            $q_order_sql = "SELECT COUNT(*) as cc, o_id from tbl_orders where o_c_id = :c_id and o_c_isbasket = 1 GROUP BY o_id;";

            /** @var PDOStatement $open_order */
            $open_order = $app['dbs']['mysql_read']->prepare($q_order_sql);
            $open_order->bindValue(':c_id', $customer_id, PDO::PARAM_STR);
            $open_order->execute();

            if ($open_order->rowCount() > 0) {
                $order = $open_order->fetch();
            } else {
                $order['cc'] = 0;
            }

            //return $order['cc']."<=Orders ".$customer_id."<= Customer ".$order['o_id']."<=Order_id";

            assert($order['cc'] <= 1, "Sprawdzenie ilosci otwartych zamownien : Uszkodzenie struktury zamowien klienta o ".$customer_id.", wiecej niz jedno otwarte zamowienie");

            if($order['cc'] === 0){

                $app['dbs']['mysql_read']->insert('tbl_orders',array('o_c_id' => $customer_id, 'o_c_isbasket' => 1));

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
    oi_id,
    oi_amount ,
    oi_price ,
    oi_order_id,
       oi_item_id,
    (SELECT o_c_id from tbl_orders where o_id = :order_id  ) as order_owner_id,
    (SELECT o_c_isbasket from tbl_orders where o_id = oi_order_id) as basket,
    (SELECT product_name from tbl_products where product_id = (SELECT product_parent from tbl_products where product_id = tbl_order_items.oi_item_id ) )as product_parent,
    (SELECT concat(product_parent, \" -> \" , product_name)) as product
    FROM tbl_order_items where oi_order_id = :order_id;";

        $q_ordered_items = $app['dbs']['mysql_read']->prepare($q_ordered_items_sql);
        $q_ordered_items->bindValue(':order_id', $order_id, PDO::PARAM_STR);
        $q_ordered_items->execute();

        $items = $q_ordered_items->fetchAll();

        if (!empty($items)) {
            assert(
                $customer_id === $items[0]['order_owner_id'] || $isAdmin,
                'Sprawdzenie uprawnien : Wykryto probe nieautoryzowanego dostepu klienta o id' . $customer_id . ' do zamownienia na koncie innego uzytkownika'
            );
        }

        if (!$isAdmin && count($items) > 0 && $customer_id <> $items[0]['order_owner_id']) {
            return "Nieoczekiwany Błąd A1789";
        }

        return $app['twig']->render('zamowienia_show.html.twig',
            array(
                'ordered_items' => $items,
                'readonly' => $request->get('readonly')
            ));
    }else{
        return "Nieoczekiwany Błąd A1790";
    }

})
    ->bind('zamowienia_show')
;

$app->post('/product_show', function (Request $request) use ($app) {

    $token = $app['security.token_storage']->getToken();

    assert(null !== $token, "Sprawdzenie poprawnosci sesji uzytkownia oraz jego tokenu");

    if (null !== $token) {
        $user = $token->getUser();
        $isAdmin = $app['security.authorization_checker']->isGranted('ROLE_ADMIN');

        $q_user = "SELECT c_id , c_name from tbl_customers where c_email=:customer_email limit 1;";

        $q_customer = $app['dbs']['mysql_read']->prepare($q_user);
        $q_customer->bindValue(':customer_email', $user->getUsername(), PDO::PARAM_STR);
        $q_customer->execute();

        $customer = $q_customer->fetch();

        $customer_id = $customer['c_id'];

        #TODO: add check if if is new product or just edit of sth.
        
        $product_id = $request->get('product_id');
        $product_type = $request->get('product_type');
        
        ##### ACTIONS
        # - 0 new item
        # - 1 edit
        # - 2 delete
        
        $product_action = $request->get('product_action');
        if(is_null($product_action)){
            $product_action = 0; #new item
        }
        
        if($product_action != 0){
            $q_product = "SELECT product_id , product_name , product_desc, product_base_value , product_base_size , product_is_category , product_parent , (SELECT count(1) from tbl_products tp  where tp.product_parent = products.product_id) as children from tbl_products products where product_id = :p_id;";
            $q_product_items = $app['dbs']['mysql_read']->prepare($q_product);
            $q_product_items->bindValue(':p_id', $product_id, PDO::PARAM_STR);
            $q_product_items->execute();

            $product = $q_product_items->fetchAll();

        }elseif( $product_action == 0 ){
            $product_parent = $product_id;
        }
        #Delete product
        if ($product_action == 2) {

            $q_product_del = "update tbl_products set product_is_active = 0 where product_id = :p_id;";
            $q_product_del_item = $app['dbs']['mysql_read']->prepare($q_product_del);
            $q_product_del_item->bindValue(':p_id', $product_id, PDO::PARAM_STR);
            $q_product_del_item->execute();

        }

        if ($product_action == 1 && $request->get('product_action_source') == 1){
            $q_product_update = "update tbl_products set 
                                    product_is_category=:product_is_category,
                                    product_name=:product_name,
                                    product_desc=:product_desc,
                                    product_base_value=:product_base_value,
                                    product_base_size=:product_base_size
                                 where product_id=:product_id ;";
            $q_product_update_item = $app['dbs']['mysql_read']->prepare($q_product_update);
            $q_product_update_item->bindValue(':product_id', $request->get('product_id'), PDO::PARAM_STR);
            $q_product_update_item->bindValue(':product_is_category', $request->get('product_is_category'), PDO::PARAM_STR);
            $q_product_update_item->bindValue(':product_name', $request->get('product_name'), PDO::PARAM_STR);
            $q_product_update_item->bindValue(':product_desc', $request->get('product_desc'), PDO::PARAM_STR);
            $q_product_update_item->bindValue(':product_base_value', $request->get('product_base_value'), PDO::PARAM_STR);
            $q_product_update_item->bindValue(':product_base_size', $request->get('product_base_size'), PDO::PARAM_STR);
            $q_product_update_item->execute();
        }elseif($product_id == -1 && $product_action == 0){
            $q_product_add = "insert into tbl_products values (null,:product_parent,:product_is_category,:product_name,:product_desc,1,current_timestamp(),:product_base_value,:product_base_size);";
            $q_product_add_item = $app['dbs']['mysql_read']->prepare($q_product_add);
            $q_product_add_item->bindValue(':product_parent', $request->get('parent_id'), PDO::PARAM_STR);
            $q_product_add_item->bindValue(':product_is_category', $request->get('product_is_category'), PDO::PARAM_STR);
            $q_product_add_item->bindValue(':product_name', $request->get('product_name'), PDO::PARAM_STR);
            $q_product_add_item->bindValue(':product_desc', $request->get('product_desc'), PDO::PARAM_STR);
            $q_product_add_item->bindValue(':product_base_value', $request->get('product_base_value'), PDO::PARAM_STR);
            $q_product_add_item->bindValue(':product_base_size', $request->get('product_base_size'), PDO::PARAM_STR);
            $q_product_add_item->execute();
        }

        return $app['twig']->render('produkt.html.twig',
            array(
                'product' => $product['0'],
                'product_action' => $product_action,
                'product_parent' => $product_parent 
            ));
    }else{
        return "Nieoczekiwany Błąd A1790";
    }

})
    ->bind('product_show')
;

$app->get(
    '/zamowienie/{id}/show',
    function ($id) use ($app) {
        /** @var TokenInterface $token */
        $token = $app['security.token_storage']->getToken();
        if (null === $token) {
            throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
        }
        /** @var StatusService $statusService */
        $statusService = $app['service.status'];

        /** @var User $user */
        $user = $token->getUser();
        /** @var Connection $connection */
        $connection = $app['dbs']['mysql_read'];

        $customer = getCustomer($connection, $user);

        $possibleStatus = [];
        if ($app['security.authorization_checker']->isGranted('ROLE_ADMIN')) {
            $order = getOrderById($connection, $id);
            $possibleStatus = $statusService->getPossibleStatusesForOrder($order);
        } else {
            $order = getOrderByUser($connection, $customer, $id);
        }

        if (null === $order) {
            return new Response('Access Denied', Response::HTTP_FORBIDDEN);
        }

        return $app['twig']->render(
            'zamowienie_podglad.html.twig',
            [
                'customer_name' => $customer->c_name,
                'customer' => $customer->c_id,
                'order' => $order,
                'possibleStatuses' => $possibleStatus,
            ]
        );
    }
)->bind('zamowienie_podglad');

$app->post(
    'zamowienie/status',
    function (Request $request) use ($app) {
        /** @var TokenInterface $token */
        $token = $app['security.token_storage']->getToken();
        if (null === $token) {
            throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
        }

        if ($app['security.authorization_checker']->isGranted('ROLE_ADMIN')) {
            /** @var StatusService $statusService */
            $statusService = $app['service.status'];

            $orderId = $request->request->get('orderId');
            $status = $request->request->get('status');

            /** @var Connection $connection */
            $connection = $app['dbs']['mysql_read'];
            $order = getOrderById($connection, $orderId);

            /** @var Connection $connection */
            $connection = $app['dbs']['mysql_read'];

            $possibleStatuses = $statusService->getPossibleStatusesForOrder($order);
            if (in_array($status, $possibleStatuses)) {
                $connection->update('tbl_orders', ['o_status' => $status], ['o_id' => $orderId]);
            }

            return $app->redirect(
                $app["url_generator"]->generate("zamowienie_podglad", ['id' => $orderId])
            );
        } else {
            return new Response("Access Denied", Response::HTTP_FORBIDDEN);
        }
    }
)->bind('change_status');

$app->post(
    'uzytkownik/status',
    function (Request $request) use ($app) {
        /** @var TokenInterface $token */
        $token = $app['security.token_storage']->getToken();
        if (null === $token) {
            throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
        }

        if ($app['security.authorization_checker']->isGranted('ROLE_ADMIN')) {
            /** @var StatusService $statusService */
            $statusService = $app['service.status'];

            $user_isactive = $request->request->get('user_isactive');
            $user_islocked = $request->request->get('user_islocked');
            $user_id = $request->request->get('user_id');

            /** @var Connection $connection */
            $connection = $app['dbs']['mysql_read'];
            $user = getUserById($connection, $user_id);

            /** @var Connection $connection */
            $connection = $app['dbs']['mysql_read'];

            $possiblestatusesLock = $statusService->getPossibleStatusesForUserLock($user);
            $possiblestatusesActivation = $statusService->getPossibleStatusesForUserActivation($user);

            $app['monolog']->error("UserActive/Locked/userid[nullActive][nullLocked] $user_isactive/$user_islocked/$user_id [".empty($user_isactive)."][".empty($user_locked)."]");

            if (!is_null($user_isactive) && in_array($user_isactive, $possiblestatusesActivation)) {
                $connection->update('tbl_customers', ['c_isactive' => $user_isactive], ['c_id' => $user_id]);
                $app['monolog']->info("LOCK ".$user->c_isactive."/$user_isactive");
            }
            if ( !is_null($user_islocked) && in_array($user_islocked, $possiblestatusesLock)) {
                $connection->update('tbl_customers', ['c_islocked' => $user_islocked], ['c_id' => $user_id]);
                $app['monolog']->info("ACTIVATION ".$user->c_islocked."/$user_islocked");
            }

            return $app->redirect(
                $app["url_generator"]->generate("kontodane_admin", ['user_id' => $user_id])
            );
        } else {
            return new Response("Access Denied", Response::HTTP_FORBIDDEN);
        }
    }
)->bind('change_status');

$app->get('/uzytkownicy', function (Request $request) use ($app) {
    

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $isAdmin = $app['security.authorization_checker']->isGranted('ROLE_ADMIN');
        $queryData = $request->query->all();
        $offset = $request->query->has('offset') ? $request->query->get('offset') : 1;
        $filter = new UsersFilter('tbl_customers', $app['user.rows']);
        $filter->bindFilter($queryData);
        $filter->setPage($offset);

        $user = $token->getUser();

        $q_user = "SELECT c_id , c_name FROM tbl_customers WHERE c_email=:customer_email LIMIT 1;";

        $q_customer = $app['dbs']['mysql_read']->prepare($q_user);
        $q_customer->bindValue(':customer_email', $user->getUsername(), PDO::PARAM_STR);
        $q_customer->execute();

        $customer = $q_customer->fetch();

        $customer_id = $customer['c_id'];

        $usersCountSql = "SELECT COUNT(1) AS cnt FROM tbl_customers";
        $parameters = [];

        #if (!$isAdmin) {
        #    $ordersCountSql .= ' WHERE tbl_orders.o_c_id = :customer_id';
        #    $parameters = array_merge($parameters, ['customer_id' => $customer_id]);
        #}

        if ($filter->getFilters()) {
            $usersCountSql .=  ($isAdmin ? ' WHERE ' : ' AND ') . $filter->getSql();
        }

        /** @var Connection $db */
        $db = $app['dbs']['mysql_read'];
        $stmt = $db->prepare($usersCountSql);
        $stmt->execute(array_merge($parameters, $filter->getFilterValues()));
        $usersCount = $stmt->fetchColumn();
        $filter->setItemsCount($usersCount);
        $sql_uzytkownicy = "SELECT 
          c_id ,
          c_name,
          c_surname,
          c_email,
          c_registered,
          c_islocked,
          c_isactive,
          c_roles
          FROM tbl_customers";

        if ($filter->getFilters()) {
            $sql_uzytkownicy .=  ($isAdmin ? ' WHERE ' : ' AND ') . $filter->getSql();
        }

        $sql_uzytkownicy .= $filter->getLimitSql();

        /** @var \Doctrine\DBAL\Statement $q */
        $q = $app['dbs']['mysql_read']->prepare($sql_uzytkownicy);
        $q->execute(
            array_merge(
                $parameters,
                $filter->getFilterValues()
            )
        );
        #$usersCount = $q->fetchColumn();
        #$filter->setItemsCount($usersCount);
        $users = $q->fetchAll();
        $q_locations = [];

   
        return $app['twig']->render(
            'uzytkownicy.html.twig',
            array(
                'users' => $users,
                'customer' => $customer_id,
                'customer_name' => $customer['c_name'],
                'filter' => $filter
            )
        );
    } else {
        return "ERROR MISSING USER ID";
    }
})->bind('uzytkownicy');

$app->get(
    '/uzytkownicy/{user_id}/show',
    function ($user_id) use ($app) {
        /** @var TokenInterface $token */
        $token = $app['security.token_storage']->getToken();
        if (null === $token) {
            throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
        }
        /** @var StatusService $statusService */
        $statusService = $app['service.status'];

        /** @var User $user */
        $user = $token->getUser();
        /** @var Connection $connection */
        $connection = $app['dbs']['mysql_read'];

        $customer = getCustomerbyId($connection, $user_id);

        $possibleStatusLocked = [];
        $possibleStatusActive = [];
        if ($app['security.authorization_checker']->isGranted('ROLE_ADMIN')) {
            $user_query = getUserById($connection, $user_id);
            $possibleStatusLocked = $statusService->getPossibleStatusesForUserLock($user_query);
            $possibleStatusActive = $statusService->getPossibleStatusesForUserActivation($user_query);
        } else {
            $user_query = null;
        }

        if (null === $user_query) {
            return new Response('Access Denied', Response::HTTP_FORBIDDEN);
        }

        

        return $app['twig']->render(
            'uzytkownicy_podglad.html.twig',
            [
                'user' => $customer,
                'possibleStatusesLock' => $possibleStatusLocked,
                'possibleStatusesActive' => $possibleStatusActive,
            ]
        );
    }
)->bind('uzytkownicy_podglad');

$app->get('/zamowienia', function (Request $request) use ($app) {
    

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $isAdmin = $app['security.authorization_checker']->isGranted('ROLE_ADMIN');
        $queryData = $request->query->all();
        $offset = $request->query->has('offset') ? $request->query->get('offset') : 1;
        $filter = new OrdersFilter('tbl_orders', $app['zamowienia.rows']);
        $filter->bindFilter($queryData);
        $filter->setPage($offset);

        $user = $token->getUser();

        $q_user = "SELECT c_id , c_name FROM tbl_customers WHERE c_email=:customer_email LIMIT 1;";

        $q_customer = $app['dbs']['mysql_read']->prepare($q_user);
        $q_customer->bindValue(':customer_email', $user->getUsername(), PDO::PARAM_STR);
        $q_customer->execute();

        $customer = $q_customer->fetch();

        $customer_id = $customer['c_id'];

        $ordersCountSql = "SELECT COUNT(1) AS cnt FROM tbl_orders";
        $parameters = [];

        if (!$isAdmin) {
            $ordersCountSql .= ' WHERE tbl_orders.o_c_id = :customer_id';
            $parameters = array_merge($parameters, ['customer_id' => $customer_id]);
        }

        if ($filter->getFilters()) {
            $ordersCountSql .=  ($isAdmin ? ' WHERE ' : ' AND ') . $filter->getSql();
        }

        /** @var Connection $db */
        $db = $app['dbs']['mysql_read'];
        $stmt = $db->prepare($ordersCountSql);
        $stmt->execute(array_merge($parameters, $filter->getFilterValues()));
        $ordersCount = $stmt->fetchColumn();

        $filter->setItemsCount($ordersCount);

        $sql_zamowienia = "SELECT 
          tbl_orders.o_id , 
          tbl_orders.o_name, 
          tbl_orders.o_datetime ,
          tbl_orders.o_status ,
          (SELECT tbl_customer_address.a_name FROM tbl_customer_address WHERE tbl_customer_address.a_id = o_c_id_shipto ) AS  o_shipto,
          tbl_orders.o_c_id_shipto,
          (SELECT CONCAT(tbl_customers.c_name, \" \" ,tbl_customers.c_surname) FROM tbl_customers WHERE tbl_customers.c_id = tbl_orders.o_c_id) AS o_customer,
          tbl_orders.o_c_id,
          tbl_orders.o_f_id
          FROM tbl_orders";

        if (!$isAdmin) {
            $sql_zamowienia .= ' WHERE tbl_orders.o_c_id = :customer_id';
        }

        if ($filter->getFilters()) {
            $sql_zamowienia .=  ($isAdmin ? ' WHERE ' : ' AND ') . $filter->getSql();
        }

        $sql_zamowienia .= $filter->getLimitSql();

        /** @var \Doctrine\DBAL\Statement $q */
        $q = $app['dbs']['mysql_read']->prepare($sql_zamowienia);
        $q->execute(
            array_merge(
                $parameters,
                $filter->getFilterValues()
            )
        );

        $orders = $q->fetchAll();

        $sql_products = "SELECT * , (SELECT count(1) from tbl_products tp  where tp.product_parent = products.product_id) as childrencount from tbl_products products";

        $products = Helper::buildTree($app['dbs']['mysql_read']->fetchAll($sql_products));

        $q_locations = "SELECT a_id , a_name FROM tbl_customer_address WHERE a_c_id=:customer_id;";

        $q_locations = $app['dbs']['mysql_read']->prepare($q_locations);
        $q_locations->bindValue(':customer_id', $customer_id, PDO::PARAM_INT);
        $q_locations->execute();

        $locations = $q_locations->fetchAll();

        return $app['twig']->render(
            'zamowienia.html.twig',
            array(
                'orders' => $orders,
                'products' => $products,
                'customer' => $customer_id,
                'customer_name' => $customer['c_name'],
                'locations' => $locations,
                'filter' => $filter
            )
        );
    } else {
        return "ERROR MISSING USER ID";
    }
})->bind('zamowienia');

$app->get('/produkty', function (Request $request) use ($app) {
    

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $isAdmin = $app['security.authorization_checker']->isGranted('ROLE_ADMIN');
        $queryData = $request->query->all();
        $offset = $request->query->has('offset') ? $request->query->get('offset') : 1;
        $filter = new OrdersFilter('tbl_orders', $app['zamowienia.rows']);
        $filter->bindFilter($queryData);
        $filter->setPage($offset);

        $user = $token->getUser();

        $q_user = "SELECT c_id , c_name FROM tbl_customers WHERE c_email=:customer_email LIMIT 1;";

        $q_customer = $app['dbs']['mysql_read']->prepare($q_user);
        $q_customer->bindValue(':customer_email', $user->getUsername(), PDO::PARAM_STR);
        $q_customer->execute();

        $customer = $q_customer->fetch();

        $customer_id = $customer['c_id'];

        $ordersCountSql = "SELECT COUNT(1) AS cnt FROM tbl_orders";
        $parameters = [];

        if (!$isAdmin) {
            $ordersCountSql .= ' WHERE tbl_orders.o_c_id = :customer_id';
            $parameters = array_merge($parameters, ['customer_id' => $customer_id]);
        }

        if ($filter->getFilters()) {
            $ordersCountSql .=  ($isAdmin ? ' WHERE ' : ' AND ') . $filter->getSql();
        }

        /** @var Connection $db */
        $db = $app['dbs']['mysql_read'];
        $stmt = $db->prepare($ordersCountSql);
        $stmt->execute(array_merge($parameters, $filter->getFilterValues()));
        $ordersCount = $stmt->fetchColumn();

        $filter->setItemsCount($ordersCount);

        $sql_zamowienia = "SELECT 
          tbl_orders.o_id , 
          tbl_orders.o_name, 
          tbl_orders.o_datetime ,
          tbl_orders.o_status ,
          (SELECT tbl_customer_address.a_name FROM tbl_customer_address WHERE tbl_customer_address.a_id = o_c_id_shipto ) AS  o_shipto,
          tbl_orders.o_c_id_shipto,
          (SELECT CONCAT(tbl_customers.c_name, \" \" ,tbl_customers.c_surname) FROM tbl_customers WHERE tbl_customers.c_id = tbl_orders.o_c_id) AS o_customer,
          tbl_orders.o_c_id,
          tbl_orders.o_f_id
          FROM tbl_orders";

        if (!$isAdmin) {
            $sql_zamowienia .= ' WHERE tbl_orders.o_c_id = :customer_id';
        }

        if ($filter->getFilters()) {
            $sql_zamowienia .=  ($isAdmin ? ' WHERE ' : ' AND ') . $filter->getSql();
        }

        $sql_zamowienia .= $filter->getLimitSql();

        /** @var \Doctrine\DBAL\Statement $q */
        $q = $app['dbs']['mysql_read']->prepare($sql_zamowienia);
        $q->execute(
            array_merge(
                $parameters,
                $filter->getFilterValues()
            )
        );

        $orders = $q->fetchAll();

        $sql_products = "SELECT * , (SELECT count(1) from tbl_products tp  where tp.product_parent = products.product_id) as childrencount from tbl_products products";

        $products = Helper::buildTree($app['dbs']['mysql_read']->fetchAll($sql_products));

        $q_locations = "SELECT a_id , a_name FROM tbl_customer_address WHERE a_c_id=:customer_id;";

        $q_locations = $app['dbs']['mysql_read']->prepare($q_locations);
        $q_locations->bindValue(':customer_id', $customer_id, PDO::PARAM_INT);
        $q_locations->execute();

        $locations = $q_locations->fetchAll();

        return $app['twig']->render(
            'produkty.html.twig',
            array(
                'orders' => $orders,
                'products' => $products,
                'customer' => $customer_id,
                'customer_name' => $customer['c_name'],
                'locations' => $locations,
                'filter' => $filter
            )
        );
    } else {
        return "ERROR MISSING USER ID";
    }
})->bind('produkty');

$app->get('/after_login', function (Request $request) use ($app) {
    

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $isAdmin = $app['security.authorization_checker']->isGranted('ROLE_ADMIN');
        $queryData = $request->query->all();
        $offset = $request->query->has('offset') ? $request->query->get('offset') : 1;
        $filter = new OrdersFilter('tbl_orders', $app['zamowienia.rows']);
        $filter->bindFilter($queryData);
        $filter->setPage($offset);

        $user = $token->getUser();

        $q_user = "SELECT c_id , c_name , c_isactive FROM tbl_customers WHERE c_email=:customer_email LIMIT 1;";

        $q_customer = $app['dbs']['mysql_read']->prepare($q_user);
        $q_customer->bindValue(':customer_email', $user->getUsername(), PDO::PARAM_STR);
        $q_customer->execute();

        $customer = $q_customer->fetch();

        $customer_id = $customer['c_id'];
        $customer_activated = $customer['c_isactive'];

        if($customer_activated){
            return $app->redirect($app['url_generator']->generate('zamowienia'));
        }else{
            return $app->redirect($app['url_generator']->generate('activate_form'));
        }
        
    } else {
        return "ERROR MISSING USER ID";
    }
})->bind('after_login');

$app->get('/activate_form', function (Request $request) use ($app) {
    

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $isAdmin = $app['security.authorization_checker']->isGranted('ROLE_ADMIN');
        $queryData = $request->query->all();
        $offset = $request->query->has('offset') ? $request->query->get('offset') : 1;
        $filter = new OrdersFilter('tbl_orders', $app['zamowienia.rows']);
        $filter->bindFilter($queryData);
        $filter->setPage($offset);

        $user = $token->getUser();

        $q_user = "SELECT c_id , c_name , c_isactive , c_activationcode FROM tbl_customers WHERE c_email=:customer_email LIMIT 1;";

        $q_customer = $app['dbs']['mysql_read']->prepare($q_user);
        $q_customer->bindValue(':customer_email', $user->getUsername(), PDO::PARAM_STR);
        $q_customer->execute();

        $customer = $q_customer->fetch();

        $customer_id = $customer['c_id'];
        $customer_activated = $customer['c_isactive'];

        if($customer_activated){
            return $app->redirect($app['url_generator']->generate('zamowienia'));
        }else{
            return $app['twig']->render('activate_form.html.twig', array('customer' => $customer_id , 'customer_name' => $customer['c_name']));
        }
        
    } else {
        return "ERROR MISSING USER ID";
    }
})->bind('activate_form');

$app->post('/activate', function (Request $request) use ($app) {

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $user = $token->getUser();
        $isAdmin = $app['security.authorization_checker']->isGranted('ROLE_ADMIN');
        $connection = $app['dbs']['mysql_read'];     
        $customer_id = getCustomerId($app['dbs']['mysql_read'], $user, -1 , false);

        $q_user = "SELECT c_id , c_name, c_activationcode from tbl_customers where c_id=:customer_id limit 1;";

        $q_customer = $app['dbs']['mysql_read']->prepare($q_user);
        $q_customer->bindValue(':customer_id', $customer_id, PDO::PARAM_STR);
        $q_customer->execute();

        $customer = $q_customer->fetch();
        $activation_code_sent = $request->get('activation_code');
        $activation_code_user = $customer['c_activationcode'];
        if(strcasecmp($activation_code_sent,$activation_code_user) == 0){
            $q_user_activate = "UPDATE tbl_customers SET c_isactive = 1 where c_id = :customer_id;";
            $q_user_activate = $app['dbs']['mysql_read']->prepare($q_user_activate);
            $q_user_activate->bindValue(':customer_id', $customer_id, PDO::PARAM_STR);
            $q_user_activate->execute();
            return $app->redirect($app['url_generator']->generate('zamowienia'));
        }else{
            return $app['twig']->render('activate_form.html.twig', array('customer' => $customer_id , 'customer_name' => $customer['c_name'], 'activate_error' => "true"));
        }

        

    }else{
        return "ERROR MISSING USER ID";
    }
    if($isAdmin && ($logged_in_id <> $customer_id)){
        return $app->redirect($app['url_generator']->generate('kontodane_admin', array('user_id' => $customer_id )));
    }else{
        return $app->redirect($app['url_generator']->generate('kontodane'));
    }
    
})
    ->bind('activate')
;

$app->post('/zamowienia_save', function (Request $request) use ($app) {

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $user = $token->getUser();

        $q_user = "SELECT c_id , c_name from tbl_customers where c_email=:customer_email limit 1;";

        $q_customer = $app['dbs']['mysql_read']->prepare($q_user);
        $q_customer->bindValue(':customer_email', $user->getUsername(), PDO::PARAM_STR);
        $q_customer->execute();

        $customer = $q_customer->fetch();

        $customer_id = $customer['c_id'];

        $q_order_sql = "SELECT o_id from tbl_orders where o_c_id = :c_id and o_c_isbasket = 1";

        /** @var \Doctrine\DBAL\Statement $open_order */
        $open_order = $app['dbs']['mysql_read']->prepare($q_order_sql);
        $open_order->bindValue(':c_id', $customer_id, PDO::PARAM_STR);
        $open_order->execute();
        $orderId = 0;

        if ($open_order->rowCount() > 0) {
            $orderObject = $open_order->fetch(PDO::FETCH_OBJ);
            $orderId = $orderObject->o_id;
        }

        assert($orderId !== 0, 'Invalid order id');

        $order_own_id = $request->get('order_own_id');
        $order_address = $request->get('order_address');


        if($request->get('state') !== NULL) {
            $state = (int) $request->get('state');
        }else{
            $o_c_isbacket = 0;
        }

        $q_order_update_ = "UPDATE tbl_orders SET o_name = :order_own_id, o_c_id_shipto = :order_address , o_c_isbasket = :o_c_isbasket, o_status = 1 WHERE o_id = :oi_id AND o_c_id = :customer_id LIMIT 1;";
        $q_order_update = $app['dbs']['mysql_read']->prepare($q_order_update_);

        $q_order_update->bindValue(':order_own_id', $order_own_id, PDO::PARAM_STR);
        $q_order_update->bindValue(':order_address', (int) $order_address, PDO::PARAM_INT);
        $q_order_update->bindValue(':customer_id', (int) $customer_id, PDO::PARAM_INT);
        $q_order_update->bindValue(':o_c_isbasket', (int) $o_c_isbacket, PDO::PARAM_INT);
        $q_order_update->bindValue(':oi_id', (int) $orderId, PDO::PARAM_INT);
        $q_order_update->execute();

        return "OK";

    }else{
        return "ERROR 2242";
    }

})
    ->bind('zamowienia_save')
;

$app->post('/user_save', function (Request $request) use ($app) {

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $user = $token->getUser();
        
        $isAdmin = $app['security.authorization_checker']->isGranted('ROLE_ADMIN');

        if($isAdmin){
            $q_user_insert_ = "INSERT INTO tbl_customers VALUES(NULL,:c_name,:c_surname,:c_secret,:c_phone,:c_email, NOW() ,0,0,:c_activation_code,:c_roles);";
            $q_user_insert = $app['dbs']['mysql_read']->prepare($q_user_insert_);

            #Defaults to 'foo' for testing purponses
            $password_secret = '$2y$10$3i9/lVd8UOFIJ6PAMFt8gu3/r5g0qeCJvoSlLCsvMTythye19F77a';
            $activation_code = generateRandomString();
            
            $q_user_insert->bindValue(':c_name', $request->get('c_name'), PDO::PARAM_STR);
            $q_user_insert->bindValue(':c_surname', $request->get('c_surname'), PDO::PARAM_STR);
            $q_user_insert->bindValue(':c_secret', $password_secret, PDO::PARAM_STR);
            $q_user_insert->bindValue(':c_phone', $request->get('c_phone'), PDO::PARAM_STR);
            $q_user_insert->bindValue(':c_email', $request->get('c_email'), PDO::PARAM_STR);
            $q_user_insert->bindValue(':c_activation_code', $activation_code, PDO::PARAM_STR);
            $q_user_insert->bindValue(':c_roles', $request->get('c_roles'), PDO::PARAM_STR);
            $q_user_insert->execute();
        }else{
            return "ERROR 6755";
        }

        return "OK";

    }else{
        return "ERROR 2242";
    }

})
    ->bind('user_save')
;

$app->post('/zamowienia_save_get', function (Request $request) use ($app) {

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $user = $token->getUser();

        $q_user = "SELECT c_id , c_name from tbl_customers where c_email=:customer_email limit 1;";

        $q_customer = $app['dbs']['mysql_read']->prepare($q_user);
        $q_customer->bindValue(':customer_email', $user->getUsername(), PDO::PARAM_STR);
        $q_customer->execute();

        $customer = $q_customer->fetch();

        $customer_id = $customer['c_id'];

        $order_id = $request->get('order_id');

        $q_order_get_ = "SELECT o_name , o_c_id_shipto from tbl_orders WHERE o_id = :oi_id AND o_c_id = :customer_id LIMIT 1;";

        $q_order_get = $app['dbs']['mysql_read']->prepare($q_order_get_);

        $q_order_get->bindValue(':order_id', (int) $order_id, PDO::PARAM_INT);

        $q_order_get->bindValue(':customer_id', (int) $customer_id, PDO::PARAM_INT);
        $q_order_get->execute();

        return "OK";

    }else{
        return "ERROR 2242";
    }

})
    ->bind('zamowienia_save_get')
;


$app->post('/zamowienia_add_item', function (Request $request) use ($app) {

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $user = $token->getUser();

        $q_user = "SELECT c_id , c_name from tbl_customers where c_email=:customer_email limit 1;";

        $q_customer = $app['dbs']['mysql_read']->prepare($q_user);
        $q_customer->bindValue(':customer_email', $user->getUsername(), PDO::PARAM_STR);
        $q_customer->execute();

        $customer = $q_customer->fetch();

        $customer_id = $customer['c_id'];

        $q_order_sql = "SELECT Count(*) as cc , o_id from tbl_orders where o_c_id = :c_id and o_c_isbasket = 1 GROUP BY o_id;";

        /** @var PDOStatement $open_order */
        $open_order = $app['dbs']['mysql_read']->prepare($q_order_sql);
        $open_order->bindValue(':c_id', $customer_id, PDO::PARAM_STR);
        $open_order->execute();

        if ($open_order->rowCount() > 0) {
            $order = $open_order->fetch();
        } else {
            $order['cc'] = 0;
        }

        assert($order['cc'] <= 1, "Sprawdzenie ilosci otwartych zamownien : Uszkodzenie struktury zamowien klienta o ".$customer_id.", wiecej niz jedno otwarte zamowienie");

        if($order['cc'] == 0){

            $app['dbs']['mysql_read']->insert('tbl_orders',array('o_c_id' => $customer_id, 'o_c_isbasket' => 1));

            $open_order = $app['dbs']['mysql_read']->prepare($q_order_sql);
            $open_order->bindValue(':c_id', $customer_id, PDO::PARAM_STR);
            $open_order->execute();

            $order = $open_order->fetch();

        }
        $order_id = $order['o_id'];

        assert($order_id <> NULL ,"Sprawdzenie poprawnosci zapisu");

        $item_id = $request->get('item_id');
        $item_count = $request->get('item_count');

        $q_item_add_query = "SELECT COUNT(*) as is_there, oi_id from tbl_order_items where oi_order_id = :order_id AND oi_item_id = :item_id GROUP BY oi_id;";
        $q_item_add = $app['dbs']['mysql_read']->prepare($q_item_add_query);
        $q_item_add->bindValue(':order_id', (int)$order_id, PDO::PARAM_INT);
        $q_item_add->bindValue(':item_id', (int)$item_id, PDO::PARAM_INT);
        $q_item_add->execute();

        $itemed = $q_item_add->fetch();
        if(($remove = $request->get('remove')) != NULL )
        {
            $message = "REMOVE";
            $q_item_add_ = "DELETE FROM tbl_order_items WHERE oi_id=:oi_id AND oi_order_id = :order_id;";

            $q_item_add_run = $app['dbs']['mysql_read']->prepare($q_item_add_);
            $q_item_add_run->bindValue(':oi_id', (int)$remove, PDO::PARAM_INT);
            $q_item_add_run->bindValue(':order_id', (int)$order_id, PDO::PARAM_INT);

        }else {
//            var_dump($itemed);
//            exit;
            if ($itemed['is_there'] == 0) {
                $q_item_add_ = "INSERT INTO tbl_order_items (oi_item_id , oi_amount, oi_price , oi_order_id) VALUES 
                                                          (:item_id ,
                                                           :item_count,
                                                             (SELECT product_base_value FROM tbl_products WHERE product_id = :item_id ),
                                                              :order_id) ;";
                $q_item_add_run = $app['dbs']['mysql_read']->prepare($q_item_add_);
                $q_item_add_run->bindValue(':item_id', $item_id, PDO::PARAM_INT);
                $q_item_add_run->bindValue(':order_id', $order_id, PDO::PARAM_INT);
                $message = "INSERT";
            } else {
                if ($request->get('update_record') !== null) {
                    $q_item_add_ = "UPDATE tbl_order_items SET oi_amount = oi_amount + :item_count WHERE oi_id = :oi_id LIMIT 1;";
                    $message = "UPDATE ADD";
                } else {
                    $q_item_add_ = "UPDATE tbl_order_items SET oi_amount = :item_count WHERE oi_id = :oi_id LIMIT 1;";
                    $message = "UPDATE EXACT";
                }
                $q_item_add_run = $app['dbs']['mysql_read']->prepare($q_item_add_);
            }

        $q_item_add_run->bindValue(':item_count', $item_count, PDO::PARAM_INT);
        $q_item_add_run->bindValue(':oi_id', $itemed['oi_id'], PDO::PARAM_INT);
        }

        $q_item_add_run->execute();

    }else{
        $message = "ERROR : 7763";
    }

    return $message." ".$itemed['oi_id']."/".$item_count."/".$item_id;

})
    ->bind('zamowienia_add_item')
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

        $sql_address = "SELECT * from tbl_customer_address where a_c_id=:customer_id AND a_active = 1;";

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
$app->get('/kontodane_admin/{user_id}/show', function ($user_id) use ($app) {

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $user = $token->getUser();
        $isAdmin = $app['security.authorization_checker']->isGranted('ROLE_ADMIN');
        $connection = $app['dbs']['mysql_read'];
        $customer_id = getCustomerId($app['dbs']['mysql_read'], $user, $user_id , $isAdmin);
        $logged_in_id = getCustomerId($app['dbs']['mysql_read'], $user, -1 , $isAdmin);
        $logged_in = getCustomerbyId($connection, $logged_in_id);

        $app['monolog']->info("User_id $user_id.");
        $app['monolog']->info("Customer_id $customer_id.");

        $sql_kontodane = "select c_id,c_name,c_surname,c_phone,c_email,c_registered,c_islocked,c_isactive from tbl_customers where c_id = :customer_id LIMIT 1;";

        $q_kontodane = $app['dbs']['mysql_read']->prepare($sql_kontodane);
        $q_kontodane->bindValue(':customer_id', $user_id, PDO::PARAM_INT);
        $q_kontodane->execute();

        $sql_cards = "SELECT card_id , card_type , CONCAT(\"*****\", SUBSTRING(card_number, -4) )as card_number , card_expiry , card_active FROM tbl_cards where card_user=:customer_id;";

        $q_cards = $app['dbs']['mysql_read']->prepare($sql_cards);
        $q_cards->bindValue(':customer_id', $user_id, PDO::PARAM_INT);
        $q_cards->execute();

        $sql_address = "SELECT * from tbl_customer_address where a_c_id=:customer_id AND a_active = 1;";

        $q_address = $app['dbs']['mysql_read']->prepare($sql_address);
        $q_address->bindValue(':customer_id', $user_id, PDO::PARAM_INT);
        $q_address->execute();

        /** @var StatusService $statusService */
        $statusService = $app['service.status'];

        /** @var User $user */
        $user = $token->getUser();
        /** @var Connection $connection */
        $connection = $app['dbs']['mysql_read'];

        $customer = getCustomerbyId($connection, $user_id);

        $possibleStatusLocked = [];
        $possibleStatusActive = [];
        if ($app['security.authorization_checker']->isGranted('ROLE_ADMIN')) {
            $user_query = getUserById($connection, $user_id);
            $possibleStatusLocked = $statusService->getPossibleStatusesForUserLock($user_query);
            $possibleStatusActive = $statusService->getPossibleStatusesForUserActivation($user_query);
        } else {
            $user_query = null;
        }

        if (null === $user_query) {
            return new Response('Access Denied', Response::HTTP_FORBIDDEN);
        }

        return $app['twig']->render('kontodane.html.twig', 
            [
                'kontodane' => $q_kontodane,
                'cards' => $q_cards,
                'address' => $q_address,
                'customer' => $logged_in_id,
                'customer_name' => $logged_in['c_name'],
                'user' => $customer,
                'possibleStatusesLock' => $possibleStatusLocked,
                'possibleStatusesActive' => $possibleStatusActive,
            ]
        );
    }else{
        return "ERROR MISSING USER ID";
    }
})
    ->bind('kontodane_admin')
;
$app->get('/kontodane_block_card/{card_id}/{user_id}', function ($card_id,$user_id) use ($app) {

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $user = $token->getUser();
        $isAdmin = $app['security.authorization_checker']->isGranted('ROLE_ADMIN');
        $connection = $app['dbs']['mysql_read'];
        $app['monolog']->error("Customer_id $user_id/$card_id");
        $customer_id = getCustomerId($app['dbs']['mysql_read'], $user, $user_id , $isAdmin);
        if($isAdmin){
            $logged_in_id = getCustomerId($app['dbs']['mysql_read'], $user, -1 , $isAdmin);
        }
        $q_card_update = "UPDATE tbl_cards SET card_active = 0 where card_user = :customer_id AND card_id = :card_id LIMIT 1;";

        $q_card_update = $app['dbs']['mysql_read']->prepare($q_card_update);
        $q_card_update->bindValue(':customer_id', (int)$customer_id, PDO::PARAM_INT);
        $q_card_update->bindValue(':card_id', (int)$card_id, PDO::PARAM_INT);
        $q_card_update->execute();

    }else{
        return "ERROR MISSING USER ID";
    }
    if($isAdmin && ($logged_in_id <> $customer_id)){
        return $app->redirect($app['url_generator']->generate('kontodane_admin', array('user_id' => $customer_id )));
    }else{
        return $app->redirect($app['url_generator']->generate('kontodane'));
    }
})
    ->bind('kontodane_block_card')
;

$app->get('/kontodane_block_address/{address_id}/{user_id}', function ($address_id,$user_id) use ($app) {

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $user = $token->getUser();
        $isAdmin = $app['security.authorization_checker']->isGranted('ROLE_ADMIN');
        $connection = $app['dbs']['mysql_read'];     
        $customer_id = getCustomerId($app['dbs']['mysql_read'], $user, $user_id , $isAdmin);
        if($isAdmin){
            $logged_in_id = getCustomerId($app['dbs']['mysql_read'], $user, -1 , $isAdmin);
        }
        #$app['monolog']->error("Address_id $address_id");

        $q_address_update = "UPDATE tbl_customer_address SET a_active = 0 where a_c_id = :customer_id AND a_id = :address_id LIMIT 1;";

        $q_address_update = $app['dbs']['mysql_read']->prepare($q_address_update);
        $q_address_update->bindValue(':customer_id', (int)$customer_id, PDO::PARAM_INT);
        $q_address_update->bindValue(':address_id', (int)$address_id, PDO::PARAM_INT);
        $q_address_update->execute();

    }else{
        return "ERROR MISSING USER ID";
    }
    if($isAdmin && ($logged_in_id <> $customer_id)){
        return $app->redirect($app['url_generator']->generate('kontodane_admin', array('user_id' => $customer_id )));
    }else{
        return $app->redirect($app['url_generator']->generate('kontodane'));
    }
})
    ->bind('kontodane_block_address')
;

$app->get('/kontodane_edit_address_/{address_id}/{user_id}', function ($address_id,$user_id) use ($app) {

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $user = $token->getUser();
        $isAdmin = $app['security.authorization_checker']->isGranted('ROLE_ADMIN');
        $connection = $app['dbs']['mysql_read'];     
        $customer_id = getCustomerId($app['dbs']['mysql_read'], $user, $user_id , $isAdmin);
        $logged_in_id = getCustomerId($app['dbs']['mysql_read'], $user, -1 , $isAdmin);
        $logged_in = getCustomerbyId($connection, $logged_in_id);

        if($address_id > 0) {
            $q_address_query = "SELECT * FROM tbl_customer_address where a_id = :address_id AND a_c_id = :customer_id  LIMIT 1;";

            $q_address_query = $app['dbs']['mysql_read']->prepare($q_address_query);
            $q_address_query->bindValue(':customer_id', (int)$customer_id, PDO::PARAM_INT);
            $q_address_query->bindValue(':address_id', (int)$address_id, PDO::PARAM_INT);
            $q_address_query->execute();

        }else{

            $q_address_query = array (

                array(
                'a_id' => NULL ,
                'a_city' => '' ,
                'a_postcode' => '' ,
                'a_street' => '' ,
                'a_street_address' => '' ,
                'a_name' => '' ,
                'a_c_id' => $customer_id
            )

            );
        }

    }else{
        return "ERROR MISSING USER ID";
    }
    return $app['twig']->render('kontodane_edit_address.html.twig', 
        [
        'address' => $q_address_query,
        'user_id' => $customer_id,
        'customer' => $logged_in_id,
        'customer_name' => $logged_in['c_name'],
        ]
    );

})
    ->bind('kontodane_edit_address_')
;

$app->post('/kontodane_edit_address', function (Request $request) use ($app) {

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $user = $token->getUser();
        $isAdmin = $app['security.authorization_checker']->isGranted('ROLE_ADMIN');
        $connection = $app['dbs']['mysql_read'];     
        $customer_id = getCustomerId($app['dbs']['mysql_read'], $user, $request->get('user_id') , $isAdmin);
        if($isAdmin){
            $logged_in_id = getCustomerId($app['dbs']['mysql_read'], $user, -1 , $isAdmin);
        }

        if($request->get('a_id') == 0) {
            $q_address_query = "INSERT INTO tbl_customer_address (a_city , a_postcode , a_street , a_street_address , a_name , a_c_id ) VALUES 
                                                                  (:a_city,:a_postcode, :a_street, :a_street_address, :a_name, :customer_id) ;";

        }else {
            $q_address_query = "UPDATE tbl_customer_address SET a_city = :a_city,
                                                                a_postcode = :a_postcode,
                                                                a_street = :a_street,
                                                                a_street_address = :a_street_address,
                                                                a_name = :a_name,
                                                                a_c_id = :customer_id
                                                                
                                WHERE a_id = :address_id AND a_c_id = :customer_id  LIMIT 1;";
        }

        $q_address_query = $app['dbs']['mysql_read']->prepare($q_address_query);
        $q_address_query->bindValue(':a_city', $request->get('a_city'), PDO::PARAM_STR);
        $q_address_query->bindValue(':a_postcode', $request->get('a_postcode'),PDO::PARAM_STR);
        $q_address_query->bindValue(':a_street', $request->get('a_street'),PDO::PARAM_STR);
        $q_address_query->bindValue(':a_street_address', $request->get('a_street_address'),PDO::PARAM_STR);
        $q_address_query->bindValue(':a_name', $request->get('a_name'),PDO::PARAM_STR);

        if($request->get('a_id') > 0){
            $q_address_query->bindValue(':address_id', (int)$request->get('a_id'), PDO::PARAM_INT);
        }
        $q_address_query->bindValue(':customer_id', (int)$customer_id, PDO::PARAM_INT);
        $q_address_query->execute();

    }else{
        return "ERROR MISSING USER ID";
    }
    if($isAdmin && ($logged_in_id <> $customer_id)){
        return $app->redirect($app['url_generator']->generate('kontodane_admin', array('user_id' => $customer_id )));
    }else{
        return $app->redirect($app['url_generator']->generate('kontodane'));
    }

})
    ->bind('kontodane_edit_address')
;

$app->post('/kontodane_change_pass', function (Request $request) use ($app) {

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $user = $token->getUser();
        $isAdmin = $app['security.authorization_checker']->isGranted('ROLE_ADMIN');
        $connection = $app['dbs']['mysql_read'];     
        $customer_id = getCustomerId($app['dbs']['mysql_read'], $user, $request->get('user_id') , $isAdmin);
        if($isAdmin){
            $logged_in_id = getCustomerId($app['dbs']['mysql_read'], $user, -1 , $isAdmin);
        }
        $encoder = $app['security.encoder_factory']->getEncoder($user);

        //$pass_current = $encoder->encodePassword($request->get('pass_current'), $user->getSalt());
        $pass_new = $encoder->encodePassword($request->get('pass_new'), $user->getSalt());

        //if(( $request->get('pass_new')  NULL ) AND ($request->get('pass_current') <> NULL ) ){

            $q_card_update = "UPDATE tbl_customers SET c_secret = :new_pass where c_id = :customer_id;";// AND ( STRCMP(c_secret,:old_pass) = 0 ) LIMIT 1;";

            $q_card_update = $app['dbs']['mysql_read']->prepare($q_card_update);
            $q_card_update->bindValue(':customer_id', $customer_id, PDO::PARAM_STR);
            //$q_card_update->bindValue(':old_pass', $pass_current, PDO::PARAM_STR);
            $q_card_update->bindValue(':new_pass', $pass_new, PDO::PARAM_STR);
            $q_card_update->execute();

            //return '/kontodane_change_pass pass_new='.$pass_new.' /kontodane_change_pass pass_new='.$pass_current;

        //}

    }else{
        return "ERROR MISSING USER ID";
    }
    if($isAdmin && ($logged_in_id <> $customer_id)){
        return $app->redirect($app['url_generator']->generate('kontodane_admin', array('user_id' => $customer_id )));
    }else{
        return $app->redirect($app['url_generator']->generate('kontodane'));
    }
    
})
    ->bind('kontodane_change_pass')
;

$app->post('/kontodane_edit_user', function (Request $request) use ($app) {

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $user = $token->getUser();
        $isAdmin = $app['security.authorization_checker']->isGranted('ROLE_ADMIN');
        $connection = $app['dbs']['mysql_read'];     
        $customer_id = getCustomerId($app['dbs']['mysql_read'], $user, $request->get('user_id') , $isAdmin);
        if($isAdmin){
            $logged_in_id = getCustomerId($app['dbs']['mysql_read'], $user, -1 , $isAdmin);
        }
        $q_user_update = "UPDATE tbl_customers 
                                               SET c_name = :customer_name,
                                                   c_surname = :customer_surname ,
                                                   c_phone = :customer_phone ,
                                                   c_email = :customer_email
                          
                          where c_id = :customer_id LIMIT 1;";

        $q_user_update = $app['dbs']['mysql_read']->prepare($q_user_update);
        $q_user_update->bindValue(':customer_name',     $request->get('c_name'), PDO::PARAM_STR);
        $q_user_update->bindValue(':customer_surname',  $request->get('c_surname'), PDO::PARAM_STR);
        $q_user_update->bindValue(':customer_phone',    $request->get('c_phone'), PDO::PARAM_STR);
        $q_user_update->bindValue(':customer_email',    $request->get('c_email'), PDO::PARAM_STR);
        $q_user_update->bindValue(':customer_id',       (int)$customer_id, PDO::PARAM_STR);
        $q_user_update->execute();

    }else{
        return "ERROR MISSING USER ID";
    }
    if($isAdmin && ($logged_in_id <> $customer_id)){
        return $app->redirect($app['url_generator']->generate('kontodane_admin', array('user_id' => $customer_id )));
    }else{
        return $app->redirect($app['url_generator']->generate('kontodane'));
    }
})
    ->bind('kontodane_edit_user')
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
