<?php

namespace WPStaging\Framework\TemplateEngine;

interface TemplateEngineInterface
{
    public function render(string $path, array $params = []): string;
}
