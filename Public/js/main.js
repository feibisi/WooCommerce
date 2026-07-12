function loadWooCommerceOrders(customer_email, domain, locale) {
	$(document).ready(function () {
		var email_address_id = String(customer_email || '').replace(/[^A-Za-z0-9\-]/g, '');
		var cacheKey = 'wc_customer_v2_' + email_address_id;
		var order_data = getWithExpiry(cacheKey);
		var $order_list = $('.woocommerce-order-list');
		var $order_list_reload = $('.woocommerce-order-list-reload');
		var $not_found = '<li class="not-found">' + ($order_list.data('not-found') || 'Orders not found') + '</li>';
		var $orders_box = $('.woocommerce-orders');

		if (!order_data) {
			get_orders();
		} else {
			display_orders(order_data);
		}

		$order_list_reload.off('click.wc').on('click.wc', function (e) {
			e.preventDefault();
			$orders_box.addClass('loading');
			localStorage.removeItem(cacheKey);
			get_orders();
			return false;
		});

		function get_orders() {
			fsAjax(
				{
					action: 'wc_check_orders',
					customer_email: customer_email,
				},
				laroute.route('woocommerce.ajax'),
				function (response) {
					if (!response || response.status !== 'success') {
						$orders_box.removeClass('loading');
						$order_list.html(
							'<li class="not-found">' +
								(response && response.msg ? response.msg : 'Load failed') +
								'</li>'
						);
						return;
					}
					// Cache 30 min
					setWithExpiry(cacheKey, response.data, 30 * 60);
					display_orders(response.data);
				},
				true
			);
		}

		function display_orders(order_data) {
			$order_list.html('');
			$orders_box.removeClass('loading');

			if (!Array.isArray(order_data) || order_data.length < 1) {
				$order_list.append($not_found);
				return;
			}

			order_data.forEach(function (order) {
				var template = $('#wc-order-item-template').html();
				var currency = order.currency || 'CNY';
				var total;
				try {
					total = new Intl.NumberFormat(locale || 'zh-CN', {
						style: 'currency',
						currency: currency,
					}).format(order.total);
				} catch (e) {
					total = order.total + ' ' + currency;
				}
				var edit_link =
					order.admin_url ||
					domain + 'wp-admin/admin.php?page=wc-orders&action=edit&id=' + order.id;
				var datetime = '';
				try {
					var date = new Date(order.date_created);
					if (!isNaN(date.getTime())) {
						datetime = Intl.DateTimeFormat(locale || 'zh-CN').format(date);
					} else {
						datetime = String(order.date_created || '').slice(0, 10);
					}
				} catch (e2) {
					datetime = String(order.date_created || '').slice(0, 10);
				}

				var data = {
					number: order.number || order.id,
					total: total,
					date: datetime,
					status: order.status || '',
					link: edit_link,
					payment: order.payment_method_title || '',
				};

				Object.keys(data).forEach(function (key) {
					template = template.split('{' + key + '}').join(data[key]);
				});

				$order_list.append(template);
			});
		}

		function setWithExpiry(key, value, ttl) {
			var now = new Date();
			localStorage.setItem(
				key,
				JSON.stringify({
					value: value,
					expiry: now.getTime() + ttl * 1000,
				})
			);
		}

		function getWithExpiry(key) {
			var itemStr = localStorage.getItem(key);
			if (!itemStr) return null;
			try {
				var item = JSON.parse(itemStr);
				if (new Date().getTime() > item.expiry) {
					localStorage.removeItem(key);
					return null;
				}
				return item.value;
			} catch (e) {
				return null;
			}
		}
	});
}
