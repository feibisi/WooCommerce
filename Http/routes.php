<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\WooCommerce\Http\Controllers'], function () {
    Route::post('/woocommerce/ajax', ['uses' => 'WooCommerceController@ajax', 'laroute' => true])->name('woocommerce.ajax');
    // Public HTML for Sidebar Webhook module (secret-protected)
    Route::match(['get', 'post'], '/modules/woocommerce/sidebar-webhook', [
        'uses' => 'WooCommerceController@sidebarWebhook',
    ])->name('woocommerce.sidebar_webhook');
});
