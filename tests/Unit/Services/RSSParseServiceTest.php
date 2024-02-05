<?php

namespace Tests\Unit\Services;

// use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use App\Services\RSSParseService;
use Illuminate\Support\Facades\Http;

/**
 * test 実行コマンド: vendor/bin/phpunit tests/Unit/Services/RSSParseServiceTest.php
 * sail ver 実行コマンド: sail test tests/Unit/Services/RSSParseServiceTest.php
 */
class RSSParseServiceTest extends TestCase
{
    /**
     * Test RSS parsing.
     */
    public function test_RSSのパース成功(): void
    {
        // Create an instance of the RSSParseService
        $rss_parse_service = new RSSParseService();

        // Mock the RSS feed data
        $rss_mock = '
        <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://purl.org/rss/1.0/" xmlns:admin="http://webns.net/mvcb/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:syn="http://purl.org/rss/1.0/modules/syndication/" xmlns:taxo="http://purl.org/rss/1.0/modules/taxonomy/">
            <channel>
                <items>
                    <rdf:Seq>
                        <rdf:li rdf:resource="https://example.com/item1"/>
                        <rdf:li rdf:resource="https://example.com/item2"/>
                    </rdf:Seq>
                </items>
            </channel>
            <item rdf:about="https://example.com/item1">
                <title>Item 1</title>
                <link>https://example.com/item1</link>
            </item>
            <item rdf:about="https://example.com/item2">
                <title>Item 2</title>
                <link>https://example.com/item2</link>
            </item>
        </rdf:RDF>';

        // Mock the Http facade to return the RSS feed data
        Http::fake([
            '*' => Http::response($rss_mock, 200),
        ]);

        // Call the parse method and assert the result
        $response = $rss_parse_service->fetchEntries();

        $expected_data = [
            [
                'title' => 'Item 1',
                'link' => 'https://example.com/item1',
            ],
            [
                'title' => 'Item 2',
                'link' => 'https://example.com/item2',
            ],
        ];

        $this->assertEquals($expected_data, $response);
    }
}
