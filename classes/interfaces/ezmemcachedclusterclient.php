<?php
/**
 * File containing the eZMemcachedClusterClient interface
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

/**
 * Interface for Memcached clients
 */
interface eZMemcachedClusterClient
{
    /**
     * Initializes the client with $options
     *
     * @param eZMemcachedClusterOptions $options
     * @return void
     */
    public function initialize( eZMemcachedClusterOptions $options );
}
