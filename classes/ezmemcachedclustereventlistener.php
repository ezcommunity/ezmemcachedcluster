<?php
/**
 * File containing the eZMemcachedClusterEventListener class
 *
 * @copyright Copyright (C) 2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

/**
 * Cluster events listener Memcached implementation
 */
class eZMemcachedClusterEventListener implements eZClusterEventListener
{
    /**
     * Memcached client
     *
     * @var eZMemcachedClusterClient
     */
    private $client;

    /**
     * Logger to use
     *
     * @var eZClusterEventLogger
     */
    private $logger;

    /**
     * Configuration handler
     *
     * @var eZMemcachedClusterConfigurationHandler
     */
    private $configurationHandler;

    /**
     * Constructor
     *
     * @param eZClusterEventLogger $logger Object used for logging errors.
     * @param eZMemcachedClusterConfigurationHandler $configurationHandler
     *        Configuration handler. If not provided, {@link eZMemcachedClusterConfigurationIni} will be used.
     */
    public function __construct( eZClusterEventLogger $logger, eZMemcachedClusterConfigurationHandler $configurationHandler = null )
    {
        $this->logger = $logger;
        if ( isset( $configurationHandler ) )
            $this->configurationHandler = $configurationHandler;
        else
            $this->configurationHandler = new eZMemcachedClusterConfigurationIni;

        $this->client = $this->configurationHandler->getClient();
    }

    /**
     * Initializes the listener.
     * Defines options for the connection with the Memcached server and passes them to the client.
     * It's up to the client to support those options, depending on which library you use.
     *
     * @return void
     */
    public function initialize()
    {
        try
        {
            $this->client->initialize( $this->configurationHandler->getOptions() );
        }
        catch( eZMemcachedException $e )
        {
            $this->logger->logError( $e->getMessage(), __METHOD__ );
            throw $e;
        }
    }

    /**
     * Returns metadata array for $filepath, as supported by cluster.
     * This array must have following keys :
     *  - name
     *  - name_trunk (name trunk for the entry, if none, equals to "name")
     *  - name_hash (md5 hash of "name")
     *  - scope
     *  - datatype
     *  - mtime (integer)
     *  - expired (integer, 0/1)
     *
     * If no metadata is available, this method must return false
     *
     * @param string $filepath
     * @return array|false
     */
    public function loadMetadata( $filepath )
    {
        try
        {
            return $this->client->get( md5( $filepath ) );
        }
        catch ( eZMemcachedException $e )
        {
            $this->logger->logError( $e->getMessage(), __METHOD__ );
            return false;
        }
    }

    /**
     * Updates a file's metadata
     *
     * @param array $metadata Same array as {@link eZClusterEventListener::loadMetadata()}
     * @return void
     */
    public function storeMetadata( array $metadata )
    {
        $filepathHash = md5( $metadata['name'] );
        try
        {
            $this->client->set( $filepathHash, $metadata );
            // if metadata contain a name trunk, we add the file hash to this nametrunk map in memcache
            if ( $metadata['name_trunk'] && $metadata['name_trunk'] !== $metadata['name'] )
            {
                $this->client->addToMap( $metadata['name_trunk'], $filepathHash );
            }
        }
        catch( eZMemcachedException $e )
        {
            $this->logger->logError( $e->getMessage(), __METHOD__ );
        }

    }

    /**
     * Checks if a file exists on the cluster.
     * If file does exist, this method must return an associative array with following keys:
     *  - name
     *  - mtime
     *
     * Returns false if file doesn't exist
     *
     * @param string $filepath
     * @return array|false
     */
    public function fileExists( $filepath )
    {
        try
        {
            $metadata = $this->client->get( md5( $filepath ) );
            if ( $metadata === false )
                return false;

            return array( 'name' => $metadata['name'], 'mtime' => $metadata['mtime'] );
        }
        catch( eZMemcachedException $e )
        {
            $this->logger->logError( $e->getMessage(), __METHOD__ );
            return false;
        }

    }

    /**
     * Deletes $filepath
     *
     * @param string $filepath
     * @return void
     */
    public function deleteFile( $filepath )
    {
        try
        {
            $this->client->delete( md5( $filepath ) );
        }
        catch( eZMemcachedException $e )
        {
            $this->logger->logError( $e->getMessage(), __METHOD__ );
        }
    }

    /**
     * Notifies of a deleteByLike operation
     *
     * @param string $like
     * @return void
     */
    public function deleteByLike( $like )
    {
        // We don't have an index in memcache that allows for such queries
        // The only way is therefore to fully flush memcache
        try
        {
            $this->client->flush();
        }
        catch( Exception $e )
        {
            $this->logger->logError( $e->getMessage(), __METHOD__ );
        }
    }

    /**
     * Notifies of a deleteByWildcard operation
     *
     * @param string $wildcard
     * @return void
     */
    public function deleteByWildcard( $wildcard )
    {
        // We don't have an index in memcache that allows for such queries
        // The only way is therefore to fully flush memcache
        try
        {
            $this->client->flush();
        }
        catch( eZMemcachedException $e )
        {
            $this->logger->logError( $e->getMessage(), __METHOD__ );
        }
    }

    /**
     * Notifies of a deleteByDirList operation
     *
     * @param array $dirList
     * @param string $commonPath
     * @param string $commonSuffix
     * @return void
     */
    public function deleteByDirList( array $dirList, $commonPath, $commonSuffix )
    {
        // We don't have an index in memcache that allows for such queries
        // The only way is therefore to fully flush memcache
        try
        {
            $this->client->flush();
        }
        catch( eZMemcachedException $e )
        {
            $this->logger->logError( $e->getMessage(), __METHOD__ );
        }
    }

    /**
     * Deletes all files matching the provided $nametrunk string
     *
     * @param string $nametrunk
     * @return void
     */
    public function deleteByNametrunk( $nametrunk )
    {
        $nametrunkMap = $this->client->get( $nametrunk );
        if ( $nametrunkMap === false || !is_array( $nametrunkMap ) )
            return;

        try
        {
            foreach ( array_keys( $nametrunkMap ) as $filepathHash )
            {
                $this->client->delete( md5( $filepathHash ) );
            }

            $this->client->delete( $nametrunk );
        }
        catch( eZMemcachedException $e )
        {
            $this->logger->logError( $e->getMessage(), __METHOD__ );
        }
    }

    /**
     * Flush method.
     * Useful for cache purging.
     */
    public function flush()
    {
        try
        {
            $this->client->flush();
        }
        catch( eZMemcachedException $e )
        {
            $this->logger->logError( $e->getMessage(), __METHOD__ );
        }
    }
}
