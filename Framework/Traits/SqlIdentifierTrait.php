<?php

namespace WPStaging\Framework\Traits;






trait SqlIdentifierTrait
{




    protected function quoteSqlIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
