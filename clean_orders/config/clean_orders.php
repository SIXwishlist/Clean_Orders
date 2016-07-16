<?php
// Emails
Configure::set("CleanOrders.install.emails", array(
	array(
		'action' => "CleanOrders.delete_canceled_orders",
		'type' => "staff",
		'plugin_dir' => "clean_orders",
		'tags' => "{orders}",
		'from' => "sales@mydomain.com",
		'from_name' => "Blesta Clean Orders System",
		'subject' => "Canceled Orders List has been deleted",
		'text' => "The fallowing canceled orders has been deleted from listing by the system.

Summary

{% for order in orders %}

Order Form: {order.order_form_name}
Order Number: {order.order_number}
Order Placed: {order.date_added}
Client id: {order.client_id_code}

--
{% endfor %}",
		'html' => "
	<p>The fallowing canceled orders has been deleted from listing by the system.</p>
	<p><strong>Summary</strong></p>
	
	{% for order in orders %}
	<p>
	Order Form: {order.order_form_name}<br />
	Order Number: {order.order_number}<br />
	Order Placed: {order.date_added}<br />
	Client id: {order.client_id_code}<br />
	
	--<br /></p>
	{% endfor %}"
	),
	array(
		'action' => "CleanOrders.cancel_unpaid_orders",
		'type' => "staff",
		'plugin_dir' => "clean_orders",
		'tags' => "{orders},{services_action},{invoices_action}",
		'from' => "sales@mydomain.com",
		'from_name' => "Blesta Clean Orders System",
		'subject' => "Unpaid Orders has been cleaned",
		'text' => "Unpaid orders has been cleaned by the system.

Summary

{% for order in orders %}

Order Form: {order.order_form_name}
Order Number: {order.order_number}
Order Placed: {order.date_added}
Client id: {order.client_id_code}
Invoice id: {order.invoice_id}
Invoice Amount: {order.invoice_total} {order.invoice_currency}
{% for service in order.services %}
Service id: {service.id}
{% endfor %}
--
{% endfor %}

All invoices has been set to : {invoices_action}

All Services has been set to : {services_action}",
		'html' => "
	<p>Unpaid orders has been cleaned by the system.</p>
	<p><strong>Summary</strong></p>
	
	{% for order in orders %}
	<p>
	Order Form: {order.order_form_name}<br />
	Order Number: {order.order_number}<br />
	Order Placed: {order.date_added}<br />
	Client id: {order.client_id_code}<br />
	Invoice id: {order.invoice_id}<br />
	Invoice Amount: {order.invoice_total} {order.invoice_currency}<br />
	{% for service in order.services %}
	Service id: {service.id}<br />
	{% endfor %}	
	--<br />
	</p>
	{% endfor %}
	
	<p>All invoices has been set to : {invoices_action}</p>
	<p>All Services has been set to : {services_action}</p>	
	")
));