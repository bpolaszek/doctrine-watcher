<?php

namespace BenTools\DoctrineWatcher\Changeset;

abstract class PropertyChangeset
{
    public const CHANGESET_DEFAULT = 'scalar';
    public const CHANGESET_ITERABLE = 'iterable';
    public const INSERT = 'insert';
    public const UPDATE = 'update';

    /**
     * @var object
     */
    protected $entity;

    /**
     * @var string
     */
    protected $property;

    /**
     * @var mixed
     */
    protected $newValue;

    /**
     * @var mixed
     */
    protected $oldValue;

    /**
     * @var array
     */
    protected $additions = [];

    /**
     * @var array
     */
    protected $removals = [];

    /**
     * Changeset constructor.
     * @param object $entity
     * @param string $property
     */
    public function __construct($entity, string $property)
    {
        $this->entity = $entity;
        $this->property = $property;
    }


    abstract public function getType(): string;

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return string
     */
    public function getProperty(): string
    {
        return $this->property;
    }

    /**
     * @return mixed
     */
    public function getOldValue()
    {
        return $this->oldValue;
    }

    /**
     * @return mixed
     */
    public function getNewValue()
    {
        return $this->newValue;
    }

    /**
     * @return bool
     */
    public function hasChanges(): bool
    {
        return $this->oldValue !== $this->newValue;
    }

    /**
     * @return iterable
     */
    public function getAdditions(): iterable
    {
        if (self::CHANGESET_ITERABLE !== $this->getType()) {
            throw new \RuntimeException(sprintf('%s can only be called on iterable properties changesets.', __METHOD__));
        }
        return $this->additions;
    }

    /**
     * @return iterable
     */
    public function getRemovals(): iterable
    {
        if (self::CHANGESET_ITERABLE !== $this->getType()) {
            throw new \RuntimeException(sprintf('%s can only be called on iterable properties changesets.', __METHOD__));
        }
        return $this->removals;
    }

    /**
     * @return bool
     */
    public function hasAdditions(): bool
    {
        if (self::CHANGESET_ITERABLE !== $this->getType()) {
            throw new \RuntimeException(sprintf('%s can only be called on iterable properties changesets.', __METHOD__));
        }
        return [] !== $this->additions;
    }

    /**
     * @return bool
     */
    public function hasRemovals(): bool
    {
        if (self::CHANGESET_ITERABLE !== $this->getType()) {
            throw new \RuntimeException(sprintf('%s can only be called on iterable properties changesets.', __METHOD__));
        }
        return [] !== $this->removals;
    }
}
