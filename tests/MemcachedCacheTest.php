<?php
namespace tests;

use Soupmix\Cache as c;
Use Memcached;
use PHPUnit\Framework\TestCase;

class MemcachedCacheTest extends TestCase
{
    /**
     * @var \Soupmix\Cache\MemcachedCache $client
     */
    protected $client = null;

    protected function setUp()
    {
        $config = [
            'bucket' => 'test',
            'hosts'   => ['127.0.0.1'],
        ];
        $handler= new Memcached($config['bucket']);
        $handler->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
        $handler->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
        if (!count($handler->getServerList())) {
            $hosts = [];
            foreach ($config['hosts'] as $host) {
                $hosts[] = [$host, 11211];
            }
            $handler->addServers($hosts);
        }
        $this->client = new c\MemcachedCache($handler);
        $this->client->clear();
    }

    public function testSetGetAndDeleteAnItem()
    {
        $ins1 = $this->client->set('test1','value1');
        $this->assertTrue($ins1);
        $value1 = $this->client->get('test1');
        $this->assertEquals('value1',$value1);
        $delete = $this->client->delete('test1');
        $this->assertTrue($delete);
    }

    public function testSetGetAndDeleteMultipleItems()
    {
        $cacheData = [
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3',
            'test4' => 'value4'
        ];
        $insMulti = $this->client->setMultiple($cacheData);
        $this->assertTrue($insMulti);

        $getMulti = $this->client->getMultiple(array_keys($cacheData));

        foreach ($cacheData as $key => $value) {
            $this->assertArrayHasKey($key, $getMulti);
            $this->assertEquals($value, $getMulti[$key]);
        }
        $deleteMulti = $this->client->deleteMultiple(array_keys($cacheData));

        foreach ($cacheData as $key => $value) {
            $this->assertTrue($deleteMulti[$key]);
        }
    }

    public function testIncrementAndDecrementACounterItem()
    {
        $this->client->set('counter', 0);
        $counter_i_1 = $this->client->increment('counter', 1);
        $this->assertEquals(1, $counter_i_1);
        $counter_i_3 = $this->client->increment('counter', 2);
        $this->assertEquals(3, $counter_i_3);
        $counter_i_4 = $this->client->increment('counter');
        $this->assertEquals(4, $counter_i_4);
        $counter_d_3 = $this->client->decrement('counter');
        $this->assertEquals(3, $counter_d_3);
        $counter_d_1 = $this->client->decrement('counter', 2);
        $this->assertEquals(1, $counter_d_1);
        $counter_d_0 = $this->client->decrement('counter', 1);
        $this->assertEquals(0, $counter_d_0);
    }

    public function testClear(){
        $clear = $this->client->clear();
        $this->assertTrue($clear);
    }

    public function testHasItem()
    {
        $has = $this->client->has('has');
        $this->assertFalse($has);
        $this->client->set('has', 'value');
        $has = $this->client->has('has');
        $this->assertTrue($has);
    }

}
