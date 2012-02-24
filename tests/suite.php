<?php
/**
 * File containing the eZMemcachedClusterTestSuite class
 *
 * @copyright Copyright (C) 2012 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPLv2
 * @package tests
 */

class eZMemcachedClusterTestSuite extends ezpDatabaseTestSuite
{
    public function __construct()
    {
        parent::__construct();
        $this->setName( "eZMemcachedCluster Test Suite" );

        $this->addTestSuite( 'eZMemcachedClusterClientMemcachedTest' );
        $this->addTestSuite( 'eZMemcachedClusterClientMemcacheTest' );
        $this->addTestSuite( 'eZMemcachedClusterEventListenerTest' );
    }

    public static function suite()
    {
        return new self();
    }

    public function setUp()
    {
        parent::setUp();

        // make sure extension is enabled and settings are read
        ezpExtensionHelper::load( 'ezmemcachedcluster' );
    }

    public function tearDown()
    {
        ezpExtensionHelper::unload( 'ezmemcachedcluster' );
        parent::tearDown();
    }
}

?>