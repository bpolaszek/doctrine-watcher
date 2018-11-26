<?php

namespace BenTools\DoctrineWatcher\Changeset;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;

class ChangesetFactory
{
    /**
     * @param object        $entity
     * @param UnitOfWork    $unitOfWork
     * @param ClassMetadata $classMetadata
     * @return array
     */
    public function getChangedProperties($entity, UnitOfWork $unitOfWork, ClassMetadata $classMetadata): array
    {
        return array_keys($this->getEntityChangeset($entity, $unitOfWork, $classMetadata));
    }

    /**
     * @param object        $entity
     * @param string        $property
     * @param UnitOfWork    $unitOfWork
     * @param ClassMetadata $classMetadata
     * @param string        $type
     * @return PropertyChangeset
     * @throws \InvalidArgumentException
     */
    public function getChangeset($entity, string $property, UnitOfWork $unitOfWork, ClassMetadata $classMetadata, string $type = PropertyChangeset::CHANGESET_DEFAULT): PropertyChangeset
    {
        if (!in_array($type, [PropertyChangeset::CHANGESET_DEFAULT, PropertyChangeset::CHANGESET_ITERABLE])) {
            throw new \InvalidArgumentException(sprintf('Expected "%s" or "%s", got %s', PropertyChangeset::CHANGESET_DEFAULT, PropertyChangeset::CHANGESET_ITERABLE, $type));
        }
        $changeset = $this->getEntityChangeset($entity, $unitOfWork, $classMetadata)[$property] ?? [];
        switch ($type) {
            case PropertyChangeset::CHANGESET_ITERABLE:
                return new IterablePropertyChangeset(...$changeset);
            case PropertyChangeset::CHANGESET_DEFAULT:
                return new DefaultPropertyChangeset(...$changeset);
        }
    }

    /**
     * @param object        $entity
     * @param UnitOfWork    $unitOfWork
     * @param ClassMetadata $classMetadata
     * @return array
     */
    private function getEntityChangeset($entity, UnitOfWork $unitOfWork, ClassMetadata $classMetadata): array
    {
        if (UnitOfWork::STATE_NEW !== $unitOfWork->getEntityState($entity)) {
            return $unitOfWork->getEntityChangeSet($entity);
        }
        $changeset = [];
        $fieldNames = $classMetadata->getFieldNames();
        foreach ($fieldNames as $fieldName) {
            $reflectionProperty = $classMetadata->getReflectionClass()->getProperty($fieldName);
            $reflectionProperty->setAccessible(true);
            $changeset[$fieldName][] = $classMetadata->getReflectionClass()->getDefaultProperties()[$fieldName] ?? null;
            $changeset[$fieldName][] = $reflectionProperty->getValue($entity);
        }
        $changeset = array_filter($changeset, function ($changes) {
            list($old, $new) = $changes;
            return $old !== $new;
        });
        return $changeset;
    }

    /**
     * @param object        $entity
     * @param string        $property
     * @param UnitOfWork    $unitOfWork
     * @param ClassMetadata $classMetadata
     * @return bool
     */
    public function hasChanges($entity, string $property, UnitOfWork $unitOfWork, ClassMetadata $classMetadata): bool
    {
        $changeset = $this->getEntityChangeSet($entity, $unitOfWork, $classMetadata);
        return array_key_exists($property, $changeset);
    }

    /**
     * @param object     $entity
     * @param UnitOfWork $unitOfWork
     * @return bool
     */
    public function isNotManagedYet($entity, UnitOfWork $unitOfWork): bool
    {
        return UnitOfWork::STATE_NEW === $unitOfWork->getEntityState($entity);
    }
}
