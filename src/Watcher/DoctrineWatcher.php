<?php

namespace BenTools\DoctrineWatcher\Watcher;

use BenTools\DoctrineWatcher\Changeset\PropertyChangeset;
use BenTools\DoctrineWatcher\Changeset\ChangesetFactory;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;

final class DoctrineWatcher implements EventSubscriber
{
    public const DEFAULT_OPTIONS = [
        'trigger_on_persist'   => true,
        'trigger_when_no_changes' => true,
        'type'    => PropertyChangeset::CHANGESET_DEFAULT
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
     * @param array                 $options
     * @param ChangesetFactory|null $changesetFactory
     */
    public function __construct(
        array $options = self::DEFAULT_OPTIONS,
        ChangesetFactory $changesetFactory = null
    ) {
        $this->defaulOptions = $options + self::DEFAULT_OPTIONS;
        $this->changesetFactory = $changesetFactory ?? new ChangesetFactory();
    }

    /**
     * @param string   $entityClass
     * @param string   $property
     * @param callable $callback
     * @param array    $options
     */
    public function watch(string $entityClass, string $property, callable $callback, array $options = []): void
    {
        $options = $options + $this->defaulOptions;
        $listener = function (LifecycleEventArgs $eventArgs) use ($entityClass, $property, $callback, $options) {
            $em = $eventArgs->getEntityManager();
            $unitOfWork = $em->getUnitOfWork();
            $entity = $eventArgs->getEntity();

            // Do not trigger on the wrong entity
            if (!$entity instanceof $entityClass) {
                return;
            }

            // Do not trigger if entity was not managed
            if (false === $options['trigger_on_persist'] && $this->changesetFactory->isNotManagedYet($entity, $unitOfWork)) {
                return;
            }

            // Do not trigger if field has no changes
            $className = ClassUtils::getClass($entity);
            $classMetadata = $em->getClassMetadata($className);
            $changedProperties = $this->changesetFactory->getChangedProperties($entity, $unitOfWork, $classMetadata);
            if (!in_array($property, $changedProperties) && false === $options['trigger_when_no_changes']) {
                return;
            }

            $changeset = $this->changesetFactory->getChangeset($entity, $property, $unitOfWork, $classMetadata, $options['type']);
            $callback(
                $changeset,
                $this->changesetFactory->isNotManagedYet($entity, $unitOfWork) ? PropertyChangeset::INSERT : PropertyChangeset::UPDATE,
                $entity,
                $property
            );
        };
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
        $this->watch($entityClass, $property, $callback, ['type' => PropertyChangeset::CHANGESET_ITERABLE] + $options);
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     */
    private function trigger(LifecycleEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getEntity();
        $class = ClassUtils::getClass($entity);
        foreach ($this->listeners[$class] ?? [] as $property => $listeners) {
            foreach ($listeners as $listener) {
                $listener($eventArgs);
            }
        }
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     */
    public function prePersist(LifecycleEventArgs $eventArgs): void
    {
        $this->trigger($eventArgs);
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     */
    public function preUpdate(LifecycleEventArgs $eventArgs): void
    {
        $this->trigger($eventArgs);
    }

    /**
     * @inheritDoc
     */
    public function getSubscribedEvents()
    {
        return [
            'prePersist',
            'preUpdate',
        ];
    }
}
