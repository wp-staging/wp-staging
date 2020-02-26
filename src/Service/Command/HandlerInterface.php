<?php

namespace WPStaging\Service\Command;

interface HandlerInterface
{
    public function addCommand(CommandInterface $command);

    public function handle();
}
