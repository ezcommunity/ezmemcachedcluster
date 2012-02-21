<?php
/**
 * File containing the eZMemcachedClusterClientMemcached class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

/**
 * Memcached powered cluster gateway for MySQLi
 */
class eZMemcachedClusterGatewayMySQLi extends ezpDfsMySQLiClusterGateway
{
    /**
     * Configuration object
     * @var eZMemcachedClusterConfiguration
     */
    private static $configuration;

    /**
     * Memcached listener object
     * @var eZClusterEventListener
     */
    private $eventListener;

    /**
     * Connects to memcache and to the database
     * @throws UnexpectedValueException if $configuration hasn't been set
     */
    public function connect()
    {
        if ( !self::$configuration instanceof eZMemcachedClusterConfigurationHandler )
            throw new UnexpectedValueException( "No valid configuration object. Set one using eZMemcachedClusterGatewayMySQLi::setConfiguration" );

        $this->eventListener = new eZMemcachedClusterEventListener(
            new eZClusterEventLoggerPhp, self::$configuration
        );
        $this->eventListener->initialize();
        parent::connect();
    }

    /**
     * Returns the metadata for $filepath from memcached if they exist. Falls back to the database if they don't.
     * @param string $filepath
     * @return array
     */
    public function fetchFileMetadata( $filepath )
    {
        $memcachedMetadata = $this->eventListener->loadMetadata( $filepath );

        if ( $memcachedMetadata !== false )
            return $memcachedMetadata;

        $clusterMetadata = parent::fetchFileMetadata( $filepath );

        if ( $clusterMetadata !== false )
            $this->eventListener->storeMetadata( $clusterMetadata );

        return $clusterMetadata;
    }

    /**
     * Sets the memcached listener configuration object
     * @param eZMemcachedClusterConfiguration $configuration
     */
    public static function setConfiguration( eZMemcachedClusterConfigurationHandler $configuration )
    {
        self::$configuration = $configuration;
    }
}

ezpClusterGateway::setGatewayClass( 'eZMemcachedClusterGatewayMySQLi' );
?>