<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPStaging\Vendor\Symfony\Component\Finder\Exception;

@\trigger_error('The ' . __NAMESPACE__ . '\\ShellCommandFailureException class is deprecated since Symfony 2.8 and will be removed in 3.0.', \E_USER_DEPRECATED);
use WPStaging\Vendor\Symfony\Component\Finder\Adapter\AdapterInterface;
use WPStaging\Vendor\Symfony\Component\Finder\Shell\Command;
/**
 * @author Jean-François Simon <contact@jfsimon.fr>
 *
 * @deprecated since 2.8, to be removed in 3.0.
 */
class ShellCommandFailureException extends \WPStaging\Vendor\Symfony\Component\Finder\Exception\AdapterFailureException
{
    private $command;
    public function __construct(\WPStaging\Vendor\Symfony\Component\Finder\Adapter\AdapterInterface $adapter, \WPStaging\Vendor\Symfony\Component\Finder\Shell\Command $command, \Exception $previous = null)
    {
        $this->command = $command;
        parent::__construct($adapter, 'Shell command failed: "' . $command->join() . '".', $previous);
    }
    /**
     * @return Command
     */
    public function getCommand()
    {
        return $this->command;
    }
}
