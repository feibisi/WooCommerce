# FreeScout WooCommerce (feibisi)

侧栏按客户邮箱拉取商城订单。维护于 [feicode.com/freescout/WooCommerce](https://feicode.com/freescout/WooCommerce)。

## v1.2.0

- WC REST **v3 优先**，失败回退 v2
- 先 `customers?email=` 再 `orders?customer=`（比单纯 search 准）
- Basic Auth 传 Key（兼容部分网关）
- 订单后台链接兼容 **HPOS**（`wc-orders&action=edit`）
- 修复侧栏模板 `{link}` 未替换
- **Sidebar Webhook** HTML：`/modules/woocommerce/sidebar-webhook?secret=…`
- 设置项 `wc_sidebar_secret`

## 安装

模块目录名必须是 `Modules/WooCommerce`。

```bash
cd /path/to/freescout/Modules
git clone https://feicode.com/freescout/WooCommerce.git WooCommerce
chown -R www-data:www-data WooCommerce   # 或 www:www
php artisan freescout:module-install woocommerce
php artisan freescout:module-laroute
```

**Settings → WooCommerce**：域名（末尾 `/`）+ Consumer Key/Secret。

## Sidebar Webhook

1. 启用模块 **Sidebar Webhook**
2. 邮箱设置填：

```text
https://support.weixiaoduo.com/modules/woocommerce/sidebar-webhook?secret=YOUR_SECRET
```

3. Settings → WooCommerce 填相同 `Sidebar webhook secret`

## Upstream

[passatgt/freescout-woocommerce](https://github.com/passatgt/freescout-woocommerce) 1.0 (2020) AGPL-3.0
