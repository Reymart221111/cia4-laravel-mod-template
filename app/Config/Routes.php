<?php

use App\Controllers\UserController;
use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->group('users', function(RouteCollection $routes) {
	$routes->get('/', [UserController::class, 'index'], ['as' => 'users.index']);
	$routes->get('create', [UserController::class, 'create'], ['as' => 'users.create']);
	$routes->get('edit/(:num)', [UserController::class, 'edit'], ['as' => 'users.edit']);
	$routes->post('store', [UserController::class, 'store'], ['as' => 'users.store']);
	$routes->put('update/(:num)', [UserController::class, 'update'], ['as' => 'users.update']);
	$routes->delete('delete/(:num)', [UserController::class, 'destroy'], ['as' => 'users.delete']);
});
