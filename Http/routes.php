<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\WooCommerce\Http\Controllers'], function () {
    Route::post('/woocommerce/ajax', ['uses' => 'WooCommerceController@ajax', 'laroute' => true])->name('woocommerce.ajax');
});

// No CSRF / session — for Sidebar Webhook module (Guzzle POST JSON)
Route::group(['middleware' => [], 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\WooCommerce\Http\Controllers'], function () {
    Route::match(['get', 'post'], '/woocommerce/sidebar-webhook', [
        'uses' => 'WooCommerceController@sidebarWebhook',
    ])->name('woocommerce.sidebar_webhook');
});
