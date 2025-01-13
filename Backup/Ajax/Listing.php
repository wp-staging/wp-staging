<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Backup\Ajax;

class Listing extends BaseListing
{
    /**
     * @return void
     */
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

    /**
     * @return string
     */
    protected function getTemplate(): string
    {
        return 'backup/listing.php';
    }

    /**
     * @return array
     */
    protected function getCommonRenderData(): array
    {
        $data = parent::getCommonRenderData();
        return array_merge($data, [
            'isProVersion' => false,
            'isValidLicense' => false,
        ]);
    }
}
