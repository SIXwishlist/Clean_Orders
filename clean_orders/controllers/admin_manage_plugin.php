<?php
class AdminManagePlugin extends AppController
{
    /**
     * Performs necessary initialization
     */
    private function init()
    {
        // Require login
        $this->parent->requireLogin();

        $this->uses(array('CleanOrders.CleanOrdersSettings','CleanOrders.CleanOrdersServices'));

        Language::loadLang('admin_manage_plugin', null, PLUGINDIR . 'clean_orders' . DS . 'language' . DS);

        // Set the page title
        $this->parent->structure->set('page_title', Language::_('AdminManagePlugin.'. Loader::fromCamelCase($this->action ? $this->action : 'index'). '.page_title', true));

        // Set the view to render for all actions under this controller
        $this->view->setView(null, 'CleanOrders.default');
    }

    /**
     * Returns the view to be rendered when managing this plugin
     */
    public function index()
    {
        $this->init();

        $vars = (object) $this->CleanOrdersSettings->getSettings($this->parent->company_id);
		// $test = $this->CleanOrdersServices->cancelUnpaidOrders($this->parent->company_id, "2", $vars->services_action, $vars->invoices_action);
		// echo "<pre>";
		// print_r($test);die();
		
        if (!empty($this->post)) {
            $this->CleanOrdersSettings->setSettings($this->parent->company_id,$this->post);

            if (($error = $this->CleanOrdersSettings->errors())) {
                $this->parent->setMessage('error', $error);
            } else {
                $this->parent->setMessage('message', Language::_('AdminManagePlugin.!success.settings_saved', true));
            }

            $vars = (object)$this->post;
        }

        $days = $this->getDays(1, 60);
        $clear_orders = $this->getDays(7, 60);
        $invoice_actions = $this->getActionsForInvoices();
        $services_actions = $this->getActionsForServices();
        // Set the view to render
        return $this->partial('admin_manage_plugin', compact('vars', 'days', 'invoice_actions', 'services_actions', 'clear_orders'));
    }

    /**
     * Fetch days
     *
     * @param int $min_days
     * @param int $max_days
     * @return array
     */
    private function getDays($min_days, $max_days)
    {
        $days = array('' => Language::_('AdminManagePlugin.getDays.never', true));
        for ($i = $min_days; $i <= $max_days; $i++) {
            $days[$i] = Language::_('AdminManagePlugin.getDays.text_day'. ($i === 1 ? '' : 's'), true, $i);
        }
        return $days;
    }
	
    /**
     * Fetch Actions For Invoices
     *
     * @return array
     */
    private function getActionsForInvoices()
    {
        $action = array(
			'' => Language::_('AdminManagePlugin.getActions.nothing', true),
			'void' => Language::_('AdminManagePlugin.getActions.void', true),
			'delete' => Language::_('AdminManagePlugin.getActions.delete', true),
			
		);

        return $action;
    }
	
    /**
     * Fetch Actions For Services
     *
     * @return array
     */
    private function getActionsForServices()
    {
        $action = array(
			'' => Language::_('AdminManagePlugin.getActions.nothing', true),
			'cancel' => Language::_('AdminManagePlugin.getActions.cancel', true),
			'delete' => Language::_('AdminManagePlugin.getActions.delete', true),
			
		);

        return $action;
    }		
}
