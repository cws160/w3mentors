<?php

/**
 * Fat Controller is a base controller for all Controllers in system
 */
class FatController
{

    protected $_controllerName;
    protected $_actionName;
    protected $_template;

    /**
     * Initialize Fat Controller
     * 
     * @param string $action
     */
    function __construct(string $action)
    {
        $this->_controllerName = get_class($this);
        $this->_actionName = $action;
        $this->setAppHeaders();
        $this->_template = new FatTemplate($this->_controllerName, $this->_actionName);
    }

    /**
     * Set variable for view
     * 
     * @param string $name
     * @param int|bool|string|array $value
     */
    function set(string $name, $value)
    {
        $this->_template->set($name, $value);
    }

    /**
     * Set variables for view
     * 
     * @param array $viewData
     */
    function sets(array $viewData = [])
    {
        foreach ($viewData as $key => $value) {
            $this->_template->set($key, $value);
        }
    }

    /**
     * Set Application Headers
     */
    protected function setAppHeaders()
    {
        header('X-Frame-Options: SAMEORIGIN');
        header('Strict-Transport-Security: max-age=10886400');
        header('X-XSS-Protection: 1; mode=block');
        header('X-Content-Type-Options: nosniff');
        /* header('Content-Security-Policy: policy-definition' ); */
        header('Referrer-Policy: no-referrer-when-downgrade');
        header("Pragma: no-cache");
        header('Cache-Control:Private,no-store, must-revalidate, public, max-age=0');
        header_remove('X-Powered-By');
    }

}
