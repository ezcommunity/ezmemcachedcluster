<?php
/**
 * File containing the eZMemcachedClusterClientMemcachedTest class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

/**
 * Test class for eZMemcachedClusterClientMemcached class
 * @backupGlobals false
 * @group ezmemcachedcluster
 * @group ezmemcachedclusterclient
 */
class eZMemcachedClusterClientMemcachedTest extends ezpDatabaseTestCase
{
    /**
     * @var eZMemcachedClusterClientMemcached
     */
    private $client;

    /**
     * Memcached gateway
     *
     * @var Memcached
     */
    private $gateway;

    private $keys = array();

    protected function setUp()
    {
        parent::setUp();
        $this->client = new eZMemcachedClusterClientMemcached;
        $this->gateway = new Memcached;
        $this->gateway->addServer( 'localhost', 11211 );

        $obj = new stdClass;
        $obj->foo = 'bar';
        $obj->truc = 'muche';
        $this->keys = array(
            'foo' => 'bar',
            'array' => array( 'foo' => 'bar' ),
            'object' => $obj
        );
        foreach ( $this->keys as $keyName => $value )
        {
            $this->gateway->add( $keyName, $value );
        }
    }

    protected function tearDown()
    {
        $this->gateway->flush();
        unset( $this->client );
        parent::tearDown();
    }

    /**
     * @covers eZMemcachedClusterClientMemcached::initialize
     */
    public function testInitialize()
    {
        $options = new eZMemcachedClusterOptions;
        $options->servers = array( 'localhost:11211' );
        $options->connectTimeout = 10000;
        $options->prefixKey = 'myPrefix';
        $options->useBinaryProtocol = false;
        $options->useBuffer = false;
        $options->useCompression = true;
        $this->client->initialize( $options );

        $refObj = new ReflectionObject( $this->client );
        $refProp = $refObj->getProperty( 'gateway' );
        $refProp->setAccessible( true );
        $gateway = $refProp->getValue( $this->client );
        self::assertSame( $options->connectTimeout, $gateway->getOption( Memcached::OPT_CONNECT_TIMEOUT ) );
        self::assertSame( $options->prefixKey, $gateway->getOption( Memcached::OPT_PREFIX_KEY ) );
        self::assertSame( (int)$options->useBinaryProtocol, $gateway->getOption( Memcached::OPT_BINARY_PROTOCOL ) );
        self::assertSame( (int)$options->useBuffer, $gateway->getOption( Memcached::OPT_BUFFER_WRITES ) );
        self::assertSame( $options->useCompression, $gateway->getOption( Memcached::OPT_COMPRESSION ) );
    }

    /**
     * @expectedException eZMemcachedException
     * @covers eZMemcachedClusterClientMemcached::initialize
     */
    public function testInitializeFail()
    {
        $options = new eZMemcachedClusterOptions;
        $options->servers = array( 'nonexistingserver:11211' );
        $this->client->initialize( $options );
        $val = $this->client->get( 'dummy' );
    }

    /**
     * Shorthand method to initialize Memcached client
     */
    private function initializeClient()
    {
        $options = new eZMemcachedClusterOptions;
        $options->servers = array( 'localhost:11211' );
        $options->usePersistentConnection = true;
        $options->connectionIdentifier = 'foobarconnection';
        $options->prefixKey = null;
        $this->client->initialize( $options );
    }

    /**
     * @covers eZMemcachedClusterClientMemcached::get
     */
    public function testGetNotFound()
    {
        $this->initializeClient();
        self::assertFalse( $this->client->get( 'nonExistingKey' ) );
    }

    /**
     * As set() is not tested yet, we'll first define a key directly via the gateway
     * @covers eZMemcachedClusterClientMemcached::get
     */
    public function testGet()
    {
        $this->initializeClient();
        foreach ( $this->keys as $keyName => $value )
        {
            self::assertEquals( $value, $this->client->get( $keyName ) );
        }
    }

    /**
     * @covers eZMemcachedClusterClientMemcached::set
     */
    public function testSet()
    {
        $this->initializeClient();
        self::assertTrue( $this->client->set( 'someKey', 'someValue', 0 ) );
        self::assertSame( 'someValue', $this->client->get( 'someKey' ) );
        self::assertTrue( $this->client->set( 'someKey', 'someNewValue', 0 ) );
        self::assertSame( 'someNewValue', $this->client->get( 'someKey' ) );
    }

    /**
     * Testing that the CAS (check and set) works as expected.
     * Test outline:
     *  - Set a key by client
     *  - Modify it by another client
     *  - Try to modify it again by the 1st client => Should fail
     *
     * @see http://fr.php.net/manual/fr/memcached.cas.php
     * @covers eZMemcachedClusterClientMemcached::set
     */
    public function testSetFail()
    {
        $this->initializeClient();
        self::assertTrue( $this->client->set( 'someKey', 'someValue', 0 ) );
        $this->gateway->set( 'someKey', '3rdPartyModifiedValue' );
        self::assertFalse( $this->client->set( 'someKey', 'someNewValue', 0 ) );
        self::assertSame( '3rdPartyModifiedValue', $this->client->get( 'someKey' ) );
    }

    /**
     * @covers eZMemcachedClusterClientMemcached::delete
     */
    public function testDelete()
    {
        $this->initializeClient();
        self::assertSame( $this->keys['foo'] , $this->client->get( 'foo' ) );
        $this->client->delete( 'foo' );
        self::assertFalse( $this->client->get( 'foo' ) );
    }

    /**
     * @covers eZMemcachedClusterClientMemcached::flush
     */
    public function testFlush()
    {
        $this->initializeClient();
        foreach ( $this->keys as $keyName => $value )
        {
            self::assertEquals( $value, $this->client->get( $keyName ) );
        }

        $this->client->flush();
        foreach ( $this->keys as $keyName => $value )
        {
            self::assertFalse( $this->client->get( $keyName ) );
        }
    }

    /**
     * @covers eZMemcachedClusterClientMemcached::addToMap
     */
    public function testAddToMap()
    {
        $this->initializeClient();
        $mapId = 'someMapId';
        $this->client->addToMap( $mapId, 'someValue' );
        self::assertSame(
            array( 'someValue' => true ),
            $this->client->get( $mapId )
        );

        $this->client->addToMap( $mapId, 'anotherValue' );
        self::assertSame(
            array(
                'someValue' => true,
                'anotherValue' => true
            ),
            $this->client->get( $mapId )
        );
    }
}
