<form class="form-horizontal margin-top" method="POST" action="">
    {{ csrf_field() }}

<div class="descr-block">
{{ __("Load order and customer information on the sidebar based on the customer's e-mail address. Generate API keys under WooCommerce → Settings → Advanced → REST API (Read permission). Prefer Application-style Consumer Key/Secret for modern WooCommerce.") }}
<br>
<small class="text-muted">feibisi fork · WC REST v3 first · Sidebar webhook: /modules/woocommerce/sidebar-webhook?secret=…</small>
</div>

    <div class="form-group{{ $errors->has('settings[wc_domain]') ? ' has-error' : '' }}">
        <label for="wc_domain" class="col-sm-2 control-label">{{ __('Domain') }}</label>
        <div class="col-sm-6">
            <input id="wc_domain" type="text" class="form-control input-sized" name="settings[wc_domain]" value="{{ old('settings[wc_domain]', $settings['wc_domain']) }}" maxlength="120" required autofocus>
            @include('partials/field_error', ['field'=>'settings.wc_domain'])
            <p class="form-help">{{ __('Include trailing slash, e.g. https://mall.example.com/') }}</p>
        </div>
    </div>

<div class="form-group{{ $errors->has('settings[wc_public_key]') ? ' has-error' : '' }}">
        <label for="wc_public_key" class="col-sm-2 control-label">{{ __('Consumer Key') }}</label>
        <div class="col-sm-6">
            <input id="wc_public_key" type="text" class="form-control input-sized" name="settings[wc_public_key]" value="{{ old('settings[wc_public_key]', $settings['wc_public_key']) }}" maxlength="80" required>
            @include('partials/field_error', ['field'=>'settings.wc_public_key'])
        </div>
    </div>

<div class="form-group{{ $errors->has('settings[wc_private_key]') ? ' has-error' : '' }}">
        <label for="wc_private_key" class="col-sm-2 control-label">{{ __('Consumer Secret') }}</label>
        <div class="col-sm-6">
            <input id="wc_private_key" type="text" class="form-control input-sized" name="settings[wc_private_key]" value="{{ old('settings[wc_private_key]', $settings['wc_private_key']) }}" maxlength="80" required>
            @include('partials/field_error', ['field'=>'settings.wc_private_key'])
        </div>
    </div>

<div class="form-group{{ $errors->has('settings[wc_sidebar_secret]') ? ' has-error' : '' }}">
        <label for="wc_sidebar_secret" class="col-sm-2 control-label">{{ __('Sidebar webhook secret') }}</label>
        <div class="col-sm-6">
            <input id="wc_sidebar_secret" type="text" class="form-control input-sized" name="settings[wc_sidebar_secret]" value="{{ old('settings[wc_sidebar_secret]', $settings['wc_sidebar_secret'] ?? '') }}" maxlength="80">
            @include('partials/field_error', ['field'=>'settings.wc_sidebar_secret'])
            <p class="form-help">{{ __('Optional. Used by Sidebar Webhook module URL: /modules/woocommerce/sidebar-webhook?secret=…') }}</p>
        </div>
    </div>

    <div class="form-group margin-top">
        <div class="col-sm-6 col-sm-offset-2">
            <button type="submit" class="btn btn-primary">
                {{ __('Save') }}
            </button>
        </div>
    </div>
</form>
