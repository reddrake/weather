<?php

namespace Reddrake\Weather\Tests;

use PHPUnit\Framework\TestCase;
use Reddrake\Weather\Exceptions\InvalidArgumentException;
use Reddrake\Weather\Weather;
use GuzzleHttp\Psr7\Response;
use Mockery\Matcher\AnyArgs;
use GuzzleHttp\Client;
use Reddrake\Weather\Exceptions\HttpException;
use GuzzleHttp\ClientInterface;

class WeatherTest extends TestCase
{
    public function testGetWeatherToJson()
    {
        $response = new Response(200, [], '{"success": true}');

        $client = \Mockery::mock(Client::class);
        $client->allows()->get('https://restapi.amap.com/v3/weather/weatherInfo', [
            'query' => [
                'key' => 'mock-key',
                'city' => '成都',
                'output' => 'json',
                'extensions' => 'base'
            ]
        ])->andReturn($response);

        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->assertSame(['success' => true], $w->getWeather('成都'));
    }

    public function testGetWeatherToXml()
    {
        $response = new Response(200, [], '<hello>abcdef</hello>');

        $client = \Mockery::mock(Client::class);
        $client->allows()->get('https://restapi.amap.com/v3/weather/weatherInfo', [
            'query' => [
                'key' => 'mock-key',
                'city' => '成都',
                'output' => 'xml',
                'extensions' => 'all'
            ]
        ])->andReturn($response);

        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->assertSame('<hello>abcdef</hello>', $w->getWeather('成都', 'all', 'xml'));
    }

    public function testGetLiveWeather()
    {
        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->expects()->getWeather('成都', 'base', 'json')->andReturn(['success' => true]);

        $this->assertSame(['success' => true], $w->getLiveWeather('成都'));
    }

    public function testGetForecastsWeather()
    {
        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->expects()->getWeather('成都', 'all', 'json')->andReturn(['success' => true]);

        $this->assertSame(['success' => true], $w->getForecastsWeather('成都'));
    }

    public function testGetWeatherWithInvalidType()
    {
        $w = new Weather('mock-key');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid type value(base/all): foo');

        $w->getWeather('成都', 'foo');

        $this->fail('Failed to assert getWeather throw exception with invalid argument.');
    }

    public function testGetWeatherWithInvalidFormat()
    {
        $w = new Weather('mock-key');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid response format: array');

        $w->getWeather('成都', 'base', 'array');

        $this->fail('Failed to assert getWeather throw exception with invalid argument.');
    }

    public function testGetWeatherWithGuzzleRuntimeException()
    {
        $client = \Mockery::mock(Client::class);
        $client->allows()
            ->get(new AnyArgs())
            ->andThrow(new \Exception('request timeout'));

        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('request timeout');

        $w->getWeather('成都');
    }

    public function testGetHttpClient()
    {
        $w = new Weather('mock-key');

        $this->assertInstanceOf(ClientInterface::class, $w->getHttpClient());
    }

    public function testSetGuzzleOptions()
    {
        $w = new Weather('mock-key');

        $this->assertNull($w->getHttpClient()->getConfig('timeout'));

        $w->setGuzzleOptions(['timeout' => 500]);

        $this->assertSame(500, $w->getHttpClient()->getConfig('timeout'));
    }
}
