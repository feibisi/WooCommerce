<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\WooCommerce\Http\Controllers'], function () {
    Route::post('/woocommerce/ajax', ['uses' => 'WooCommerceController@ajax', 'laroute' => true])->name('woocommerce.ajax');
    // Public HTML for Sidebar Webhook (NOT under /modules/* — that path is static Public symlink)
    Route::match(['get', 'post'], '/woocommerce/sidebar-webhook', [
        'uses' => 'WooCommerceController@sidebarWebhook',
    ])->name('woocommerce.sidebar_webhook');
});
