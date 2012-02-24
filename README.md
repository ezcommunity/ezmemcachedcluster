# eZ Memcached cluster

This extension adds cluster events support with [Memcached](http://memcached.org) backend server.


## Requirements

- A Memcached server
- [Memcached PECL extension](http://php.net/memcached) for PHP
  ([Memcache PECL extension](http://php.net/memcache) is also an option)
- eZ Publish **Etna** (4.7), with DFS cluster enabled using MySQLi.


## Install

- Activate the extension
- Regenerate autoloads
- In `settings/override/file.ini.append.php`, add:

```ini
[ClusterEventsSettings]
ClusterEvents=enabled
Listener=eZMemcachedClusterEventListener
```

### Using Memcached PECL extension
[Memcached PECL extension](http://php.net/memcached) is currently the recommended way for communication with Memcached server.

- Make an override of `memcachedcluster.ini` in `settings/override` and customize default values (like adding some Memcached servers).
  Placing it in the global override directory is mandatory to workaround cluster limitations about very early loading.

*Example:*

```ini
[ServerSettings]
Servers[]=memcachedhost1:11211;34
Servers[]=memcachedhost2:11211;66

ConnectionTimeout=2000
```
- Copy content of eZMemcachedCluster's `config.php.RECOMMENDED` to `config.cluster.php` at eZ Publish root.
  Here you can configure it the way you want, adding servers, modifying options via `eZMemcachedClusterOptions` object.<br />
  Note that **every options MUST reflect configuration placed in `memcachedcluster.ini`** (see comments in default INI file).

*Example following previous INI configuration example:*

```php
<?php
// config.cluster.php
require 'extension/ezmemcachedcluster/autoloader.php';

// instanciating the autoloader is enough, no need to assign it to a variable or call anything
new eZMemcachedAutoloader;

// Let's add 2 servers, with some weight.
// See extension/ezmemcachedcluster/settings/memcachedcluster.ini for more information.
$options = new eZMemcachedClusterOptions;
$options->servers[] = 'memcachedhost1:11211;34';
$options->servers[] = 'memcachedhost2:11211;66';
// Now let's modify the connect timeout
$options->connectTimeout = 2000;
// For other options, see extension/ezmemcachedcluster/classes/ezmemcachedoptions.php

// The following should not be modified
$memcachedConfiguration = new eZMemcachedClusterConfigurationManual(
    $options, new eZMemcachedClusterClientMemcached()
);
eZMemcachedClusterGatewayMySQLi::setConfiguration( $memcachedConfiguration );
ezpClusterGateway::setGatewayClass( 'eZMemcachedClusterGatewayMySQLi' );
```

### Using Memcache PECL extension
If you prefer to use [Memcache](http://php.net/memcache) (though not recommended)

In addition to previous settings explained for Memcached client, you will need to add the following in `settings/override/memcachedcluster.ini.append.php`:

```ini
[ClientSettings]
BackendClient=eZMemcachedClusterClientMemcache
```

And in `config.cluster.php`, replace:

```php
$memcachedConfiguration = new eZMemcachedClusterConfigurationManual(
    $options, new eZMemcachedClusterClientMemcached()
);
```

By:

```php
<?php
$memcachedConfiguration = new eZMemcachedClusterConfigurationManual(
    $options, new eZMemcachedClusterClientMemcache()
);
```

### Memcached server backend
- Start your Memcached server(s) with your preferred options.

If everything is OK, cluster queries count appearing in the debug output should be drastically reduced once cache has been generated
(no query most of the time).


## Debugging

### Memcached keys watch
Several tools can be used in order to watch/adminitrate Memcached servers.

You might consider [PHPMemcachedAdmin](http://code.google.com/p/phpmemcacheadmin/) for debugging purpose.
It will help you check keys stored in Memcached.

### Logging
If something is going wrong for any reason, you might want to activate error logging to see what happens.
To do that, you need to enable clustering conditional debug:

```ini
# In an override of debug.ini
[DebugSettings]
ConditionDebug=enabled

[GeneralCondition]
kernel-clustering=enabled
```

Additionnaly, any errors occuring via `index_cluster.php` (like while serving binary files) are logged in the default PHP's `error_log`.

### Flushing Memcached
If for some reason you need to flush Memcached keys from eZ Publish, you can use `bin/php/ezcache.php` script for that:

```bash
php bin/php/ezcache.php --clear-id=ezmemcachedcluster
```
