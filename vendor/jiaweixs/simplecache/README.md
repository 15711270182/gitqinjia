### Installation
```
//install by composer
composer require jiaweixs/simplecache
  
//or clone from github
git clone git@github.com:kulokai/simplecache.git
```

### Usage
```php
//init the tool
SimpleCache::init('.../path/to/cache');
  
//init the tool and set default expire time.
SimpleCache::init('.../path/to/cache',600);
```

```php
//set cache
$isSuccess = SimpleCache::set('key','hello world');
  
//set cache and set expire time for the 'key'.
$isSuccess = SimpleCache::set('key','hello world',600);
```

```php
//get cache
$value = SimpleCache::get('key');
  
//get cache and set default value for the 'key'.
$value = SimpleCache::get('key','default value');
```