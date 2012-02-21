<?php
/**
 * File containing the ezmemcachedclusterconfigurationini class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

/**
 * Description of ezmemcachedclusterconfigurationini
 */
class eZMemcachedClusterConfigurationManual implements eZMemcachedClusterConfigurationHandler
{
    /**
     * Options object
     * @var eZMemcachedClusterOptions
     */
    private $options;

    /**
     * Client object
     * @var eZMemcachedClusterClient
     */
    private $client;

    /**
     * Instanciates a configuration object with $options and $client
     * @param eZMemcachedClusterOptions $options
     * @param eZMemcachedClusterClient $client
     */
    public function __construct( eZMemcachedClusterOptions $options, eZMemcachedClusterClient $client )
    {
        $this->options = $options;
        $this->client = $client;
    }

    /**
     * Returns the client object to use to handle queries to Memcached backend.
     *
     * @return eZMemcachedClusterClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Returns the options object to pass to client for configuration.
     *
     * @return eZMemcachedClusterOptions
     */
    public function getOptions()
    {
        return $this->options;
    }
}
