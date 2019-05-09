<?php

namespace BenTools\DoctrineWatcher\Watcher;

use BenTools\DoctrineWatcher\Changeset\ChangesetFactory;
use BenTools\DoctrineWatcher\Changeset\PropertyChangeset;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;

final class DoctrineWatcher implements EventSubscriber
{
    public const DEFAULT_OPTIONS = [
        'trigger_on_persist'      => false,
        'trigger_when_no_changes' => false,
    ];

    /**
     * @var array
     */
    private $defaulOptions;

    /**
     * @var ChangesetFactory
     */
    private $changesetFactory;

    /**
     * @var callable[]
     */
    private $listeners = [];

    /**
     * DoctrineWatcher constructor.
     *
     * @param array                 $options
     * @param ChangesetFactory|null $changesetFactory
     */
    public function __construct(
        array $options = self::DEFAULT_OPTIONS,
        ChangesetFactory $changesetFactory = null
    ) {
        $this->defaulOptions = \array_replace(self::DEFAULT_OPTIONS, $options);
        $this->changesetFactory = $changesetFactory ?? new ChangesetFactory();
    }

    /**
     * @param string   $entityClass
     * @param          $property
     * @param callable $callback
     * @param array    $options
     * @throws \InvalidArgumentException
     */
    public function watch(string $entityClass, $property, callable $callback, array $options = []): void
    {
        if (\is_array($property)) {
            foreach ($property as $prop) {
                $this->watch($entityClass, $prop, $callback, $options);
            }
            return;
        }

        if (!\is_string($property)) {
            throw new \InvalidArgumentException(\sprintf('Expected property, got %s.', \is_object($property) ? \get_class($property) : \gettype($property)));
        }

        $options = \array_replace($this->defaulOptions, $options);
        $listener = $this->createPropertyListener($entityClass, $property, $callback, $options);
        $this->listeners[$entityClass][$property][] = $listener;
    }

    /**
     * @param string   $entityClass
     * @param string   $property
     * @param callable $callback
     * @param array    $options
     */
    public function watchIterable(string $entityClass, string $property, callable $callback, array $options = []): void
    {
        \trigger_error(\sprintf('%s is deprecated as it is now an alias of %s.', __METHOD__, strtr(__METHOD__, ['watchIterable' => 'watch'])), \E_USER_DEPRECATED);
        $this->watch($entityClass, $property, $callback, $options);
    }

    /**
     * @param string   $entityClass
     * @param string   $property
     * @param callable $callback
     * @param array    $options
     * @return callable
     */
    private function createPropertyListener(string $entityClass, string $property, callable $callback, array $options = []): callable
    {
        return function (LifecycleEventArgs $eventArgs, string $operationType) use ($entityClass, $property, $callback, $options) {
            $em = $eventArgs->getEntityManager();
            $unitOfWork = $em->getUnitOfWork();
            $entity = $eventArgs->getEntity();

            // Do not trigger on the wrong entity
            if (!$entity instanceof $entityClass) {
                return;
            }

            // Do not trigger if entity was not managed
            if (false === $options['trigger_on_persist'] && PropertyChangeset::INSERT === $operationType) {
                return;
            }

            // Do not trigger if field has no changes
            $className = ClassUtils::getClass($entity);
            $classMetadata = $em->getClassMetadata($className);
            $changedProperties = $this->changesetFactory->getChangedProperties($entity, $unitOfWork, $classMetadata);
            if (!\in_array($property, $changedProperties) && false === $options['trigger_when_no_changes']) {
                return;
            }

            $changeset = $this->changesetFactory->getChangeset($entity, $property, $unitOfWork, $classMetadata);
            $callback(
                $changeset,
                $operationType,
                $entity,
                $property
            );
        };
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     * @param string             $operationType
     */
    private function trigger(LifecycleEventArgs $eventArgs, string $operationType): void
    {
        $entity = $eventArgs->getEntity();
        $class = ClassUtils::getClass($entity);
        foreach ($this->listeners[$class] ?? [] as $property => $listeners) {
            foreach ($listeners as $listener) {
                $listener($eventArgs, $operationType);
            }
        }
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     */
    public function postPersist(LifecycleEventArgs $eventArgs): void
    {
        $this->trigger($eventArgs, PropertyChangeset::INSERT);
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     */
    public function postUpdate(LifecycleEventArgs $eventArgs): void
    {
        $this->trigger($eventArgs, PropertyChangeset::UPDATE);
    }

    /**
     * @inheritDoc
     */
    public function getSubscribedEvents()
    {
        return [
            'postPersist',
            'postUpdate',
        ];
    }
}
