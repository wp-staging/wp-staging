<?php

 
 

namespace WPStaging\Backup\Ajax;

class Listing extends BaseListing
{



    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        if (is_multisite()) {
            $result = $this->templateEngine->render('backup/free-version.php');
        } else {
            $directories = $this->getDirectories();
            $result = $this->templateEngine->render(
                $this->getTemplate(),
                array_merge($this->getCommonRenderData(), ['directories' => $directories])
            );
        }

        wp_send_json($result);
    }




    protected function getTemplate(): string
    {
        return 'backup/listing.php';
    }




    protected function getCommonRenderData(): array
    {
        $data = parent::getCommonRenderData();
        return array_merge($data, [
            'isProVersion'      => false,
            'isValidLicense'    => false,
            'isPersonalLicense' => false,
            'licenseType'       => 'basic', 
        ]);
    }
}
