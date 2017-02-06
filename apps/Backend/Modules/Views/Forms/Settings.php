<?php
namespace WPStaging\Backend\Modules\Views\Forms;

use WPStaging\Forms\Elements\Check;
use WPStaging\Forms\Elements\Numeric;
use WPStaging\Forms\Elements\Select;
use WPStaging\Forms\Form;

/**
 * Class Settings
 * @package WPStaging\Backend\Modules\Views\Forms
 */
class Settings
{

    /**
     * @var Form
     */
    private $form;

    public function __construct()
    {
        $this->set();
    }

    public function set()
    {
        $this->form = new Form();

        // DB Copy Query Limit
        $this->form->add(new Numeric(
            "wpstg_settings[wpstg_query_limit]",
            array(
                "class" => "medium-text",
                "step"  => 1,
                "max"   => 999999,
                "min"   => 0
            )
        ));

        // File Copy Batch Size
        $this->form->add(new Numeric(
            "wpstg_settings[wpstg_settings[wpstg_batch_size]]",
            array(
                "class" => "medium-text",
                "step"  => 1,
                "max"   => 999999,
                "min"   => 0
            )
        ));

        // CPU load priority
        $this->form->add(new Select(
            "wpstg_settings[wpstg_cpu_load]",
            array(
                "default"   => "Default",
                "high"      => "High (fast)",
                "medium"    => "Medium (average)",
                "low"       => "Low (slow)"
            )
        ));

        // Optimizer
        $this->form->add(new Check(
            "wpstg_settings[optimizer]",
            array(
                '1' => "Select the plugins you wish to disable during clone process"
            )
        ));

        // Disable admin authorization
        $this->form->add(new Check(
            "wpstg_settings[disable_admin_login]",
            array(
                '1' => ''
            )
        ));

        // Wordpress in subdirectory
        $this->form->add(new Check(
            "wpstg_settings[wordpress_subdirectory]",
            array(
                '1' => ''
            )
        ));

        // Debug Mode
        $this->form->add(new Check(
            "wpstg_settings[debug_mode]",
            array(
                '1' => ''
            )
        ));

        // Remove Data on Uninstall?
        $this->form->add(new Check(
            "wpstg_settomgs[uninstall_on_delete]",
            array(
                '1' => ''
            )
        ));
    }

    /**
     * @return Form
     */
    public function get()
    {
        return $this->form;
    }
}