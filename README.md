# freescout-woocommerce (feibisi fork)

FreeScout 侧栏展示客户 WooCommerce 订单。

- **上游**: [passatgt/freescout-woocommerce](https://github.com/passatgt/freescout-woocommerce) (Peter Viszt, 2020, v1.0)
- **维护**: feibisi / feicode.com `freescout` 组织
- **安装目录名**: 必须为 `Modules/WooCommerce`

## 为何 fork

1. 上游停更；官方 freescout.net 模块为付费闭源  
2. 现代 WooCommerce REST（v3）与认证需适配  
3. 与薇晓朵商城、joy 神州工单联动要可控

## 安装

```bash
# 到 FreeScout 服务器
cd /www/wwwroot/support.weixiaoduo.com/Modules
git clone https://feicode.com/freescout/freescout-woocommerce.git WooCommerce
# 或 rsync 后 chown www:www
php artisan freescout:module-install  # 若可用；否则后台 Modules 激活
```

后台 **Settings → WooCommerce**：`https://mall.xxx/`（末尾 `/`）+ WC REST Consumer Key/Secret。

## 路线图

- [ ] WC REST v3 优先、v2 回退  
- [ ] Application Password / 更稳的鉴权  
- [ ] 订单列表字段对齐现代 WC（支付方式、订阅）  
- [ ] 与 joy 神州工单面板共享订单摘要 API  

## License

沿用上游 AGPL-3.0（见 module.json）。
