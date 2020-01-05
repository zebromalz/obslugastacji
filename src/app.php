<?php

use Service\StatusService;
use Silex\Application;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;


// Authentication :
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UserProvider implements UserProviderInterface
{
    private $conn;

    public function __construct(Doctrine\DBAL\Connection $conn)
    {
        $this->conn = $conn;
    }

    public function loadUserByUsername($username)
    {

        $sql_users = "SELECT c_email , c_secret , c_roles FROM tbl_customers WHERE c_email = :c_email;";

        $q_users = $this->conn->prepare($sql_users);
        $q_users->bindValue(':c_email', $username, PDO::PARAM_STR);
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

//$app->error(function (\Exception $e, $code) use($app) {
//    // ...
//    $client = $app['sentry'];
//    $client->captureException($e);
//    // ...
//});

$app->register(new Silex\Provider\SessionServiceProvider());

$app['service.status'] = new StatusService();

$app->register(
    new Silex\Provider\SecurityServiceProvider(),
    array(
        'security.firewalls' => array(
            'login' => array(
                'pattern' => '^/login$',
            ),
            'secured' => array(
                'pattern' => '^.*$',
                'form' => array(
                    'login_path' => '/login',
                    'check_path' => '/login_check',
                    'always_use_default_target_path' => true,
                    'default_target_path' => '/zamowienia'
                ),
                'logout' => array('logout_path' => '/logout', 'invalidate_session' => true),
                'users' => function () use ($app) {
                    return new UserProvider($app['db']);
                },
            ),
        ),
        'security.access_rules' => [
            array('/zamowienie/status', 'ROLE_ADMIN'),
            array('/zamowienie', 'ROLE_USER'),
            array('/admin', 'ROLE_ADMIN'),
        ],
        'security.role_hierarchy' => [
            'ROLE_ADMIN' => array('ROLE_USER'),
        ]
    )
);

$app['twig'] = $app->extend(
    'twig',
    function ($twig, $app) {
        /** @var Twig_Environment $twig */
        $twig->addFilter(
            new Twig_Filter('status_label', function (int $status = null) {
                switch ($status) {
                    case 1:
                        return '<span class="label label-default">Nowy</span>';
                    case 2:
                        return '<span class="label label-warning">Przyjęte</span>';
                    case 3:
                        return '<span class="label label-info">Opłacone</span>';
                    case 4:
                        return '<span class="label label-success">Zrealizowane</span>';
                    case 5:
                        return '<span class="label label-danger">Anulowane</span>';
                }

                return null;
        }));
        /** @var Twig_Environment $twig */
        $twig->addFilter(
            new Twig_Filter('status_locked_label', function (int $status = null) {
                switch ($status) {
                    case 0:
                        return '';
                    case 1:
                        return '<span class="label label-danger">Zablokowany</span>';
                }

                return null;
        }));
        $twig->addFilter(
            new Twig_Filter('status_locked', function (int $status = null) {
                switch ($status) {
                    case 0:
                        return 'Nie Zablokowany';
                    case 1:
                        return 'Zablokowany';
                }

                return null;
        }));
        /** @var Twig_Environment $twig */
        $twig->addFilter(
            new Twig_Filter('status_active_label', function (int $status = null) {
                switch ($status) {
                    case 0:
                        return '<span class="label label-default">Nie Aktywny</span>';
                    case 1:
                        return '<span class="label label-success">Aktywny</span>';
                }

                return null;
        }));
        /** @var Twig_Environment $twig */
        $twig->addFilter(
            new Twig_Filter('status_active', function (int $status = null) {
                switch ($status) {
                    case 0:
                        return 'Nie Aktywny';
                    case 1:
                        return 'Aktywny';
                }

                return null;
        }));
        $twig->addFilter(
            new Twig_Filter('status', function (int $status = null) {
                switch ($status) {
                    case 1:
                        return 'Nowy';
                    case 2:
                        return 'Przyjęte';
                    case 3:
                        return 'Opłacone';
                    case 4:
                        return 'Zrealizowane';
                    case 5:
                        return 'Anulowane';
                }

                return null;
            }));

        return $twig;
    }
);


return $app;
