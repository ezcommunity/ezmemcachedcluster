<?php
/**
 * File containing the ezmemcachedclusterconfigurationini class
 *
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

/**
 * Description of ezmemcachedclusterconfigurationini
 */
class eZMemcachedClusterConfigurationIni implements eZMemcachedClusterConfigurationHandler
{
    /**
     * @var eZINI
     */
    private $memcacheINI;

    public function __construct()
    {
        $this->memcacheINI = eZINI::instance( 'memcachedcluster.ini' );
    }

    /**
     * Returns the client object to use to handle queries to Memcached backend.
     *
     * @return eZMemcachedClusterClient
     */
    public function getClient()
    {
        $client = eZExtension::getHandlerClass(
            new ezpExtensionOptions(
                array(
                    'iniFile'     => 'memcachedcluster.ini',
                    'iniSection'  => 'ClientSettings',
                    'iniVariable' => 'BackendClient'
                )
            )
        );

        return $client;
    }

    /**
     * Returns the options object to pass to client for configuration.
     *
     * @return eZMemcachedClusterOptions
     */
    public function getOptions()
    {
        $serverOptions = $this->memcacheINI->group( 'ServerSettings' );
        $options = new eZMemcachedClusterOptions;
        $options->servers = $serverOptions['Servers'];
        $options->connectTimeout = (int)$serverOptions['ConnectionTimeout'];
        $options->usePersistentConnection = $serverOptions['UsePersistentConnection'] === 'enabled';
        if ( $options->usePersistentConnection )
            $options->connectionIdentifier = $serverOptions['PersistentConnectionIdentifier'];
        $options->useCompression = $serverOptions['UseCompression'] === 'enabled';
        $options->prefixKey = $serverOptions['PrefixKey'];
        $options->useBuffer = $serverOptions['UseBuffer'] === 'enabled';
        $options->useBinaryProtocol = $serverOptions['UseBinaryProtocol'] === 'enabled';
        $options->defaultCacheTTL = (int)$this->memcacheINI->variable( 'ClientSettings' , 'CacheTTL' );

        return $options;
    }
}
