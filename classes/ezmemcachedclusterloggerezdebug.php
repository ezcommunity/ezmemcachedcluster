<?php
/**
 * File containing the eZMemcachedClusterLoggerEzdebug class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

/**
 * Logger using eZDebugSetting.
 * Condition is 'ezmemcachedcluster-debug'
 */
class eZMemcachedClusterLoggerEzdebug implements eZMemcachedClusterLogger
{
    /**
     * Logs $errMsg.
     *
     * @param string $errMsg Error message to be logged
     * @param string $context Context where the error occurred
     * @return void
     */
    public function log( $errMsg, $context = null )
    {
        eZDebugSetting::writeError( 'ezmemcachedcluster-debug', $errMsg, $context );
    }
}
