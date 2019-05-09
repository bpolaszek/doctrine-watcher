<?php

namespace BenTools\DoctrineWatcher\Tests;

use BenTools\DoctrineWatcher\Changeset\PropertyChangeset;
use BenTools\DoctrineWatcher\Tests\Entity\Employee;
use BenTools\DoctrineWatcher\Tests\Entity\User;
use BenTools\DoctrineWatcher\Watcher\DoctrineWatcher;
use Noback\PHPUnitTestServiceContainer\PHPUnit\TestCaseWithEntityManager;
use PHPUnit\Framework\TestCase;

final class DoctrineWatcherTest extends TestCase
{
    use TestCaseWithEntityManager;

    protected function getEntityDirectories()
    {
        return [__DIR__ . '/Entity'];
    }

    /**
     * @test
     */
    public function it_does_nothing_on_persist()
    {
        $watcher = new DoctrineWatcher();
        $name = null;
        $operationType = null;
        $entity = null;
        $property = null;
        $watcher->watch(User::class, 'name', function (PropertyChangeset $changeset, $_operationType, $_entity, $_property) use (&$name, &$operationType, &$entity, &$property) {
            $name = $changeset->getNewValue();
            $operationType = $_operationType;
            $entity = $_entity;
            $property = $_property;
        });
        $this->getEventManager()->addEventSubscriber($watcher);
        $user = new User();
        $user->setName('foo');
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        $this->assertNull($name);
        $this->assertNull($operationType);
        $this->assertNull($entity);
        $this->assertNull($property);
    }

    /**
     * @test
     */
    public function it_does_something_on_persist()
    {
        $watcher = new DoctrineWatcher(['trigger_on_persist' => true]);
        $name = null;
        $operationType = null;
        $entity = null;
        $property = null;
        $watcher->watch(User::class, 'name', function (PropertyChangeset $changeset, $_operationType, $_entity, $_property) use (&$name, &$operationType, &$entity, &$property) {
            $name = $changeset->getNewValue();
            $operationType = $_operationType;
            $entity = $_entity;
            $property = $_property;
        });
        $this->getEventManager()->addEventSubscriber($watcher);
        $user = new User();
        $user->setName('foo');
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        $this->assertEquals('foo', $name);
        $this->assertEquals(PropertyChangeset::INSERT, $operationType);
        $this->assertInstanceOf(User::class, $entity);
        $this->assertEquals('name', $property);
    }

    /**
     * @test
     */
    public function it_maps_on_the_correct_property()
    {
        $watcher = new DoctrineWatcher(['trigger_on_persist' => true]);
        $name = null;
        $watcher->watch(User::class, 'title', function (PropertyChangeset $changeset) use (&$name) {
            $name = $changeset->getNewValue();
        });
        $this->getEventManager()->addEventSubscriber($watcher);
        $user = new User();
        $user->setName('foo');
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        $this->assertNull($name);
    }

    /**
     * @test
     */
    public function it_can_handle_several_listeners()
    {
        $watcher = new DoctrineWatcher(['trigger_on_persist' => true]);
        $name = null;
        $watcher->watch(User::class, 'name', function (PropertyChangeset $changeset) use (&$name) {
            $name = $changeset->getNewValue();
        });
        $watcher->watch(User::class, 'name', function (PropertyChangeset $changeset) use (&$name) {
            $name .= 'bar';
        });
        $this->getEventManager()->addEventSubscriber($watcher);
        $user = new User();
        $user->setName('foo');
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        $this->assertEquals('foobar', $name);
    }

    /**
     * @test
     */
    public function it_does_something_on_update()
    {
        $watcher = new DoctrineWatcher(['trigger_on_persist' => true]);
        $name = null;
        $operationType = null;
        $entity = null;
        $property = null;
        $watcher->watch(User::class, 'name', function (PropertyChangeset $changeset, $_operationType, $_entity, $_property) use (&$name, &$operationType, &$entity, &$property) {
            $name = $changeset->getNewValue();
            $operationType = $_operationType;
            $entity = $_entity;
            $property = $_property;
        });
        $this->getEventManager()->addEventSubscriber($watcher);
        $user = new User();
        $user->setName('foo');
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        $this->assertEquals('foo', $name);
        $this->assertEquals(PropertyChangeset::INSERT, $operationType);
        $this->assertInstanceOf(User::class, $entity);
        $this->assertEquals('name', $property);


        $user->setName('bar');
        $this->getEntityManager()->flush();
        $this->assertEquals('bar', $name);
        $this->assertEquals(PropertyChangeset::UPDATE, $operationType);
        $this->assertInstanceOf(User::class, $entity);
        $this->assertEquals('name', $property);
    }

    /**
     * @test
     */
    public function it_does_nothing_when_nothing_changes()
    {
        $watcher = new DoctrineWatcher();
        $called = false;
        $watcher->watch(User::class, 'name', function (PropertyChangeset $changeset) use (&$called) {
            $called = true;
        }, ['trigger_on_persist' => true, 'trigger_when_no_changes' => false]);
        $this->getEventManager()->addEventSubscriber($watcher);

        $user = new User();
        $user->setName('foo');
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        $this->assertTrue($called);

        $called = false;
        $user->setTitle('bar');
        $this->getEntityManager()->flush();
        $this->assertFalse($called);
    }

    /**
     * @test
     */
    public function it_does_something_when_nothing_changes()
    {
        $watcher = new DoctrineWatcher();
        $called = false;
        $watcher->watch(User::class, 'name', function (PropertyChangeset $changeset) use (&$called) {
            $called = true;
        }, ['trigger_on_persist' => true, 'trigger_when_no_changes' => true]);
        $this->getEventManager()->addEventSubscriber($watcher);

        $user = new User();
        $user->setName('foo');
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        $this->assertTrue($called);

        $called = false;
        $user->setTitle('bar');
        $this->getEntityManager()->flush();
        $this->assertTrue($called);
    }

    /**
     * @test
     */
    public function it_does_nothing_on_persist_but_it_does_something_on_update()
    {
        $watcher = new DoctrineWatcher();
        $called = false;
        $watcher->watch(User::class, 'name', function (PropertyChangeset $changeset) use (&$called) {
            $called = true;
        }, ['trigger_on_persist' => false]);
        $this->getEventManager()->addEventSubscriber($watcher);

        $user = new User();
        $user->setName('foo');
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        $this->assertFalse($called);

        $called = false;
        $user->setName('bar');
        $this->getEntityManager()->flush();
        $this->assertTrue($called);
    }

    /**
     * @test
     */
    public function it_does_nothing_on_another_entity_class()
    {
        $watcher = new DoctrineWatcher();
        $called = false;
        $watcher->watch(Employee::class, 'name', function (PropertyChangeset $changeset) use (&$called) {
            $called = true;
        });
        $this->getEventManager()->addEventSubscriber($watcher);

        $user = new User();
        $user->setName('foo');
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        $this->assertFalse($called);

        $called = false;
        $user->setName('bar');
        $this->getEntityManager()->flush();
        $this->assertFalse($called);
    }

    /**
     * @test
     */
    public function i_can_enable_iterable_changeset()
    {
        $watcher = new DoctrineWatcher(['trigger_on_persist' => true]);
        $changeset = null;
        $watcher->watch(User::class, 'roles', function (PropertyChangeset $_changeset) use (&$changeset) {
            $changeset = $_changeset;
        });
        $this->getEventManager()->addEventSubscriber($watcher);


        $user = new User();
        $user->setName('John');
        $user->setRoles(['foo', 'bar']);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        /** @var PropertyChangeset $changeset */
        $this->assertInstanceOf(PropertyChangeset::class, $changeset);
        $this->assertTrue($changeset->hasAdditions());
        $this->assertFalse($changeset->hasRemovals());
        $this->assertEquals(['foo', 'bar'], $changeset->getAdditions());

        $user->setRoles(['foo', 'baz', 'bar']);
        $this->getEntityManager()->flush();
        $this->assertTrue($changeset->hasAdditions());
        $this->assertFalse($changeset->hasRemovals());
        $this->assertEquals(['baz'], $changeset->getAdditions());

        $user->setRoles(['foo', 'baz']);
        $this->getEntityManager()->flush();
        $this->assertFalse($changeset->hasAdditions());
        $this->assertTrue($changeset->hasRemovals());
        $this->assertEquals(['bar'], $changeset->getRemovals());

        $user->setRoles(['foo', 'boom']);
        $this->getEntityManager()->flush();
        $this->assertTrue($changeset->hasAdditions());
        $this->assertTrue($changeset->hasRemovals());
        $this->assertEquals(['baz'], $changeset->getRemovals());
        $this->assertEquals(['boom'], $changeset->getAdditions());
    }
}