# Session Package

![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/rancoud/session)
[![Packagist Version](https://img.shields.io/packagist/v/rancoud/session)](https://packagist.org/packages/rancoud/session)
[![Packagist Downloads](https://img.shields.io/packagist/dt/rancoud/session)](https://packagist.org/packages/rancoud/session)
[![Composer dependencies](https://img.shields.io/badge/dependencies-0-brightgreen)](https://github.com/rancoud/session/blob/master/composer.json)
[![Test workflow](https://img.shields.io/github/workflow/status/rancoud/session/test?label=test&logo=github)](https://github.com/rancoud/session/actions?workflow=test)
[![Codecov](https://img.shields.io/codecov/c/github/rancoud/session?logo=codecov)](https://codecov.io/gh/rancoud/session)
[![composer.lock](https://poser.pugx.org/rancoud/session/composerlock)](https://packagist.org/packages/rancoud/session)

Session.  

## Installation
```php
composer require rancoud/session
```

## Informations
By default session is in read only (option read_and_close = 1).  
You can specify it using `Session::setReadWrite()` or `Session::setReadOnly()`  

Session::start() is not needed, but: 
* Session will automatically start in read only when using `get, has, hasKeyAndValue, getAll`
* Session will automatically start in write mode when using `set, remove, getAndRemove, keepFlash, gc, regenerate`

## How to use it?
Set and get value from $_SESSION
```php
Session::set('key', 'value');
$value = Session::get('key');
```
Use custom options
```php
// first way
Session::setOption('name', 'custom_session_name');

// second way
Session::start(['cookie_lifetime' => 1440]);

Session::set('key', 'value');
$value = Session::get('key');
```
With encryption on default php session
```php
Session::useDefaultEncryptionDriver('keyForEncryption');
Session::set('key', 'value');
$value = Session::get('key');
```
With File driver
```php
Session::useFileDriver();
Session::set('key', 'value');
$value = Session::get('key');
```
With Database driver (you have to install rancoud/database)
```php
$conf = new \Rancoud\Database\Configurator([
    'engine'   => 'mysql',
    'host'     => '127.0.0.1',
    'user'     => 'root',
    'password' => '',
    'database' => 'test_database'
]);
$db = new \Rancoud\Database\Database($conf);

// for using a current connection
Session::useCurrentDatabaseDriver($db);

// for creating a new connection
//Session::useNewDatabaseDriver($conf);

Session::set('key', 'value');
$value = Session::get('key');
```
With Redis driver (you have to install predis/predis)
```php
$params = [
    'scheme' => 'tcp',
    'host'   => '127.0.0.1',
    'port'   => 6379,
];
$redis = new Predis\Client($params);

// for using a current connection
Session::useCurrentRedisDriver($redis);

// for creating a new connection
//Session::useNewRedisDriver($params);

Session::set('key', 'value');
$value = Session::get('key');
```
With your own driver implementing SessionHandlerInterface
```php
$driver = new MyCustomDriver();
Session::useCustomDriver($driver);
Session::set('key', 'value');
$value = Session::get('key');
```

## Session
### Static General Commands  
* start([options: array = []]): void  
* regenerate(): bool  
* destroy(): bool  
* commit(): void  
* rollback(): bool  
* unsaved(): bool  
* hasStarted(): bool  
* getId(): string  
* setId(id: string): string  
* gc(): void  
* setReadOnly(): void  
* setReadWrite(): void  

### Static Variables $_SESSION access
* set(key: string, value: mixed): void  
* get(key: string): mixed  
* getAll(): array  
* getAndRemove(key: string): mixed  
* has(key: string): bool  
* hasKeyAndValue(key: string, value: mixed): bool  
* remove(key: string): void  

### Static Variables flash access
Flash data are store in a separate variable.  
They will dissapear at the end of the script execution.  
You can use keepFlash for saving it in $_SESSION.  
When flash data is restore, it will be delete in $_SESSION.  

* setFlash(key: string, value: mixed): void    
* getFlash(key: string): mixed  
* getAllFlash(): array  
* hasFlash(key: string): bool  
* hasFlashKeyAndValue(key: string, value: mixed): bool  
* keepFlash([keys: array = []]): void  
* restoreFlashData(): void  

### Static Options  
* setOption(key: string, value): void  
* setOptions(options: array): void  
* getOption(key: string): mixed  

### Static Driver
* getDriver(): \SessionHandlerInterface  

#### Static PHP Session Default Driver
* useDefaultDriver(): void  
* useDefaultEncryptionDriver(key: string, [method: string|null = null]): void  

#### Static File Driver
* useFileDriver(): void  
* useFileEncryptionDriver(key: string, [method: string|null = null]): void  
* setPrefixForFile(prefix: string): void  

#### Static Database Driver
* useNewDatabaseDriver(configuration: \Rancoud\Database\Configurator|array): void  
* useCurrentDatabaseDriver(databaseInstance: \Rancoud\Database\Database): void  
* useNewDatabaseEncryptionDriver(configuration: \Rancoud\Database\Configurator|array, key: string, [method: string = null]): void  
* useCurrentDatabaseEncryptionDriver(databaseInstance: \Rancoud\Database\Database, key: string, [method: string = null]): void  
* setUserIdForDatabase(userId: int): void  

#### Static Redis Driver
* useNewRedisDriver(configuration: array|string): void  
* useCurrentRedisDriver(redisInstance: \Predis\Client): void  
* useNewRedisEncryptionDriver(configuration: array|string, key: string, [method: string = null]): void  
* useCurrentRedisEncryptionDriver(redisInstance: \Predis\Client, key: string, [method: string = null]): void  

#### Static Custom Driver
* useCustomDriver(customDriver: \SessionHandlerInterface): void  

## Driver Informations
### Default
Use SessionHandler
### File
Extends SessionHandler
### Database
You need to install
```php
composer require rancoud/database
```
```sql
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(128) NOT NULL,
  `id_user` int(10) unsigned DEFAULT NULL,
  `last_access` datetime NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

```
### Redis
You need to install
```php
composer require predis/predis
```

## How to Dev
`composer ci` for php-cs-fixer and phpunit and coverage  
`composer lint` for php-cs-fixer  
`composer test` for phpunit and coverage  