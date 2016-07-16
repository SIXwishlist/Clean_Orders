<?php
class CleanOrdersSettings extends AppModel
{
    public function __construct()
    {
        parent::__construct();

        if (!isset($this->SettingsCollection))
            Loader::loadComponents($this, array('SettingsCollection'));

        Language::loadLang('clean_orders', null, PLUGINDIR . 'clean_orders' . DS . 'language' . DS);
    }

    /**
     * Fetches settings
     *
     * @param int $company_id
     * @return array
     */
    public function getSettings($company_id)
    {
        $supported = $this->supportedSettings();
        $company_settings = $this->SettingsCollection->fetchSettings(null, $company_id);
        $settings = array();
        foreach ($company_settings as $setting => $value) {
            if (($index = array_search($setting, $supported)) !== false) {
                $settings[$index] = $value;
            }
        }
        return $settings;
    }

    /**
     * Set settings
     *
     * @param int $company_id
     * @param array $settings Key/value pairs
     */
    public function setSettings($company_id, array $settings)
    {
        if (!isset($this->Companies))
            Loader::loadModels($this, array('Companies'));

        $valid_settings = array();
        foreach ($this->supportedSettings() as $key => $name) {
            if (array_key_exists($key, $settings)) {
                $valid_settings[$name] = $settings[$key];
            }
        }

        $this->Input->setRules($this->getRules($valid_settings));
        if ($this->Input->validates($valid_settings)) {
            $this->Companies->setSettings($company_id, $valid_settings);
        }
    }

    /**
     * Fetch supported settings
     *
     * @return array
     */
    public function supportedSettings()
    {
        return array(
            'cancel_days' => 'clean_orders.cancel_days',
            'invoices_action' => 'clean_orders.invoices_action',
            'services_action' => 'clean_orders.services_action',
            'clear_orders' => 'clean_orders.clear_orders',
        );
    }

    /**
     * Input validate rules
     *
     * @param array $vars
     * @return array
     */
    private function getRules($vars)
    {
        return array(
            'clean_orders.cancel_days' => array(
                'valid' => array(
                    'rule' => array(array($this, 'isValidDay')),
                    'message' => $this->_('CleanOrdersSettings.!error.cancel_days.valid')
                )
            ),
			'clean_orders.invoices_action' => array(
				'valid' => array(
					'rule' => array("in_array", array("", "delete", "void")),
					'message' => $this->_('CleanOrdersSettings.!error.invoices_action.valid')
				)
			),	
			'clean_orders.services_action' => array(
				'valid' => array(
					'rule' => array("in_array", array("", "delete", "cancel")),
					'message' => $this->_('CleanOrdersSettings.!error.services_action.valid')
				)
			),				
            'clean_orders.clear_orders' => array(
                'valid' => array(
                    'rule' => array(array($this, 'isValidDay')),
                    'message' => $this->_('CleanOrdersSettings.!error.cancel_days.valid')
                )
            ),
        );
    }

    /**
     * Validate the day given
     *
     * @param string $day
     * @return boolean
     */
    public function isValidDay($day)
    {
        return $day === '' || ($day >= 0 && $day <= 60);
    }
}
