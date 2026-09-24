<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

// Admin authentication — Phase 3
$routes->get('admin/login', 'Admin\AuthController::login', ['filter' => 'adminGuest']);
$routes->post('admin/login', 'Admin\AuthController::attemptLogin', ['filter' => 'adminGuest']);
$routes->post('admin/logout', 'Admin\AuthController::logout', ['filter' => 'adminAuth']);

// Authenticated admin
$routes->get('admin', 'Admin\DashboardController::index', ['filter' => 'adminAuth']);
$routes->get('admin/profile', 'Admin\ProfileController::index', ['filter' => 'adminAuth']);
$routes->post('admin/profile/password', 'Admin\ProfileController::updatePassword', ['filter' => 'adminAuth']);

// Packages — Phase 5D: media, lifecycle, reorder, soft-delete
$routes->get('admin/packages/create', 'Admin\PackageController::create', ['filter' => 'adminAuth']);
$routes->get('admin/packages/deleted', 'Admin\PackageController::deleted', ['filter' => 'adminAuth']);
$routes->post('admin/packages/reorder', 'Admin\PackageController::reorder', ['filter' => 'adminAuth']);
$routes->post('admin/packages/(:segment)/toggle-active', 'Admin\PackageController::toggleActive/$1', ['filter' => 'adminAuth']);
$routes->post('admin/packages/(:segment)/toggle-featured', 'Admin\PackageController::toggleFeatured/$1', ['filter' => 'adminAuth']);
$routes->post('admin/packages/(:segment)/delete', 'Admin\PackageController::delete/$1', ['filter' => 'adminAuth']);
$routes->post('admin/packages/(:segment)/restore', 'Admin\PackageController::restore/$1', ['filter' => 'adminAuth']);
$routes->get('admin/packages/(:segment)/edit', 'Admin\PackageController::edit/$1', ['filter' => 'adminAuth']);
$routes->post('admin/packages/(:segment)', 'Admin\PackageController::update/$1', ['filter' => 'adminAuth']);
$routes->post('admin/packages', 'Admin\PackageController::store', ['filter' => 'adminAuth']);
$routes->get('admin/packages', 'Admin\PackageController::index', ['filter' => 'adminAuth']);

// UI Preview — dev only, authenticated (Phase 4)
$routes->get('admin/ui-preview', 'Admin\UiPreviewController::index', ['filter' => 'adminAuth']);

// Brand Guidelines — authenticated preview (docs/brand-guidelines/ is source of truth, not prod theme)
$routes->get('admin/brand-guidelines', 'Admin\BrandGuidelinesController::index', ['filter' => 'adminAuth']);
$routes->get('admin/brand-guidelines/frame', 'Admin\BrandGuidelinesController::frame', ['filter' => 'adminAuth']);
$routes->get('admin/brand-guidelines/css/styles.css', 'Admin\BrandGuidelinesController::css', ['filter' => 'adminAuth']);
$routes->get('admin/brand-guidelines/js/main.js', 'Admin\BrandGuidelinesController::js', ['filter' => 'adminAuth']);
