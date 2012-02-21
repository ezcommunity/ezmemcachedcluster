<?php
/**
 * File containing the eZMemcachedClusterEventListenerTest class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

/**
 * Test class for eZMemcachedClusterEventListener class
 * @backupGlobals false
 * @group ezmemcachedcluster
 * @group ezmemcachedclusterlistener
 */
class eZMemcachedClusterEventListenerTest extends ezpDatabaseTestCase
{
    /**
     * Mocked up configuration handler
     *
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $confHandler;

    /**
     * Mocked up logger
     *
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * Expected options to be passed to the client
     *
     * @var eZMemcachedClusterOptions
     */
    private $clientOptions;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $clientMock;

    protected function setUp()
    {
        parent::setUp();
        $this->clientOptions = new eZMemcachedClusterOptions;
        $this->clientOptions->usePersistentConnection = true;
        $this->clientOptions->connectionIdentifier = 'ezmemcached';
        $this->clientOptions->servers = array( 'localhost:11211' );
        $this->clientMock = $this->getMock( 'eZMemcachedClusterClient' );

        $this->confHandler = $this->getConfHandlerMock( $this->clientMock, $this->clientOptions );
        $this->logger = $this->getMock( 'eZClusterEventLogger' );
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Builds the configuration handler mock to be used for tests
     *
     * @param eZMemcachedClusterClient $client
     * @param eZMemcachedClusterOptions $options
     * @return eZMemcachedClusterConfigurationHandler
     */
    private function getConfHandlerMock( eZMemcachedClusterClient $client, eZMemcachedClusterOptions $options )
    {
        $confHandler = $this->getMock( 'eZMemcachedClusterConfigurationHandler' );
        $confHandler->expects( $this->any() )
                    ->method( 'getOptions' )
                    ->will( $this->returnValue( $options ) );

        $confHandler->expects( $this->any() )
                    ->method( 'getClient' )
                    ->will( $this->returnValue( $client ) );

        return $confHandler;
    }

    /**
     * @covers eZMemcachedClusterEventListener::initialize
     */
    public function testInitialize()
    {
        $listener = new eZMemcachedClusterEventListener( $this->logger, $this->confHandler );
        $this->clientMock->expects( $this->once() )
                         ->method( 'initialize' )
                         ->with( $this->equalTo( $this->clientOptions ) );
        $listener->initialize();
    }

    /**
     * @covers eZMemcachedClusterEventListener::initialize
     * @expectedException eZMemcachedException
     */
    public function testInitializeFail()
    {
        $listener = new eZMemcachedClusterEventListener( $this->logger, $this->confHandler );
        $this->clientMock->expects( $this->once() )
                         ->method( 'initialize' )
                         ->with( $this->equalTo( $this->clientOptions ) )
                         ->will( $this->throwException( new eZMemcachedException ) );
        $this->logger->expects( $this->once() )
                     ->method( 'logError' );
        $listener->initialize();
    }

    private function getExpectedMetadata( $filepath )
    {
        return array(
            'name'          => $filepath,
            'name_trunk'    => $filepath,
            'name_hash'     => md5( $filepath ),
            'scope'         => 'viewcache',
            'datatype'      => 'misc',
            'mtime'         => time(),
            'expired'       => 0
        );
    }

    /**
     * @covers eZMemcachedClusterEventListener::loadMetadata
     */
    public function testLoadMetadata()
    {
        $filepath = 'foo/bar';
        $expectedMeta = $this->getExpectedMetadata( $filepath );

        $this->clientMock->expects( $this->once() )
                         ->method( 'get' )
                         ->with( $this->equalTo( md5( $filepath ) ) )
                         ->will( $this->returnValue( $expectedMeta ) );
        $listener = new eZMemcachedClusterEventListener( $this->logger, $this->confHandler );
        $listener->loadMetadata( $filepath );
    }

    /**
     * @covers eZMemcachedClusterEventListener::loadMetadata
     */
    public function testLoadMetadataFail()
    {
        $filepath = 'foo/bar';
        $this->clientMock->expects( $this->once() )
                         ->method( 'get' )
                         ->with( $this->equalTo( md5( $filepath ) ) )
                         ->will( $this->throwException( new eZMemcachedException ) );
        $this->logger->expects( $this->once() )
                     ->method( 'logError' );

        $listener = new eZMemcachedClusterEventListener( $this->logger, $this->confHandler );
        self::assertFalse( $listener->loadMetadata( $filepath ) );
    }

    /**
     * @covers eZMemcachedClusterEventListener::storeMetadata
     */
    public function testStoreMetadata()
    {
        $metadata = $this->getExpectedMetadata( __METHOD__ );
        $this->clientMock->expects( $this->once() )
                         ->method( 'set' )
                         ->with( md5( __METHOD__ ), $metadata );
        $listener = new eZMemcachedClusterEventListener( $this->logger, $this->confHandler );
        $listener->storeMetadata( $metadata );
    }

    /**
     * @covers eZMemcachedClusterEventListener::storeMetadata
     */
    public function testStoreMetadataWithNametrunk()
    {
        $metadata = $this->getExpectedMetadata( __METHOD__ );
        $metadata['name_trunk'] = 'i/am/a/nametrunk';
        $filepathHash = md5( __METHOD__ );
        $this->clientMock->expects( $this->once() )
                         ->method( 'set' )
                         ->with( $filepathHash, $metadata );
        $this->clientMock->expects( $this->once() )
                         ->method( 'addToMap' )
                         ->with( $metadata['name_trunk'], $filepathHash );
        $listener = new eZMemcachedClusterEventListener( $this->logger, $this->confHandler );
        $listener->storeMetadata( $metadata );
    }

    /**
     * @covers eZMemcachedClusterEventListener::storeMetadata
     */
    public function testStoreMetadataFail()
    {
        $metadata = $this->getExpectedMetadata( __METHOD__ );
        $this->clientMock->expects( $this->once() )
                         ->method( 'set' )
                         ->with( md5( __METHOD__ ), $metadata )
                         ->will( $this->throwException( new eZMemcachedException ) );
        $this->logger->expects( $this->once() )
                     ->method( 'logError' );
        $listener = new eZMemcachedClusterEventListener( $this->logger, $this->confHandler );
        $listener->storeMetadata( $metadata );
    }

    /**
     * @covers eZMemcachedClusterEventListener::fileExists
     */
    public function testFileExists()
    {
        $metadata = $this->getExpectedMetadata( __METHOD__ );
        $this->clientMock->expects( $this->once() )
                         ->method( 'get' )
                         ->with( $this->equalTo( md5( __METHOD__ ) ) )
                         ->will( $this->returnValue( $metadata ) );
        $listener = new eZMemcachedClusterEventListener( $this->logger, $this->confHandler );
        self::assertSame(
            array(
                'name'  => $metadata['name'],
                'mtime' => $metadata['mtime']
            ),
            $listener->fileExists( __METHOD__ )
        );
    }

    /**
     * @covers eZMemcachedClusterEventListener::fileExists
     */
    public function testFileNotExists()
    {
        $metadata = $this->getExpectedMetadata( __METHOD__ );
        $this->clientMock->expects( $this->once() )
                         ->method( 'get' )
                         ->with( $this->equalTo( md5( __METHOD__ ) ) )
                         ->will( $this->returnValue( false ) );
        $listener = new eZMemcachedClusterEventListener( $this->logger, $this->confHandler );
        $listener->initialize();
        self::assertFalse( $listener->fileExists( __METHOD__ ) );
    }

    /**
     * @covers eZMemcachedClusterEventListener::fileExists
     */
    public function testFileExistsFail()
    {
        $this->clientMock->expects( $this->once() )
                         ->method( 'get' )
                         ->with( $this->equalTo( md5( __METHOD__ ) ) )
                         ->will( $this->throwException( new eZMemcachedException ) );
        $this->logger->expects( $this->once() )
                     ->method( 'logError' );
        $listener = new eZMemcachedClusterEventListener( $this->logger, $this->confHandler );
        self::assertFalse( $listener->fileExists( __METHOD__ ) );
    }

    /**
     * @covers eZMemcachedClusterEventListener::deleteFile
     */
    public function testDeleteFile()
    {
        $this->clientMock->expects( $this->once() )
                         ->method( 'delete' )
                         ->with( $this->equalTo( md5( __METHOD__ ) ) );
        $listener = new eZMemcachedClusterEventListener( $this->logger, $this->confHandler );
        $listener->deleteFile( __METHOD__ );
    }

    /**
     * @covers eZMemcachedClusterEventListener::deleteByLike
     */
    public function testDeleteByLike()
    {
        $this->clientMock->expects( $this->once() )
                         ->method( 'flush' );
        $listener = new eZMemcachedClusterEventListener( $this->logger, $this->confHandler );
        $listener->deleteByLike( __METHOD__ );
    }

    /**
     * @covers eZMemcachedClusterEventListener::deleteByWildcard
     */
    public function testDeleteByWildcard()
    {
        $this->clientMock->expects( $this->once() )
                         ->method( 'flush' );
        $listener = new eZMemcachedClusterEventListener( $this->logger, $this->confHandler );
        $listener->deleteByWildcard( __METHOD__ );
    }

    /**
     * @covers eZMemcachedClusterEventListener::deleteByDirList
     */
    public function testDeleteByDirList()
    {
        $this->clientMock->expects( $this->once() )
                         ->method( 'flush' );
        $listener = new eZMemcachedClusterEventListener( $this->logger, $this->confHandler );
        $listener->deleteByDirList( array( __METHOD__ ), 'foo', '.bar' );
    }

    /**
     * @covers eZMemcachedClusterEventListener::deleteByNametrunk
     */
    public function testDeleteByNonExistentNametrunk()
    {
        $nametrunk = 'i/am/a/nametrunk';
        $this->clientMock->expects( $this->once() )
                         ->method( 'get' )
                         ->with( $this->equalTo( $nametrunk ) )
                         ->will( $this->returnValue( false ) );
        $this->clientMock->expects( $this->never() )
                         ->method( 'delete' );
        $listener = new eZMemcachedClusterEventListener( $this->logger, $this->confHandler );
        $listener->deleteByNametrunk( $nametrunk );
    }

    /**
     * @covers eZMemcachedClusterEventListener::deleteByNametrunk
     */
    public function testDeleteByNametrunk()
    {
        $nametrunk = 'i/am/a/nametrunk';
        $expectedHashes = array();
        for ( $i = 0; $i < 10; ++$i )
        {
            $hash = md5( mt_rand() . microtime() );
            $expectedHashes[$hash] = true;
        }

        $i = 0;
        $this->clientMock->expects( $this->at( $i++ ) )
                         ->method( 'get' )
                         ->with( $this->equalTo( $nametrunk ) )
                         ->will( $this->returnValue( $expectedHashes ) );
        foreach ( $expectedHashes as $hash )
        {
            $this->clientMock->expects( $this->at( $i ) )
                             ->method( 'delete' )
                             ->with( $this->equalTo( $hash ) );
            $i++;
        }

        $this->clientMock->expects( $this->at( $i++ ) )
                         ->method( 'delete' )
                         ->with( $this->equalTo( $nametrunk ) );
        $listener = new eZMemcachedClusterEventListener( $this->logger, $this->confHandler );
        $listener->deleteByNametrunk( $nametrunk );
    }

    /**
     * @covers eZMemcachedClusterEventListener::flush
     */
    public function testFlush()
    {
        $this->clientMock->expects( $this->once() )
                         ->method( 'flush' );
        $listener = new eZMemcachedClusterEventListener( $this->logger, $this->confHandler );
        $listener->initialize();
        $listener->flush();
    }
}
