<?php

namespace Modules\WooCommerce\Http\Controllers;

use App\Option;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * FreeScout ↔ WooCommerce REST (feibisi fork).
 *
 * - Prefer wc/v3, fallback wc/v2
 * - Resolve customer by email then list orders (more reliable than order search alone)
 * - Basic auth for keys (works behind some reverse proxies that strip query secrets)
 */
class WooCommerceController extends Controller
{
    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '',
        ];

        if (!auth()->user()) {
            $response['msg'] = 'Unauthorized';
            return \Response::json($response, 401);
        }

        switch ($request->action) {
            case 'wc_check_orders':
                $customer_email = trim((string) $request->customer_email);
                if ($customer_email === '' || !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
                    $response['msg'] = 'Invalid customer email';
                    break;
                }

                $result = $this->fetchOrdersForEmail($customer_email);
                if ($result['ok']) {
                    $response['status'] = 'success';
                    $response['data'] = $result['orders'];
                    $response['meta'] = [
                        'api' => $result['api'],
                        'customer_id' => $result['customer_id'],
                        'count' => count($result['orders']),
                    ];
                } else {
                    $response['msg'] = $result['error'] ?: 'WooCommerce request failed';
                    $response['debug'] = $result['debug'] ?? null;
                }
                break;

            case 'wc_test_connection':
                $result = $this->requestWc('orders', ['per_page' => 1]);
                if ($result['ok']) {
                    $response['status'] = 'success';
                    $response['data'] = ['api' => $result['api'], 'http' => $result['http']];
                } else {
                    $response['msg'] = $result['error'] ?: 'Connection failed';
                    $response['debug'] = $result['debug'] ?? null;
                }
                break;

            default:
                $response['msg'] = 'Unknown action';
                break;
        }

        if ($response['status'] === 'error' && empty($response['msg'])) {
            $response['msg'] = 'Unknown error';
        }

        return \Response::json($response);
    }

    /**
     * Public HTML fragment for Sidebar Webhook (no session; shared secret).
     * GET/POST: email= & secret=
     */
    public function sidebarWebhook(Request $request)
    {
        // fulldecent/freescout-sidebar-webhook POSTs JSON:
        // customerEmail, customerEmails[], secret, conversationId, ...
        $payload = $request->all();
        if ($request->getContent()) {
            $json = json_decode($request->getContent(), true);
            if (is_array($json)) {
                $payload = array_merge($payload, $json);
            }
        }

        $secret = (string) Option::get('wc_sidebar_secret', '');
        $given = (string) (
            $payload['secret']
            ?? $request->input('secret')
            ?? $request->header('X-WC-Sidebar-Secret')
            ?? ''
        );
        if ($secret === '' || !hash_equals($secret, $given)) {
            return response('Forbidden', 403);
        }

        $email = trim((string) (
            $payload['customerEmail']
            ?? $payload['email']
            ?? $payload['customer_email']
            ?? data_get($payload, 'customer.email')
            ?? ''
        ));
        if ($email === '' && !empty($payload['customerEmails'][0])) {
            $email = trim((string) $payload['customerEmails'][0]);
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response(
                '<div class="wc-sidebar-empty" style="padding:8px;color:#888">无有效客户邮箱</div>',
                200,
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }

        $result = $this->fetchOrdersForEmail($email, 8);
        $domain = rtrim((string) Option::get('wc_domain', ''), '/') . '/';
        $html = $this->renderOrdersHtml($result, $domain, $email);

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * @return array{ok:bool,orders?:array,api?:string,customer_id?:int|null,error?:string,debug?:array}
     */
    protected function fetchOrdersForEmail(string $email, int $limit = 10): array
    {
        $customerId = null;
        $apiUsed = null;

        // 1) customers?email=
        $cust = $this->requestWc('customers', [
            'email' => $email,
            'role' => 'all',
            'per_page' => 5,
        ]);
        if ($cust['ok'] && is_array($cust['data']) && count($cust['data']) > 0) {
            $customerId = (int) ($cust['data'][0]['id'] ?? 0) ?: null;
            $apiUsed = $cust['api'];
        }

        // 2) orders by customer id, else search
        if ($customerId) {
            $ordersRes = $this->requestWc('orders', [
                'customer' => $customerId,
                'per_page' => $limit,
                'orderby' => 'date',
                'order' => 'desc',
            ], $apiUsed);
        } else {
            $ordersRes = $this->requestWc('orders', [
                'search' => $email,
                'per_page' => $limit,
                'orderby' => 'date',
                'order' => 'desc',
            ]);
        }

        if (!$ordersRes['ok']) {
            return [
                'ok' => false,
                'error' => $ordersRes['error'] ?? 'orders request failed',
                'debug' => $ordersRes['debug'] ?? null,
                'customer_id' => $customerId,
            ];
        }

        $orders = is_array($ordersRes['data']) ? $ordersRes['data'] : [];
        // Normalize for sidebar JS
        $normalized = [];
        foreach ($orders as $o) {
            if (!is_array($o)) {
                continue;
            }
            $id = (int) ($o['id'] ?? 0);
            $normalized[] = [
                'id' => $id,
                'number' => (string) ($o['number'] ?? $id),
                'status' => (string) ($o['status'] ?? ''),
                'currency' => (string) ($o['currency'] ?? 'CNY'),
                'total' => (string) ($o['total'] ?? '0'),
                'date_created' => (string) ($o['date_created'] ?? $o['date_created_gmt'] ?? ''),
                'payment_method_title' => (string) ($o['payment_method_title'] ?? ''),
                'billing' => $o['billing'] ?? [],
                'line_items_count' => is_array($o['line_items'] ?? null) ? count($o['line_items']) : 0,
                // HPOS-friendly admin URL (works on classic too when routed)
                'admin_url' => $this->adminOrderUrl($id),
            ];
        }

        return [
            'ok' => true,
            'orders' => $normalized,
            'api' => $ordersRes['api'] ?? $apiUsed,
            'customer_id' => $customerId,
        ];
    }

    protected function adminOrderUrl(int $orderId): string
    {
        $domain = rtrim((string) Option::get('wc_domain', ''), '/') . '/';
        // WC 8+ HPOS
        return $domain . 'wp-admin/admin.php?page=wc-orders&action=edit&id=' . $orderId;
    }

    /**
     * @param array|null $preferApi 'wc/v3'|'wc/v2'|null
     * @return array{ok:bool,data?:mixed,api?:string,http?:int,error?:string,debug?:array}
     */
    protected function requestWc(string $resource, array $query = [], ?string $preferApi = null): array
    {
        $domain = Option::get('wc_domain', '');
        $publicKey = Option::get('wc_public_key', '');
        $privateKey = Option::get('wc_private_key', '');

        if (!$domain || !$publicKey || !$privateKey) {
            return ['ok' => false, 'error' => 'WooCommerce domain/keys not configured (Settings → WooCommerce)'];
        }

        $domain = rtrim($domain, '/') . '/';
        $apis = $preferApi ? [$preferApi, $preferApi === 'wc/v3' ? 'wc/v2' : 'wc/v3'] : ['wc/v3', 'wc/v2'];
        $apis = array_values(array_unique($apis));

        $last = ['ok' => false, 'error' => 'no response'];
        foreach ($apis as $api) {
            $url = $domain . 'wp-json/' . $api . '/' . ltrim($resource, '/');
            if ($query) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
            }

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 18,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_USERPWD => $publicKey . ':' . $privateKey,
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'User-Agent: FreeScout-WooCommerce-feibisi/1.2',
                ],
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $body = curl_exec($ch);
            $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $cerr = curl_error($ch);
            curl_close($ch);

            if ($body === false || $http === 0) {
                $last = ['ok' => false, 'error' => 'cURL: ' . $cerr, 'api' => $api, 'http' => $http];
                continue;
            }

            $data = json_decode($body, true);
            if ($http >= 200 && $http < 300 && $data !== null) {
                return ['ok' => true, 'data' => $data, 'api' => $api, 'http' => $http];
            }

            $msg = is_array($data) ? ($data['message'] ?? json_encode($data)) : substr((string) $body, 0, 180);
            $last = [
                'ok' => false,
                'error' => "HTTP {$http}: {$msg}",
                'api' => $api,
                'http' => $http,
                'debug' => ['api' => $api, 'http' => $http],
            ];
            // 404 on v3 → try v2; 401 hard fail both
            if ($http === 401 || $http === 403) {
                break;
            }
        }

        return $last;
    }

    protected function renderOrdersHtml(array $result, string $domain, string $email): string
    {
        if (!$result['ok']) {
            $err = htmlspecialchars($result['error'] ?? 'error', ENT_QUOTES, 'UTF-8');
            return '<div class="wc-sidebar" style="font-size:12px;padding:6px 0"><strong>商城订单</strong><div style="color:#c00">' . $err . '</div></div>';
        }
        $orders = $result['orders'] ?? [];
        $emailH = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        if (!$orders) {
            return '<div class="wc-sidebar" style="font-size:12px;padding:6px 0"><strong>商城订单</strong><div style="color:#888">无订单 · ' . $emailH . '</div></div>';
        }
        $li = '';
        foreach ($orders as $o) {
            $num = htmlspecialchars((string) $o['number'], ENT_QUOTES, 'UTF-8');
            $st = htmlspecialchars((string) $o['status'], ENT_QUOTES, 'UTF-8');
            $total = htmlspecialchars((string) $o['total'] . ' ' . ($o['currency'] ?? ''), ENT_QUOTES, 'UTF-8');
            $date = htmlspecialchars(substr((string) $o['date_created'], 0, 10), ENT_QUOTES, 'UTF-8');
            $link = htmlspecialchars((string) $o['admin_url'], ENT_QUOTES, 'UTF-8');
            $pay = htmlspecialchars((string) ($o['payment_method_title'] ?? ''), ENT_QUOTES, 'UTF-8');
            $li .= '<li style="margin:0 0 8px;padding:0 0 6px;border-bottom:1px solid #eee">'
                . '<div><a href="' . $link . '" target="_blank" rel="noopener">#' . $num . '</a> '
                . '<b>' . $total . '</b></div>'
                . '<div style="color:#666">' . $date . ' · <span>' . $st . '</span>'
                . ($pay ? ' · ' . $pay : '') . '</div></li>';
        }
        return '<div class="wc-sidebar" style="font-size:12px;padding:6px 0">'
            . '<strong>商城订单</strong> <span style="color:#888">(' . count($orders) . ')</span>'
            . '<ul style="list-style:none;margin:8px 0 0;padding:0">' . $li . '</ul>'
            . '<div style="color:#aaa;margin-top:4px">' . $emailH . '</div></div>';
    }
}
