<?php

namespace BenTools\DoctrineWatcher\Changeset;

final class DefaultPropertyChangeset extends PropertyChangeset
{
    /**
     * DefaultChangeset constructor.
     * @param null   $oldValue
     * @param null   $newValue
     */
    public function __construct($oldValue = null, $newValue = null)
    {
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
    }
    /**
     * @return string
     */
    public function getType(): string
    {
        return self::CHANGESET_DEFAULT;
    }
}
