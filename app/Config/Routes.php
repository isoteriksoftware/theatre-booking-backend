<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php'))
{
	require SYSTEMPATH . 'Config/Routes.php';
}

$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true);

$routes->options('(:any)', 'BaseController::options');

$routes->get('/', 'Home::index');
$routes->get('shows', 'Admin::getPublicShows');

$routes->post('user', 'User::createUser');
$routes->post('user/session', 'User::createSession');
$routes->delete('user/session', 'User::clearSession');
$routes->post('user/booking', 'User::addBooking');
$routes->post('admin/session', 'Admin::createSession');
$routes->delete('admin/session', 'Admin::clearSession');
$routes->post('admin/show', 'Admin::addShow');
$routes->get('admin/show', 'Admin::getShows');
$routes->get('admin/booking', 'Admin::getBookings');
$routes->get('admin/tickets', 'Admin::getTickets');

if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php'))
{
	require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
