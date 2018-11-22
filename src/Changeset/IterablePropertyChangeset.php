<?php

namespace BenTools\DoctrineWatcher\Changeset;

final class IterablePropertyChangeset extends PropertyChangeset
{

    /**
     * IterableChangeset constructor.
     * @param object        $entity
     * @param string        $property
     * @param iterable|null $oldValue
     * @param iterable|null $newValue
     */
    public function __construct($entity, string $property, ?iterable $oldValue = null, ?iterable $newValue = null)
    {
        parent::__construct($entity, $property);

        $this->oldValue = $oldValue;
        $this->newValue = $newValue;

        $old = iterable_to_array($oldValue ?? []);
        $new = iterable_to_array($newValue ?? []);

        if (!$this->isSequential($old) && !$this->isSequential($new)) {
            $this->additions = array_diff($new, $old);
            $this->removals = array_diff($old, $new);
            return;
        }
        $this->additions = array_values(array_diff($new, $old));
        $this->removals = array_values(array_diff($old, $new));
    }

    /**
     * @inheritDoc
     */
    public function hasChanges(): bool
    {
        return $this->hasAdditions() || $this->hasRemovals();
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return self::CHANGESET_ITERABLE;
    }

    /**
     * @param array $array
     * @return bool
     */
    private function isSequential(array $array): bool
    {
        return isset($array[0]) && array_keys($array) === range(0, count($array) - 1);
    }
}
