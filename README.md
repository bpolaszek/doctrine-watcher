[![Latest Stable Version](https://poser.pugx.org/bentools/doctrine-watcher/v/stable)](https://packagist.org/packages/bentools/doctrine-watcher)
[![License](https://poser.pugx.org/bentools/doctrine-watcher/license)](https://packagist.org/packages/bentools/doctrine-watcher)
[![Build Status](https://img.shields.io/travis/bpolaszek/doctrine-watcher/master.svg?style=flat-square)](https://travis-ci.org/bpolaszek/doctrine-watcher)
[![Quality Score](https://img.shields.io/scrutinizer/g/bpolaszek/doctrine-watcher.svg?style=flat-square)](https://scrutinizer-ci.com/g/bpolaszek/doctrine-watcher)
[![Total Downloads](https://poser.pugx.org/bentools/doctrine-watcher/downloads)](https://packagist.org/packages/bentools/doctrine-watcher)

# Doctrine Watcher

This little library will help you to monitor changes on Doctrine insertions and/or updates, for specific classes, for specific properties.

## Usage

```php
use App\Entity\User;
use BenTools\DoctrineWatcher\Changeset\PropertyChangeset;
use BenTools\DoctrineWatcher\Watcher\DoctrineWatcher;

/**
 * Instanciate watcher
 */
$watcher = new DoctrineWatcher();

/**
 * Register it as an event subscriber
 * @var \Doctrine\Common\EventManager $eventManager
 */
$eventManager->addEventSubscriber($watcher);

/**
 * Watch for changes on the $email property for the User class
 */
$watcher->watch(User::class, 'email', function (
    PropertyChangeset $changeset,
    string $operationType,
    User $user
) {

    if (!$changeset->hasChanges()) {
        return;
    }

    vprintf('Changed email from %s to %s for user %s' . PHP_EOL, [
        $changeset->getOldValue(),
        $changeset->getNewValue(),
        $user->getName(),
    ]);
});

/**
 * Watch for changes on the $roles property for the User class
 */
$watcher->watch(User::class, 'roles', function (
    PropertyChangeset $changeset, 
    string $operationType, 
    User $user
) {

    if ($changeset::INSERT === $operationType) {
        return;
    }

    if ($changeset->hasAdditions()) {
        vprintf('Roles %s were granted for user %s' . PHP_EOL, [
            implode(', ', $changeset->getAdditions()),
            $user->getName(),
        ]);
    }

    if ($changeset->hasRemovals()) {
        vprintf('Roles %s were revoked for user %s' . PHP_EOL, [
            implode(', ', $changeset->getRemovals()),
            $user->getName(),
        ]);
    }
});
```

## Installation

PHP7.1+ is required.

```bash
composer require bentools/doctrine-watcher:0.2.*
```

## Tests

> ./vendor/bin/phpunit

## F.A.Q.

### Can I also trigger callable on insertions ?

```php
$watcher = new DoctrineWatcher(['trigger_on_persist' => true]); // Will be default config for all watchers
```
or 
```php
$watcher->watch(Entity::class, 'property', $callable, ['trigger_on_persist' => true]); // Will apply on this watcher only
```

### How do I trigger something even when there are no changes?

```php
$watcher = new DoctrineWatcher(['trigger_when_no_changes' => true]); // Will be default config
```
or 
```php
$watcher->watch(Entity::class, 'property', $callable, ['trigger_when_no_changes' => true]); // Will apply on this watcher only
```

### When are the callback triggered?

On `postPersist` and `postUpdate` events.

## License

MIT

## See also
[bentools/doctrine-watcher-bundle](https://github.com/bpolaszek/doctrine-watcher-bundle) - A Symfony Bundle for this library
