<?php
namespace WPStaging\Backend\Modules\Views\Forms;

use WPStaging\Forms\Elements\Check;
use WPStaging\Forms\Elements\Numeric;
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
    private $form = [];

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

        // DB Copy Query Limit
        $this->form["general"]->add(
            (new Numeric(
                "wpstg_settings[wpstg_query_limit]",
                array(
                "class" => "medium-text",
                "step"  => 1,
                "max"   => 999999,
                "min"   => 0
            )
            ))
            ->setLabel("DB Copy Query Limit")
            ->setDefault(1000)
        );

        // File Copy Batch Size
        $this->form["general"]->add(
            (new Numeric(
                "wpstg_settings[wpstg_batch_size]",
                array(
                "class" => "medium-text",
                "step"  => 1,
                "max"   => 999999,
                "min"   => 0
            )
            ))
            ->setLabel("File Copy Batch Size")
            ->setDefault(2)
        );

        // CPU load priority
        $this->form["general"]->add(
            (new Select(
                "wpstg_settings[wpstg_cpu_load]",
                array(
                "default"   => "Default",
                "high"      => "High (fast)",
                "medium"    => "Medium (average)",
                "low"       => "Low (slow)"
            )
            ))
            ->setLabel("CPU load priority")
            ->setDefault("default")
        );

        // Optimizer
        $this->form["general"]->add(
            (new Check(
                "wpstg_settings[optimizer]",
                array(
                '1' => "Select the plugins you wish to disable during clone process"
            )
            ))
            ->setLabel("Optimizer")
        );

        // Disable admin authorization
        $this->form["general"]->add(
            (new Check(
                "wpstg_settings[disable_admin_login]",
                array(
                '1' => ''
            )
            ))
            ->setLabel("Disable admin authorization")
        );

        // WordPress in subdirectory
        $this->form["general"]->add(
            (new Check(
                "wpstg_settings[wordpress_subdirectory]",
                array(
                '1' => ''
            )
            ))
            ->setLabel("Wordpress in subdirectory")
        );

        // Debug Mode
        $this->form["general"]->add(
            (new Check(
                "wpstg_settings[debug_mode]",
                array(
                '1' => ''
            )
            ))
            ->setLabel("Debug Mode")
        );

        // Remove Data on Uninstall?
        $this->form["general"]->add(
            (new Check(
                "wpstg_settomgs[uninstall_on_delete]",
                array(
                '1' => ''
            )
            ))
            ->setLabel("Remove Data on Uninstall?")
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