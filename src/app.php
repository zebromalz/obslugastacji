<?php

use Silex\Application;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;


// Authentication :
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UserProvider implements UserProviderInterface
{
    private $conn;

    public function __construct( Doctrine\DBAL\Connection $conn)
    {
        $this->conn = $conn;
    }

    public function loadUserByUsername($username)
    {

        $sql_users = "SELECT c_email , c_secret , c_roles FROM tbl_customers where c_email = :c_email;";

        $q_users = $this->conn->prepare($sql_users);
        $q_users->bindValue(':c_email',$username,PDO::PARAM_STR);
        $q_users->execute();

        if (!$user = $q_users->fetch()) {

                throw new UsernameNotFoundException(sprintf('Konto "%s" nie istnieje.', $username));
            }

        return new User($user['c_email'], $user['c_secret'], explode(',', $user['c_roles']), true, true, true, true);
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return $class === 'Symfony\Component\Security\Core\User\User';
    }
}

$app = new Application();
$app->register(new ServiceControllerServiceProvider());
$app->register(new AssetServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new HttpFragmentServiceProvider());

$app->error(function (\Exception $e, $code) use($app) {
    // ...
    $client = $app['sentry'];
    $client->captureException($e);
    // ...
});

$app->register(new Silex\Provider\SessionServiceProvider());

$app->register(new Silex\Provider\SecurityServiceProvider(),
    array(
    'security.firewalls' => array(
                                    'login' => array(
                                                'pattern' => '^/login$',
                                                    ),
                                    'secured' => array(
                                                'pattern' => '^.*$',
                                                'form' => array('login_path' => '/login', 'check_path' => '/login_check'),
                                                'logout' => array('logout_path' => '/logout', 'invalidate_session' => true),
                                                'users' => function () use ($app) {
                                                                                    return new UserProvider($app['db']);
                                                                                    },
                                                ),
                                )
    )
);

//$app['security.access_rules'] = array(
//    array('^/admin', 'ROLE_ADMIN', 'https'),
//    array('^.*$', 'ROLE_ADMIN'),
//);

$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/dev.log',
));

$app['twig'] = $app->extend('twig', function ($twig, $app) {
    // add custom globals, filters, tags, ...

    return $twig;
});



return $app;
