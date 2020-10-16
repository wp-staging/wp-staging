<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x type-hints & return types

namespace WPStaging\Framework\Traits;

use RuntimeException;
use WPStaging\Component\Dto\AbstractDto;

trait RequestNotationTrait
{
    // TODO sanitize POST data
    /**
     * @param string $notation
     * @return array|string|object
     */
    public function resolvePostRequestData($notation)
    {
        $data = isset($_POST['wpstg'])? $_POST['wpstg'] : [];
        $keys = explode('.', $notation);
        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                return [];
            }
            $data = $data[$key];
        }
        return $data;
    }

    /**
     * @param string $className
     * @param string $notation
     * @return object|AbstractDto
     */
    public function initializeRequestDto($className, $notation)
    {
        $data = $this->resolvePostRequestData($notation);
        $dto = (new $className);
        if (!method_exists($dto, 'hydrate')) {
            throw new RuntimeException(sprintf('DTO Class %s does not have hydrate method', $className));
        }
        $dto->hydrate($data);
        return $dto;
    }
}