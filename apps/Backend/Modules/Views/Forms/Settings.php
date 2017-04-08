<?php
namespace WPStaging\Backend\Modules\Views\Forms;

use WPStaging\Forms\Elements\Check;
use WPStaging\Forms\Elements\Numerical;
use WPStaging\Forms\Elements\Select;
use WPStaging\Forms\Form;
use WPStaging\Backend\Modules\Views\Tabs\Settings as Tabs;

/**
 * Class Settings
 * @package WPStaging\Backend\Modules\Views\Forms
 */
class Settings
{

    /**
     * @var array
     */
    private $form = array();

    private $tabs;

    /**
     * Settings constructor.
     * @param Tabs $tabs
     */
    public function __construct($tabs)
    {
        $this->tabs = $tabs;

        foreach ($this->tabs->get() as $id => $name)
        {
            if (!method_exists($this, $id))
            {
                continue;
            }

            $this->{$id}();
        }
    }

    private function general()
    {
        $this->form["general"] = new Form();

        $settings = json_decode(json_encode(get_option("wpstg_settings", array())));

        // DB Copy Query Limit
        $element = new Numerical(
            "wpstg_settings[queryLimit]",
            array(
                "class" => "medium-text",
                "step"  => 1,
                "max"   => 999999,
                "min"   => 0
            )
        );

        $this->form["general"]->add(
            $element->setLabel("DB Copy Query Limit")
            ->setDefault(isset($settings->queryLimit) ? $settings->queryLimit : 1000)
        );

        // File Copy Batch Size
        $element = new Numerical(
            "wpstg_settings[batchSize]",
            array(
                "class" => "medium-text",
                "step"  => 1,
                "max"   => 999999,
                "min"   => 0
            )
        );

        $this->form["general"]->add(
            $element->setLabel("File Copy Batch Size")
            ->setDefault(isset($settings->batchSize) ? $settings->batchSize : 2)
        );

        // CPU load priority
        $element = new Select(
            "wpstg_settings[cpuLoad]",
            array(
                "default"   => "Default",
                "high"      => "High (fast)",
                "medium"    => "Medium (average)",
                "low"       => "Low (slow)"
            )
        );

        $this->form["general"]->add(
            $element->setLabel("CPU load priority")
            ->setDefault(isset($settings->cpuLoad) ? $settings->cpuLoad : "default")
        );

        // Optimizer
        $element = new Check(
            "wpstg_settings[optimizer]",
            array('1' => "Select the plugins you wish to disable during clone process")
        );

        $this->form["general"]->add(
            $element->setLabel("Optimizer")
            ->setDefault((isset($settings->optimizer)) ? $settings->optimizer : null)
        );

        // Disable admin authorization
        $element = new Check(
            "wpstg_settings[disableAdminLogin]",
            array('1' => '')
        );

        $this->form["general"]->add(
            $element->setLabel("Disable admin authorization")
                ->setDefault((isset($settings->disableAdminLogin)) ? $settings->disableAdminLogin : null)
        );

        // WordPress in subdirectory
        $element = new Check(
            "wpstg_settings[wpSubDirectory]",
            array('1' => '')
        );

        $this->form["general"]->add(
            $element->setLabel("Wordpress in subdirectory")
                ->setDefault((isset($settings->wpSubDirectory)) ? $settings->wpSubDirectory : null)
        );

        // Debug Mode
        $element = new Check(
            "wpstg_settings[debugMode]",
            array('1' => '')
        );

        $this->form["general"]->add(
            $element->setLabel("Debug Mode")
                ->setDefault((isset($settings->debugMode)) ? $settings->debugMode : null)
        );

        // Remove Data on Uninstall?
        $element = new Check(
            "wpstg_settings[unInstallOnDelete]",
            array('1' => '')
        );

        $this->form["general"]->add(
            $element->setLabel("Remove Data on Uninstall?")
                ->setDefault((isset($settings->unInstallOnDelete)) ? $settings->unInstallOnDelete : null)
        );

        // Check Directory Sizes
        $element = new Check(
            "wpstg_settings[checkDirectorySize]",
            array('1' => '')
        );

        $this->form["general"]->add(
            $element->setLabel("Check Directory Size")
                ->setDefault((isset($settings->checkDirectorySize)) ? $settings->checkDirectorySize : null)
        );
    }

    /**
     * @param string $name
     * @return array|Form
     */
    public function get($name = null)
    {
        return (null === $name) ? $this->form : $this->form[$name];
    }
}