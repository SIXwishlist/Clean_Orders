<?php
class CleanOrdersServices extends AppModel
{

    public function __construct()
    {
        parent::__construct();
        Language::loadLang('clean_orders', null, PLUGINDIR . 'clean_orders' . DS . 'language' . DS);
    }

    /**
     *
     * @param int $company_id
     * @param string $cancel_days
     * @param string $services_action
     * @param string $invoices_action
     */
    public function cancelUnpaidOrders($company_id, $cancel_days, $services_action, $invoices_action) 
	{
        // Can not proceed unless values are non-empty
        if ($cancel_days === '' || $services_action === '' || $invoices_action === '') {
            return;
        }

        $order_timelife = $this->dateToUtc($this->Date->cast(strtotime('-' . $cancel_days . ' days'), 'c'));

		// $this->Record = $this->getOrders("active");		
		$this->Record = $this->getOrders(null);		

		// return // for test purpose
		$orders = $this->Record->where('orders.date_added', '<=', $order_timelife)->group(array("order_number"))->fetchAll();	

        foreach ($orders as $order) {
			
			$order->services = $services = $this->Record->select(array('services.id'))->from("order_services")->
				innerJoin("orders", "order_services.order_id", "=", "orders.id", false)->					
				innerJoin("services", "services.id", "=", "order_services.service_id", false)->
				innerJoin("clients", "clients.id", "=", "services.client_id", false)->
				innerJoin("client_groups", "client_groups.id", "=", "clients.client_group_id", false)->
				where("client_groups.company_id", "=", Configure::get("Blesta.company_id"))->						
				where("order_services.order_id", "=", $order->id)->
				where("services.status", "!=", "active")->
				fetchAll();			
			
			// Load orders model 
			Loader::loadModels($this, array("Order.OrderOrders"));
		
			if ($services_action == 'cancel' && $invoices_action == 'void') {
				$this->OrderOrders->setStatus(array('order_id' => $order->id, 'status' => "canceled"));
				$this->addNoteToInvoice($order->invoice_id);
				// Nothing more ;)
			}
			else {
				// Set Order status and invoice/services to void/canceled
				$this->OrderOrders->setStatus(array('order_id' => $order->id, 'status' => "canceled"));
				$this->addNoteToInvoice($order->invoice_id);

				// delete service if is not active status
				if ($services_action === 'delete') {			
					foreach($services as $service) {
						$this->Record->from("services")->where("id", "=", $service->id)->delete();
					}
				}

				// delete invoice related to the order
				if ($invoices_action === 'delete') {
					$this->Record->from("invoices")->where("id", "=", $order->invoice_id)->delete();
				}
			}
        }
		
		if (!isset($this->Emails))
			Loader::loadModels($this, array("Emails"));
		
		// Send email notifications to staff about the order
		$tags = array(
			'orders' => $orders,
			'services_action' => $services_action,
			'invoices_action' => $invoices_action,
		);
		
		// Fetch all staff that should receive the email notification
		if (!isset($this->OrderStaffSettings))
			Loader::loadModels($this, array("Order.OrderStaffSettings"));
		
		$staff_email = $this->OrderStaffSettings->getStaffWithSetting(Configure::get("Blesta.company_id"), "email_notice", "always");
		
		$to_addresses = array();
		foreach ($staff_email as $staff)
			$to_addresses[] = $staff->email;
		
		// print_r($to_addresses);die();
		// Send to staff email
		$this->Emails->send("CleanOrders.delete_canceled_orders", Configure::get("Blesta.company_id"), null, $to_addresses, $tags);	
		
		// return $orders ;
    }
  
  /**
     *
     * @param int $company_id
     * @param string $clear_orders
     */
    public function deleteCanceledOrders($company_id, $clear_orders) 
	{
        // Can not proceed unless values are non-empty
        if ($clear_orders === '') {
            return;
        }

        $order_timelife = $this->dateToUtc($this->Date->cast(strtotime('-' . $clear_orders . ' days'), 'c'));

		$this->Record = $this->getOrders("canceled");

		// return // for test purpose
		$orders = $this->Record->where('orders.date_added', '<=', $order_timelife)->group(array("order_number"))->fetchAll();

		if (!isset($this->Emails))
			Loader::loadModels($this, array("Emails"));
		
        foreach ($orders as $order) {
			$this->Record->from("order_services")->where("order_id", "=", $order->id)->delete();
			$this->Record->from("orders")->where("id", "=", $order->id)->delete();
        }
		
		// Send email notifications to staff about the order
		$tags = array(
			'orders' => $orders
		);
		
		// Fetch all staff that should receive the email notification
		if (!isset($this->OrderStaffSettings))
			Loader::loadModels($this, array("Order.OrderStaffSettings"));
		
		$staff_email = $this->OrderStaffSettings->getStaffWithSetting(Configure::get("Blesta.company_id"), "email_notice", "always");
		
		$to_addresses = array();
		foreach ($staff_email as $staff)
			$to_addresses[] = $staff->email;
		
		// Send to staff email
		$this->Emails->send("CleanOrders.cancel_unpaid_orders", Configure::get("Blesta.company_id"), null, $to_addresses, $tags);			
    }

	/**
	 * Fetches a partial Record object to fetch all orders of the given status
	 * for the current company
	 *
	 * @param string $status The status of orders to fetch which can be one of, default null for all:
	 * 	- pending
	 * 	- accepted
	 * 	- fraud
	 */
	private function getOrders($status = null) 
	{
		$fields = array("orders.*", 
			'order_forms.label' => "order_form_label",
			'order_forms.name' => "order_form_name", 
			"invoices.client_id" => "client_id", 
			"invoices.total" => "invoice_total",
			"invoices.paid" => "invoice_paid",
			"invoices.date_closed" => "invoice_date_closed",
			"invoices.currency" => "invoice_currency",
			// 'order_services.service_id' => "service_id",
			'REPLACE(invoices.id_format, ?, invoices.id_value)' => "invoice_id_code",
			'REPLACE(clients.id_format, ?, clients.id_value)' => "client_id_code",
			);

		
		$this->Record->select($fields)->
			appendValues(array(
				$this->replacement_keys['invoices']['ID_VALUE_TAG'],
				$this->replacement_keys['clients']['ID_VALUE_TAG']
				)
			)->
			from("orders")->
			innerJoin("order_services", "order_services.order_id", "=", "orders.id", false)->
			innerJoin("invoices", "invoices.id", "=", "orders.invoice_id", false)->
			innerJoin("clients", "clients.id", "=", "invoices.client_id", false)->
			innerJoin("client_groups", "client_groups.id", "=", "clients.client_group_id", false)->
			on("contacts.contact_type", "=", "primary")->
			innerJoin("contacts", "contacts.client_id", "=", "clients.id", false)->
			leftJoin("order_forms", "order_forms.id", "=", "orders.order_form_id", false)->
			where("invoices.date_closed", "=", null)->
			where("client_groups.company_id", "=", Configure::get("Blesta.company_id"));

		if ($status)
			$this->Record->where("orders.status", "=", $status);
			
		return $this->Record;
	}	

	private function addNoteToInvoice($invoice_id)
	{
		// Load invoices model
		Loader::loadModels($this, array("Invoices"));		
		// update invoices
		return $this->Invoices->edit($invoice_id, array(
			'note_public' => Language::_("CleanOrdersServices.invoice.add_note", true),
			'status' => "void"
		));
	}		

}
