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
// Package Display Template Selector
$routes->get('admin/package-display', 'Admin\PackageDisplayController::index', ['filter' => 'adminAuth']);
$routes->post('admin/package-display', 'Admin\PackageDisplayController::update', ['filter' => 'adminAuth']);

// FAQs — Phase 06: Dynamic FAQ System Management
$routes->get('admin/faqs', 'Admin\FaqController::index', ['filter' => 'adminAuth']);
$routes->get('admin/faqs/create', 'Admin\FaqController::create', ['filter' => 'adminAuth']);
$routes->post('admin/faqs', 'Admin\FaqController::store', ['filter' => 'adminAuth']);
$routes->get('admin/faqs/(:num)/edit', 'Admin\FaqController::edit/$1', ['filter' => 'adminAuth']);
$routes->post('admin/faqs/(:num)', 'Admin\FaqController::update/$1', ['filter' => 'adminAuth']);
$routes->post('admin/faqs/(:num)/delete', 'Admin\FaqController::delete/$1', ['filter' => 'adminAuth']);
$routes->post('admin/faqs/(:num)/toggle-active', 'Admin\FaqController::toggleActive/$1', ['filter' => 'adminAuth']);

// Client Results — Phase 07B & 07C: Client Results Admin Management & Dynamic Metrics
$routes->get('admin/client-results', 'Admin\ClientResultController::index', ['filter' => 'adminAuth']);
$routes->get('admin/client-results/create', 'Admin\ClientResultController::create', ['filter' => 'adminAuth']);
$routes->post('admin/client-results', 'Admin\ClientResultController::store', ['filter' => 'adminAuth']);
$routes->get('admin/client-results/(:num)/edit', 'Admin\ClientResultController::edit/$1', ['filter' => 'adminAuth']);
$routes->post('admin/client-results/(:num)', 'Admin\ClientResultController::update/$1', ['filter' => 'adminAuth']);
$routes->post('admin/client-results/(:num)/delete', 'Admin\ClientResultController::delete/$1', ['filter' => 'adminAuth']);
$routes->post('admin/client-results/(:num)/toggle-active', 'Admin\ClientResultController::toggleActive/$1', ['filter' => 'adminAuth']);
$routes->post('admin/client-results/(:num)/toggle-featured', 'Admin\ClientResultController::toggleFeatured/$1', ['filter' => 'adminAuth']);
// Outcome Metrics — Phase 07C
$routes->post('admin/client-results/(:num)/metrics', 'Admin\ClientResultController::storeMetric/$1', ['filter' => 'adminAuth']);
$routes->post('admin/client-results/(:num)/metrics/(:num)', 'Admin\ClientResultController::updateMetric/$1/$2', ['filter' => 'adminAuth']);
$routes->post('admin/client-results/(:num)/metrics/(:num)/delete', 'Admin\ClientResultController::deleteMetric/$1/$2', ['filter' => 'adminAuth']);
$routes->post('admin/client-results/(:num)/metrics/(:num)/toggle-public', 'Admin\ClientResultController::toggleMetricPublic/$1/$2', ['filter' => 'adminAuth']);
// Visual Proof Media — Phase 07D
$routes->post('admin/client-results/(:num)/media/before', 'Admin\ClientResultController::storeBeforeMedia/$1', ['filter' => 'adminAuth']);
$routes->post('admin/client-results/(:num)/media/after', 'Admin\ClientResultController::storeAfterMedia/$1', ['filter' => 'adminAuth']);
$routes->post('admin/client-results/(:num)/media', 'Admin\ClientResultController::storeGalleryMedia/$1', ['filter' => 'adminAuth']);
$routes->post('admin/client-results/(:num)/media/(:num)', 'Admin\ClientResultController::updateMedia/$1/$2', ['filter' => 'adminAuth']);
$routes->post('admin/client-results/(:num)/media/(:num)/delete', 'Admin\ClientResultController::deleteMedia/$1/$2', ['filter' => 'adminAuth']);
$routes->post('admin/client-results/(:num)/media/(:num)/toggle-public', 'Admin\ClientResultController::toggleMediaPublic/$1/$2', ['filter' => 'adminAuth']);
// Reports & Evidence — Phase 07E
$routes->post('admin/client-results/(:num)/reports', 'Admin\ClientResultController::storeReport/$1', ['filter' => 'adminAuth']);
$routes->post('admin/client-results/(:num)/reports/(:num)', 'Admin\ClientResultController::updateReport/$1/$2', ['filter' => 'adminAuth']);
$routes->post('admin/client-results/(:num)/reports/(:num)/delete', 'Admin\ClientResultController::deleteReport/$1/$2', ['filter' => 'adminAuth']);
$routes->post('admin/client-results/(:num)/reports/(:num)/toggle-public', 'Admin\ClientResultController::toggleReportPublic/$1/$2', ['filter' => 'adminAuth']);
$routes->get('admin/client-results/(:num)/reports/(:num)/file', 'Admin\ClientResultController::serveReportFile/$1/$2', ['filter' => 'adminAuth']);
// Admin Proof Preview — Phase 07F
$routes->get('admin/client-results/(:num)/preview', 'Admin\ClientResultController::preview/$1', ['filter' => 'adminAuth']);

// UI Preview — dev only, authenticated (Phase 4)
$routes->get('admin/ui-preview', 'Admin\UiPreviewController::index', ['filter' => 'adminAuth']);

// Brand Guidelines — authenticated preview (docs/brand-guidelines/ is source of truth, not prod theme)
$routes->get('admin/brand-guidelines', 'Admin\BrandGuidelinesController::index', ['filter' => 'adminAuth']);
$routes->get('admin/brand-guidelines/frame', 'Admin\BrandGuidelinesController::frame', ['filter' => 'adminAuth']);
$routes->get('admin/brand-guidelines/css/styles.css', 'Admin\BrandGuidelinesController::css', ['filter' => 'adminAuth']);
$routes->get('admin/brand-guidelines/js/main.js', 'Admin\BrandGuidelinesController::js', ['filter' => 'adminAuth']);
