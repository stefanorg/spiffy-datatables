<?php

namespace SpiffyDatatables;

use Zend\Stdlib\AbstractOptions;

class ModuleOptions extends AbstractOptions
{
    /**
     * Service configuration for the datatable manager (invokables, factories, etc.).
     *
     * @var array
     */
    protected $manager = array();

    /**
     * An array of datatables to register with the datatable manager. This is handled by the
     * SpiffyDatatables\DatatableAbstractFactory.
     *
     * @var array
     */
    protected $datatables = array();

    /**
     * An array of javascript plugins to load on datatables render
     * @var array
     */
    protected $plugins = array();

    /**
     * @param array $manager
     * @return $this
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
        return $this;
    }

    /**
     * @return array
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param array $datatables
     * @return $this
     */
    public function setDatatables($datatables)
    {
        $this->datatables = $datatables;
        return $this;
    }

    /**
     * @return array
     */
    public function getDatatables()
    {
        return $this->datatables;
    }

    /**
     * Gets the value of plugins.
     *
     * @return mixed
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * Sets the value of plugins.
     *
     * @param mixed $plugins the plugins
     *
     * @return self
     */
    public function setPlugins($plugins)
    {
        $this->plugins = $plugins;

        return $this;
    }
}
