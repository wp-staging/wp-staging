<?php

namespace WPStaging\Framework\Adapter;

use WPStaging\Framework\Adapter\Dto\HookDto;

final class Hooks
{
    /** @var HookDto[]|array  */
    private $actions = [];

    /** @var HookDto[]|array  */
    private $filters = [];

    /** @noinspection PhpUnused */
    public function addAction(HookDto $dto)
    {
        $this->actions[] = $dto;
    }

    /** @noinspection PhpUnused */
    public function addFilter(HookDto $dto)
    {
        $this->filters[] = $dto;
    }

    public function init()
    {
        foreach ($this->filters as $filter) {
            add_filter(
                $filter->getHook(),
                [$filter->getComponent(), $filter->getCallback()],
                $filter->getPriority(),
                $filter->getAcceptedArgs()
            );
        }

        foreach ($this->actions as $action) {
            add_action(
                $action->getHook(),
                [$action->getComponent(), $action->getCallback()],
                $action->getPriority(),
                $action->getAcceptedArgs()
            );
        }
    }
}
