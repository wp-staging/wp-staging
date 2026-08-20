<?php

namespace WPStaging\Core\Forms\Elements;

use WPStaging\Core\Forms\ElementsWithOptions;
use WPStaging\Framework\Facades\UI\Toggle as ToggleFacade;














class Toggle extends ElementsWithOptions
{




    protected function prepareOutput(): string
    {
        $output = '';

        foreach ($this->options as $id => $value) {
            $attributeId = $this->getId();
            $output .= ToggleFacade::render($attributeId, $this->name, $id, $this->isChecked($id));

            if ($value) {
                $output .= "<label for='{$attributeId}'>{$value}</label>";
            }
        }

        return $output;
    }






    private function isChecked(string $value): bool
    {
        if (
            $this->default &&
            (
                (is_string($this->default) && $this->default === $value) ||
                (is_int($this->default) && $this->default === (int)$value) ||
                (is_array($this->default) && in_array($value, $this->default))
            )
        ) {
            return true;
        }

        return false;
    }




    public function render()
    {
        return ($this->renderFile) ? @file_get_contents($this->renderFile) : $this->prepareOutput();
    }
}
