<?php
/**
 * File containing the eZMemcachedClusterClientMemcacheTest class
 *
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

/**
 * Test class for eZMemcachedClusterClientMemcache class
 * @backupGlobals false
 * @group ezmemcachedcluster
 * @group ezmemcachedclusterclient
 */
class eZMemcachedClusterClientMemcacheTest extends eZMemcachedClusterClientMemcachedTest
{
    /**
     * @var eZMemcachedClusterClientMemcache
     */
    protected $client;

    /**
     * Memcached gateway
     *
     * @var Memcache
     */
    protected $gateway;

    protected function setUp()
    {
        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @return eZMemcachedClusterClientMemcache
     */
    protected function getMemcachedClient()
    {
        return new eZMemcachedClusterClientMemcache;
    }

    /**
     * @return Memcache
     */
    protected function getMemcachedGateway()
    {
        $gateway = new Memcache;
        $gateway->addServer( 'localhost', 11211 );
        return $gateway;
    }

    /**
     * @covers eZMemcachedClusterClientMemcache::initialize
     */
    public function testInitialize()
    {
        $options = new eZMemcachedClusterOptions;
        $options->servers = array( 'localhost:11211' );
        $options->connectTimeout = 10000;
        $options->useCompression = true;
        $this->client->initialize( $options );

        $refObj = new ReflectionObject( $this->client );
        $refCompressionFlag = $refObj->getProperty( 'compressionFlag' );
        $refCompressionFlag->setAccessible( true );
        self::assertSame( MEMCACHE_COMPRESSED , $refCompressionFlag->getValue(  $this->client ) );
    }

    public function testInitializeFail()
    {
        self::markTestSkipped( 'Cannot run this test because of PECL Memcache limitation' );
    }

    /**
     * Shorthand method to initialize Memcached client
     */
    protected function initializeClient()
    {
        $options = new eZMemcachedClusterOptions;
        $options->servers = array( 'localhost:11211' );
        $options->usePersistentConnection = true;
        $options->prefixKey = null;
        $this->client->initialize( $options );
    }

    public function testSetFail()
    {
        $this->initializeClient();
        self::assertTrue( $this->client->set( 'someKey', 'someValue', 0 ) );
        $this->gateway->set( 'writeLock:someKey', 1 );
        self::assertFalse( $this->client->set( 'someKey', 'someNewValue', 0 ) );
    }
}
