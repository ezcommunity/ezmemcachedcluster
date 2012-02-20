<?php
/**
 * File containing the eZMemcachedClusterCacheClear class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

/**
 * Clear cache handler.
 * Purges keys in Memcached
 */
class eZMemcachedClusterCacheClear
{
    public static function clearCache()
    {
        $fileINI = eZINI::instance( 'file.ini' );
        if ( $fileINI->variable( 'ClusterEventsSettings', 'ClusterEvents' ) === 'enabled' )
        {
            $listener = eZExtension::getHandlerClass(
                new ezpExtensionOptions(
                    array(
                        'iniFile'       => 'file.ini',
                        'iniSection'    => 'ClusterEventsSettings',
                        'iniVariable'   => 'Listener',
                        'handlerParams' => array( new eZClusterEventLoggerEzdebug() )
                    )
                )
            );

            if ( $listener instanceof eZMemcachedClusterEventListener )
            {
                $listener->initialize();
                $listener->flush();
            }
        }
    }

    public static function purgeCache()
    {
        self::clearCache();
    }
}
