<?php

namespace BenTools\DoctrineWatcher\Changeset;

class PropertyChangeset
{
    public const INSERT = 'insert';
    public const UPDATE = 'update';

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
    protected $additions;

    /**
     * @var array
     */
    protected $removals;

    /**
     * PropertyChangeset constructor.
     *
     * @param mixed $newValue
     * @param mixed $oldValue
     */
    public function __construct($oldValue = null, $newValue = null)
    {
        $this->newValue = $newValue;
        $this->oldValue = $oldValue;
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
        if (!$this->canBeComparedAsIterables()) {
            throw new \RuntimeException(sprintf('%s can only be called on iterable properties changesets.', __METHOD__));
        }

        $this->computeAdditionsAndRemovals();

        return $this->additions;
    }

    /**
     * @return iterable
     */
    public function getRemovals(): iterable
    {
        if (!$this->canBeComparedAsIterables()) {
            throw new \RuntimeException(sprintf('%s can only be called on iterable properties changesets.', __METHOD__));
        }

        $this->computeAdditionsAndRemovals();

        return $this->removals;
    }

    /**
     * @return bool
     */
    public function hasAdditions(): bool
    {
        if (!$this->canBeComparedAsIterables()) {
            throw new \RuntimeException(sprintf('%s can only be called on iterable properties changesets.', __METHOD__));
        }

        $this->computeAdditionsAndRemovals();

        return [] !== $this->additions;
    }

    /**
     * @return bool
     */
    public function hasRemovals(): bool
    {
        if (!$this->canBeComparedAsIterables()) {
            throw new \RuntimeException(sprintf('%s can only be called on iterable properties changesets.', __METHOD__));
        }

        $this->computeAdditionsAndRemovals();

        return [] !== $this->removals;
    }

    /**
     * @param $value
     * @return bool
     */
    private function isNullOrIterable($value): bool
    {
        return null === $value || \is_iterable($value);
    }

    /**
     * @param $oldValue
     * @param $newValue
     * @return bool
     */
    private function canBeComparedAsIterables(): bool
    {
        return $this->isNullOrIterable($this->oldValue) && $this->isNullOrIterable($this->newValue);
    }

    /**
     *
     */
    private function computeAdditionsAndRemovals(): void
    {
        if (null !== $this->additions) {
            return;
        }

        $old = iterable_to_array($this->oldValue ?? []);
        $new = iterable_to_array($this->newValue ?? []);

        if (!$this->isSequential($old) && !$this->isSequential($new)) {
            $this->additions = $this->diff($new, $old);
            $this->removals = $this->diff($old, $new);
            return;
        }
        $this->additions = \array_values($this->diff($new, $old));
        $this->removals = \array_values($this->diff($old, $new));
    }

    /**
     * @param array $array
     * @return bool
     */
    private function isSequential(array $array): bool
    {
        return isset($array[0]) && \array_keys($array) === \range(0, \count($array) - 1);
    }

    /**
     * @param array $a
     * @param array $b
     * @return array
     */
    private function diff(array $a, array $b): array
    {
        return \array_filter($a, function ($item) use ($b) {
            return !\in_array($item, $b, true);
        });
    }
}
