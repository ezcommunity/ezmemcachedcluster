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
     * eZINI instance for memcachedcluster.ini
     *
     * @var eZINI
     */
    private $memcacheINI;

    public function __construct()
    {
        $this->memcacheINI = eZINI::instance( 'memcachedcluster.ini' );
        $this->client = eZExtension::getHandlerClass(
            new ezpExtensionOptions(
                array(
                    'iniFile'     => 'memcachedcluster.ini',
                    'iniSection'  => 'ClientSettings',
                    'iniVariable' => 'BackendClient'
                )
            )
        );
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
        $serverOptions = $this->memcacheINI->group( 'ServerSettings' );
        $options = new eZMemcachedClusterOptions;
        $options->servers = $serverOptions['Servers'];
        $options->connectTimeout = (int)$serverOptions['ConnectionTimeout'];
        $options->usePersistentConnection = $serverOptions['UsePersistentConnection'] === 'enabled';
        if ( $options->usePersistentConnection )
            $options->connectionIdentifier = $serverOptions['PersistentConnectionIdentifier'];
        $options->useCompression = $serverOptions['UserCompression'] === 'enabled';
        $options->prefixKey = $serverOptions['PrefixKey'];
        $options->useBuffer = $serverOptions['UseBuffer'] === 'enabled';
        $options->useBinaryProtocol = $serverOptions['UseBinaryProtocol'] === 'enabled';

        $this->client->initialize( $options );
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
        return $this->client->get( md5( $filepath ) );
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
        $this->client->set( $filepathHash, $metadata );

        // if metadata contain a name trunk, we add the file hash to this nametrunk map in memcache
        if ( $metadata['name_trunk'] && $metadata['name_trunk'] !== $metadata['name'] )
        {
            $this->client->addToMap( $metadata['name_trunk'], $filepathHash );
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
        $metadata = $this->client->get( $filepath );

        if ( $metadata === false )
            return false;

        return array( 'name' => $metadata['name'], 'mtime' => $metadata['mtime'] );
    }

    /**
     * Deletes $filepath
     *
     * @param string $filepath
     * @return void
     */
    public function deleteFile( $filepath )
    {
        $this->client->delete( md5( $filepath ) );
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
        $this->client->flush();
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
        $this->client->flush();
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
        $this->client->flush();
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

        foreach( array_keys( $nametrunkMap ) as $filepathHash )
            $this->client->delete( md5( $filepathHash ) );

        $this->client->delete( $nametrunk );
    }
}
