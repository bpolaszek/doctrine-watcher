<?php

namespace BenTools\DoctrineWatcher\Changeset;

final class DefaultPropertyChangeset extends PropertyChangeset
{

    /**
     * DefaultChangeset constructor.
     * @param object $entity
     * @param string $property
     * @param null   $oldValue
     * @param null   $newValue
     */
    public function __construct($entity, string $property, $oldValue = null, $newValue = null)
    {
        parent::__construct($entity, $property);
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
