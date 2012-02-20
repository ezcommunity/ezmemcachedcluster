<?php
/**
 * File containing the eZMemcachedClusterLoggerPhp class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

/**
 * Description of ezmemcachedclusterloggerphp
 */
class eZMemcachedClusterLoggerPhp implements eZMemcachedClusterLogger
{
    /**
     * Logs $errMsg in PHP error log
     *
     * @param string $errMsg Error message to be logged
     * @param string $context Context where the error occurred
     * @return void
     */
    public function log( $errMsg, $context = null )
    {
        error_log( $errMsg );
    }
}
