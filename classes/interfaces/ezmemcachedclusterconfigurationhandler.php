<?php
/**
 * File containing the eZMemcachedClusterConfigurationHandler class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

/**
 * Interface for configuration handlers
 */
interface eZMemcachedClusterConfigurationHandler
{
    /**
     * Returns the client object to use to handle queries to Memcached backend.
     *
     * @return eZMemcachedClusterClient
     */
    public function getClient();

    /**
     * Returns the options object to pass to client for configuration.
     *
     * @return eZMemcachedClusterOptions
     */
    public function getOptions();
}
