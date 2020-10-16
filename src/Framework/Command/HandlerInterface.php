<?php

namespace WPStaging\Framework\Command;

interface HandlerInterface
{
    public function addCommand(CommandInterface $command);

    public function handle();
}
