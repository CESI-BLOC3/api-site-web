<?php
declare(strict_types=1);
session_start();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// CORS pour /api/*
if (strpos($uri, '/api/') === 0) {
  header('Content-Type: application/json');
  header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') exit;
}

$config = require __DIR__ . '/../config/config.php';

spl_autoload_register(function ($class) {
  $prefix='App\\'; $base=__DIR__.'/../app/';
  if (strncmp($prefix,$class,strlen($prefix))!==0) return;
  $file=$base.str_replace('\\','/',$class).'.php'; $file=str_replace('App/','',$file);
  if (file_exists($file)) require $file;
});

use App\Core\Router;
use App\Core\Database;
Database::init($config['db']);

$router = new Router();

/** Web */
$router->get('/', 'App\\Controllers\\HomeController@index');
$router->get('/events/{id}', 'App\\Controllers\\HomeController@detail');
$router->get('/events/{id}/flyer.pdf', 'App\\Controllers\\HomeController@flyer');

$router->get('/login', 'App\\Controllers\\AuthController@showLogin');
$router->post('/login', 'App\\Controllers\\AuthController@login');
$router->post('/logout', 'App\\Controllers\\AuthController@logout');

$router->get('/account', 'App\\Controllers\\AccountController@show');
$router->post('/account/email', 'App\\Controllers\\AccountController@updateEmail');
$router->post('/account/password/request', 'App\\Controllers\\AccountController@requestPasswordCode');
$router->post('/account/password/reset', 'App\\Controllers\\AccountController@resetPassword');
$router->get('/set-password', 'App\\Controllers\\AccountController@setPasswordForm');
$router->post('/set-password', 'App\\Controllers\\AccountController@setPasswordApply');

/** Admin Web */
$router->get('/admin/events', 'App\\Controllers\\EventController@index');
$router->get('/admin/events/create', 'App\\Controllers\\EventController@create');
$router->post('/admin/events/store', 'App\\Controllers\\EventController@store');
$router->get('/admin/events/{id}', 'App\\Controllers\\EventController@show');
$router->get('/admin/events/{id}/edit', 'App\\Controllers\\EventController@edit');
$router->post('/admin/events/{id}/update', 'App\\Controllers\\EventController@update');
$router->post('/admin/events/{id}/delete', 'App\\Controllers\\EventController@destroy');
$router->get('/admin/users/create', 'App\\Controllers\\UserController@create');
$router->post('/admin/users/store', 'App\\Controllers\\UserController@store');

/** API pour mobile */
$router->post('/api/login', 'App\\Controllers\\ApiController@login');
$router->get('/api/me', 'App\\Controllers\\ApiController@me');

$router->get('/api/events-public', 'App\\Controllers\\ApiController@listPublic');
$router->get('/api/events', 'App\\Controllers\\ApiController@list');
$router->get('/api/events/{id}', 'App\\Controllers\\ApiController@detail');
$router->post('/api/events', 'App\\Controllers\\ApiController@create');
$router->put('/api/events/{id}', 'App\\Controllers\\ApiController@update');
$router->delete('/api/events/{id}', 'App\\Controllers\\ApiController@delete');

$router->post('/api/events/{id}/cover', 'App\\Controllers\\ApiController@uploadCover');
$router->post('/api/events/{id}/photos', 'App\\Controllers\\ApiController@uploadPhotos');

// Compte (utilisé par l’app)
$router->post('/api/account/update-profile', 'App\\Controllers\\ApiController@updateProfile');
$router->post('/api/account/change-email', 'App\\Controllers\\ApiController@requestEmailChange');
$router->post('/api/account/confirm-email', 'App\\Controllers\\ApiController@confirmEmailChange');
$router->post('/api/account/change-password', 'App\\Controllers\\ApiController@changePassword');

$router->dispatch();
