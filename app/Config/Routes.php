<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'User::index');
$routes->get('/admin', 'Admin::index', ['filter' => 'role:admin']);
$routes->get('/admin/index', 'Admin::index', ['filter' => 'role:admin']);
$routes->get('/admin/(:num)', 'Admin::detail/$1', ['filter' => 'role:admin']);
$routes->post('recap/fetchMachineData', 'Recap::fetchMachineData');
$routes->get('API/updateLastSeen', 'APIController::updateLastSeen');
$routes->get('API/updateWeldID', 'APIController::updateWeldID');
$routes->get('API/insertHeartBeat', 'APIController::insertHeartbeat');
$routes->get('API/handleArea', 'APIController::handleArea');
$routes->get('API/updateMachineData', 'APIController::updateMachineData');
$routes->get('API/handleScan', 'APIController::qrRFIDData');
$routes->get('API/handleRFID', 'APIController::handleRFID');
$routes->post('record/insertProject', 'Record::insertProject');
$routes->post('record/insertWeldMetal', 'Record::insertWeldMetal');
$routes->get('nfc/read', 'NFCRead::index');
$routes->post('nfc-read/receive', 'NFCRead::receive');
$routes->setAutoRoute(true);