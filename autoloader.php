<?php
/**
 * Default memcached configuration file for cluster index
 *
 * DO NOT MODIFY THIS FILE.
 * Either copy the contents to your own config.php, or create a copy that you include from config.php.
 */
class eZMemcachedAutoloader
{
    /**
     * Autoload array
     */
    private $autoloadArray = array(
      'eZMemcachedClusterCacheClear'                   => 'extension/ezmemcachedcluster/classes/ezmemcachedclustercacheclear.php',
      'eZMemcachedClusterClient'                       => 'extension/ezmemcachedcluster/classes/interfaces/ezmemcachedclusterclient.php',
      'eZMemcachedClusterClientMemcached'              => 'extension/ezmemcachedcluster/classes/ezmemcachedclusterclientmemcached.php',
      'eZMemcachedClusterConfigurationHandler'         => 'extension/ezmemcachedcluster/classes/interfaces/ezmemcachedclusterconfigurationhandler.php',
      'eZMemcachedClusterConfigurationIni'             => 'extension/ezmemcachedcluster/classes/ezmemcachedclusterconfigurationini.php',
      'eZMemcachedClusterConfigurationManual'          => 'extension/ezmemcachedcluster/classes/ezmemcachedclusterconfigurationmanual.php',
      'eZMemcachedClusterEventListener'                => 'extension/ezmemcachedcluster/classes/ezmemcachedclustereventlistener.php',
      'eZMemcachedClusterOptions'                      => 'extension/ezmemcachedcluster/classes/ezmemcachedoptions.php',
      'eZMemcachedClusterGatewayMySQLi'                => 'extension/ezmemcachedcluster/classes/ezmemcachedclustergatewaymysqli.php',
      'eZClusterEventListener'                         => 'kernel/private/classes/clusterfilehandlers/interfaces/ezclustereventlistener.php',
      'ezpDfsMySQLiClusterGateway'                     => 'kernel/clustering/dfsmysqli.php',
      'ezpClusterGateway'                              => 'kernel/clustering/gateway.php',
      'eZClusterEventLogger'                           => 'kernel/private/classes/clusterfilehandlers/interfaces/ezclustereventlogger.php',
      'eZClusterEventLoggerPhp'                        => 'kernel/private/classes/clusterfilehandlers/ezclustereventloggerphp.php',
    );

    public function __construct()
    {
        spl_autoload_register( array( $this, 'load' ) );
    }

    private function load( $className )
    {
        if ( isset( $this->autoloadArray[$className] ) )
            include $this->autoloadArray[$className];
    }
}
?>