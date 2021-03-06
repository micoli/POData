<?php

declare(strict_types=1);

namespace UnitTests\POData\Writers\Json;

use Mockery as m;
use POData\Common\MimeTypes;
use POData\Common\ODataConstants;
use POData\Common\Version;
use POData\ObjectModel\ODataBagContent;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataExpandedResult;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataMediaLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataTitle;
use POData\ObjectModel\ODataURL;
use POData\ObjectModel\ODataURLCollection;
use POData\Providers\ProvidersWrapper;
use POData\Writers\Json\JsonODataV2Writer;
use UnitTests\POData\Writers\BaseWriterTest;

/**
 * Class JsonODataV2WriterTest.
 * @package UnitTests\POData\Writers\Json
 */
class JsonODataV2WriterTest extends BaseWriterTest
{
    public function testWriteURL()
    {
        $oDataUrl      = new ODataURL('http://services.odata.org/OData/OData.svc/Suppliers(0)');
        $writer        = new JsonODataV2Writer(PHP_EOL, true);
        $result        = $writer->write($oDataUrl);
        $this->assertSame($writer, $result);

        //decoding the json string to test, there is no json string comparison in php unit
        $actual = json_decode($writer->getOutput());

        $expected = '{ "d" : {"uri": "http://services.odata.org/OData/OData.svc/Suppliers(0)"} }';
        $expected = json_decode($expected);
        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());
    }

    public function testWriteURLCollection()
    {
        //see http://services.odata.org/v3/OData/OData.svc/Categories(1)/$links/Products?$format=application/json;odata=verbose

        $oDataUrlCollection       = new ODataURLCollection(
            [
                new ODataURL('http://services.odata.org/OData/OData.svc/Products(0)'),
                new ODataURL('http://services.odata.org/OData/OData.svc/Products(7)'),
                new ODataURL('http://services.odata.org/OData/OData.svc/Products(8)')
            ],
            null,
            3
        );
        $writer                    = new JsonODataV2Writer(PHP_EOL, true);
        $result                    = $writer->write($oDataUrlCollection);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
		                "d" : {
							"results": [
								{
							        "uri": "http://services.odata.org/OData/OData.svc/Products(0)"
								},
							    {
							        "uri": "http://services.odata.org/OData/OData.svc/Products(7)"
							    },
							    {
							        "uri": "http://services.odata.org/OData/OData.svc/Products(8)"
							    }
							],
							"__count": "3"
						}
					}';

        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());
    }

    public function testWriteFeed()
    {
        $oDataFeed        = new ODataFeed();
        $oDataFeed->id    = 'FEED ID';
        $oDataFeed->setTitle(new ODataTitle('FEED TITLE'));
        //self link
        $selfLink            = new ODataLink(
            'Products',
            'Products',
            null,
            'Categories(0)/Products'
        );
        $oDataFeed->setSelfLink($selfLink);
        //self link end
        $oDataFeed->setRowCount(3);

        //next page link
        $oDataFeed->setNextPageLink(
            new ODataLink(
                'Next Page Link',
                'Next Page',
                null,
                'http://services.odata.org/OData/OData.svc$skiptoken=12'
            )
        );
        //feed entries

        //entry1
        $entry1 = $this->buildSingleEntry();

        $entry1->isExpanded       = false;
        $entry1->isMediaLinkEntry = false;

        //entry 1 links
        //link1
        $link1        = new ODataLink(
            'http://services.odata.org/OData/OData.svc/Products(0)/Categories',
            'Categories',
            null,
            'http://services.odata.org/OData/OData.svc/Products(0)/Categories'
        );

        $entry1->links = [$link1];
        //entry 1 links end

        //entry 1 end
        $oDataFeed->setEntries([$entry1]);

        $writer = new JsonODataV2Writer(PHP_EOL, true);
        $result = $writer->write($oDataFeed);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual   = json_decode($writer->getOutput());
        $expected = '{
					    "d" : {
							"__count": "3",
					        "__next": "http://services.odata.org/OData/OData.svc$skiptoken=12",
					        "results": [
					            {
					                "__metadata": {
					                    "uri": "http://services.odata.org/OData/OData.svc/Products(0)",
					                    "type": "DataServiceProviderDemo.Product"
					                },
					                "Categories": {
					                    "__deferred": {
					                        "uri": "http://services.odata.org/OData/OData.svc/Products(0)/Categories"
					                    }
					                },
					                "ID": 100,
					                "Name": "Bread",
					                "ReleaseDate" : "/Date(1346990823000)/",
					                "DiscontinuedDate" : null,
					                "Price" : 2.5
					            }
					        ]
					    }
					}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());
    }

    public function testWriteFeedWithEntriesWithComplexProperty()
    {

        //entry1
        $entry1 = $this->buildEntryWithComplexProperties();

        $entry1->isExpanded       = false;
        $entry1->isMediaLinkEntry = false;

        //entry 1 links
        //link1
        $link1        = new ODataLink(
            'Products',
            'Products',
            null,
            'http://services.odata.org/OData/OData.svc/Suppliers(0)/Products'
        );

        $entry1->links = [$link1];
        //entry 1 links end

        //entry 1 end

        //entry 2
        $entry2 = $this->buildSecondEntryWithComplexProperties();

        $entry2->isExpanded       = false;
        $entry2->isMediaLinkEntry = false;

        //entry 2 links
        //link1
        $link1        = new ODataLink(
            'Products',
            'Products',
            null,
            'http://services.odata.org/OData/OData.svc/Suppliers(1)/Products'
        );

        $entry2->links = [$link1];
        //entry 2 links end

        //entry 2 end

        $oDataFeed        = new ODataFeed();
        $oDataFeed->id    = 'FEED ID';
        $oDataFeed->setTitle(new ODataTitle('FEED TITLE'));
        //self link
        $selfLink            = new ODataLink(
            'Products',
            'Products',
            null,
            'Categories(0)/Products'
        );
        $oDataFeed->setSelfLink($selfLink);
        //self link end
        $oDataFeed->setRowCount(13);

        //next page
        $oDataFeed->setNextPageLink(
            new ODataLink(
                'Next Page Link',
                'Next Page',
                null,
                'http://services.odata.org/OData/OData.svc$skiptoken=12'
            )
        );
        //feed entries
        $oDataFeed->setEntries([$entry1, $entry2]);

        $writer = new JsonODataV2Writer(PHP_EOL, true);
        $result = $writer->write($oDataFeed);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual   = json_decode($writer->getOutput());
        $expected = '{
						"d" : {
							"__count": "13",
							"__next": "http:\/\/services.odata.org\/OData\/OData.svc$skiptoken=12",
                            "results": [
								{
									"__metadata": {
										"uri": "http://services.odata.org/OData/OData.svc/Suppliers(0)",
										"etag": "W/\"0\"", "type": "ODataDemo.Supplier"
									},
									"ID": 0,
									"Name": "Exotic Liquids",
									"Address": {
										"__metadata": {
											"type": "ODataDemo.Address"
										},
										"Street": "NE 228th",
										 "City": "Sammamish",
										 "State": "WA",
										 "ZipCode": "98074",
										 "Country": "USA"
									},
									"Concurrency": 0,
									"Products": {
									        "__deferred": {
												"uri": "http://services.odata.org/OData/OData.svc/Suppliers(0)/Products"
											}
									}
								},
								{
									"__metadata": {
										"uri": "http://services.odata.org/OData/OData.svc/Suppliers(1)",
										"etag": "W/\"0\"", "type": "ODataDemo.Supplier"
									},
									"ID": 1,
									"Name": "Tokyo Traders",
									"Address": {
										"__metadata": {
											"type": "ODataDemo.Address"
										},
										"Street": "NE 40th",
										"City": "Redmond",
										"State": "WA",
										"ZipCode": "98052",
										"Country": "USA"
									},
									"Concurrency": 0,
									"Products": {
										"__deferred": {
											"uri": "http://services.odata.org/OData/OData.svc/Suppliers(1)/Products"
										}
									}
								}
							]
						}
					}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());

        $oDataFeed->setRowCount(null);
        $writer              = new JsonODataV2Writer(PHP_EOL, true);
        $result              = $writer->write($oDataFeed);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual   = json_decode($writer->getOutput());
        $expected = '{
						"d" : {
							"__next": "http:\/\/services.odata.org\/OData\/OData.svc$skiptoken=12",
                            "results": [
								{
									"__metadata": {
										"uri": "http://services.odata.org/OData/OData.svc/Suppliers(0)",
										"etag": "W/\"0\"", "type": "ODataDemo.Supplier"
									},
									"ID": 0,
									"Name": "Exotic Liquids",
									"Address": {
										"__metadata": {
											"type": "ODataDemo.Address"
										},
										"Street": "NE 228th",
										 "City": "Sammamish",
										 "State": "WA",
										 "ZipCode": "98074",
										 "Country": "USA"
									},
									"Concurrency": 0,
									"Products": {
									        "__deferred": {
												"uri": "http://services.odata.org/OData/OData.svc/Suppliers(0)/Products"
											}
									}
								},
								{
									"__metadata": {
										"uri": "http://services.odata.org/OData/OData.svc/Suppliers(1)",
										"etag": "W/\"0\"", "type": "ODataDemo.Supplier"
									},
									"ID": 1,
									"Name": "Tokyo Traders",
									"Address": {
										"__metadata": {
											"type": "ODataDemo.Address"
										},
										"Street": "NE 40th",
										"City": "Redmond",
										"State": "WA",
										"ZipCode": "98052",
										"Country": "USA"
									},
									"Concurrency": 0,
									"Products": {
										"__deferred": {
											"uri": "http://services.odata.org/OData/OData.svc/Suppliers(1)/Products"
										}
									}
								}
							]
						}
					}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());

        $oDataFeed->setNextPageLink(null);
        $writer                  = new JsonODataV2Writer(PHP_EOL, true);
        $result                  = $writer->write($oDataFeed);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual   = json_decode($writer->getOutput());
        $expected = '{
						"d" : {
                            "results": [
								{
									"__metadata": {
										"uri": "http://services.odata.org/OData/OData.svc/Suppliers(0)",
										"etag": "W/\"0\"", "type": "ODataDemo.Supplier"
									},
									"ID": 0,
									"Name": "Exotic Liquids",
									"Address": {
										"__metadata": {
											"type": "ODataDemo.Address"
										},
										"Street": "NE 228th",
										 "City": "Sammamish",
										 "State": "WA",
										 "ZipCode": "98074",
										 "Country": "USA"
									},
									"Concurrency": 0,
									"Products": {
									        "__deferred": {
												"uri": "http://services.odata.org/OData/OData.svc/Suppliers(0)/Products"
											}
									}
								},
								{
									"__metadata": {
										"uri": "http://services.odata.org/OData/OData.svc/Suppliers(1)",
										"etag": "W/\"0\"", "type": "ODataDemo.Supplier"
									},
									"ID": 1,
									"Name": "Tokyo Traders",
									"Address": {
										"__metadata": {
											"type": "ODataDemo.Address"
										},
										"Street": "NE 40th",
										"City": "Redmond",
										"State": "WA",
										"ZipCode": "98052",
										"Country": "USA"
									},
									"Concurrency": 0,
									"Products": {
										"__deferred": {
											"uri": "http://services.odata.org/OData/OData.svc/Suppliers(1)/Products"
										}
									}
								}
							]
						}
					}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());
    }

    public function testWriteEntry()
    {
        //IE http://services.odata.org/v3/OData/OData.svc/Products(0)?$format=application/json;odata=verbose

        //entry
        $entry = $this->buildTestEntry();

        $writer = new JsonODataV2Writer(PHP_EOL, true);
        $result = $writer->write($entry);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
						"d" : {
							"__metadata": {
								"uri": "http://services.odata.org/OData/OData.svc/Categories(0)", "type": "ODataDemo.Category"
							},
							"ID": 0,
							"Name": "Food",
							"Products": {
								"__deferred": {
									"uri": "http://services.odata.org/OData/OData.svc/Categories(0)/Products"
								}
							}
						}
					}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());
    }

    public function testWriteComplexProperty()
    {
        //see http://services.odata.org/v3/OData/OData.svc/Suppliers(0)/Address?$format=application/json;odata=verbose

        //property

        $propContent = $this->buildComplexProperty();

        $writer = new JsonODataV2Writer(PHP_EOL, true);
        $result = $writer->write($propContent);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
						"d" : {
							"Address": {
								"__metadata": {
									"type": "ODataDemo.Address"
								},
								"Street": "NE 228th",
								"City": "Sammamish",
								"State": "WA",
								"ZipCode": "98074",
								"Country": "USA"
								}
						}
					}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());
    }

    public function testEntryWithBagProperty()
    {
        //TODO: bags are not available till v3 see https://github.com/balihoo/POData/issues/79

        //entry
        $entry = $this->buildEntryWithBagProperty();

        $writer = new JsonODataV2Writer(PHP_EOL, true);
        $result = $writer->write($entry);
        $this->assertSame($writer, $result, 'raw JSON is: ' . $writer->getOutput());

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
						"d" : {
							"__metadata": {
								"uri": "http://host/service.svc/Customers(1)",
								"type": "SampleModel.Customer",
								"etag": "some eTag"
							},
							"ID": 1,
							"Name": "mike",
							"EmailAddresses": {
					            "__metadata": {
					                "type": "Bag(Edm.String)"
					            },
					            "results": [
					                "mike@foo.com", "mike2@foo.com"
					            ]
				            },
				            "Addresses": {
				                "__metadata": {
				                    "type": "Bag(SampleModel.Address)"
				                },
				                "results": [
				                    {
				                        "Street": "123 contoso street",
				                        "Apartment": "508"
				                    },
				                    {
				                        "Street": "834 foo street",
				                        "Apartment": "102"
				                    }
				                ]
				            }
					    }
					}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());
    }

    public function testPrimitiveProperty()
    {

        //see http://services.odata.org/v3/OData/OData.svc/Products(0)/Rating?$format=application/json;odata=verbose

        $content             = new ODataPropertyContent(
            [
                new ODataProperty(
                    'SomeProp',
                    'Edm.Int16',
                    56
                )
            ]
        );

        $writer = new JsonODataV2Writer(PHP_EOL, true);
        $result = $writer->write($content);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
						"d" : {
							"SomeProp": 56
						}
					}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());
    }

    public function testWriteEntryWithExpandedEntry()
    {
        //First build up the expanded entry
        $entry = $this->buildEntryWithExpandedEntry();

        $writer = new JsonODataV2Writer(PHP_EOL, true);
        $result = $writer->write($entry);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
    "d":{
        "__metadata":{
            "uri":"Main Entry",
            "etag":"Entry ETag",
            "type":"Main.Type"
        },
        "Expanded Property":{
            "__metadata":{
                "uri":"Expanded Entry 1",
                "etag":"Entry ETag",
                "type":"Expanded.Type"
            },
            "Expanded Entry Complex Property":{
                "__metadata":{
                    "type":"Full Name"
                },
                "fname":"Yash",
                "lname":"Kothari"
            },
            "Expanded Entry City Property":"Ahmedabad",
            "Expanded Entry State Property":"Gujarat"
        },
        "Main Entry Property 1":"Yash",
        "Main Entry Property 2":"Kothari"
    }
}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());
    }

    public function testWriteEntryWithExpandedEntryThatIsNull()
    {

        //build up the main entry

        $entry             = new ODataEntry();
        $entry->id         = 'Main Entry';
        $entry->setTitle(new ODataTitle('Entry Title'));
        $entry->type       = 'Main.Type';
        $entry->editLink   = 'Edit Link URL';
        $entry->setSelfLink(new ODataLink('Self Link URL'));
        $entry->mediaLinks = [
            new ODataMediaLink(
                'Media Link Name',
                'Edit Media link',
                'Src Media Link',
                'Media Content Type',
                'Media ETag'
            ),
            new ODataMediaLink(
                'Media Link Name2',
                'Edit Media link2',
                'Src Media Link2',
                'Media Content Type2',
                'Media ETag2'
            ),
        ];

        $entry->eTag             = 'Entry ETag';
        $entry->isMediaLinkEntry = false;

        $entry->propertyContent             = new ODataPropertyContent(
            [
                new ODataProperty(
                    'Main Entry Property 1',
                    'string',
                    'Yash'
                ),
                new ODataProperty(
                    'Main Entry Property 2',
                    'string',
                    'Kothari'
                )
            ]
        );
        //End of main entry

        //Now link the expanded entry to the main entry
        $expandLink                 = new ODataLink(
            null,
            'Expanded Property',
            null,
            'ExpandedURL',
            false,
            null, //<--key part
            true
        );
        $entry->links               = [$expandLink];

        $writer = new JsonODataV2Writer(PHP_EOL, true);
        $result = $writer->write($entry);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
    "d":{
        "__metadata":{
            "uri":"Main Entry",
            "etag":"Entry ETag",
            "type":"Main.Type"
        },
        "Expanded Property":null,
        "Main Entry Property 1":"Yash",
        "Main Entry Property 2":"Kothari"
    }
}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());
    }

    public function testWriteEntryWithExpandedFeed()
    {
        //First build up the expanded entry 1
        $entry = $this->buildEntryWithExpandedFeed();

        $writer = new JsonODataV2Writer(PHP_EOL, true);
        $result = $writer->write($entry);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
	"d":{
        "__metadata":{
            "uri":"Main Entry",
            "etag":"Entry ETag",
            "type":"Main.Type"
        },
        "SubCollection" : {
            "results" : [
			    {
			        "__metadata":{
			            "uri":"Expanded Entry 1",
			            "etag":"Entry ETag",
			            "type":"Expanded.Type"
			        },
			        "Expanded Entry Complex Property":{
			            "__metadata":{
		                    "type":"Full Name"
		                },
			            "first":"Entry 1 Name First",
			            "last":"Entry 1 Name Last"
			        },
			        "Expanded Entry City Property":"Entry 1 City Value",
			        "Expanded Entry State Property":"Entry 1 State Value"
			    },
			    {
			        "__metadata":{
			            "uri":"Expanded Entry 2",
			            "etag":"Entry ETag",
			            "type":"Expanded.Type"
			        },
			        "Expanded Entry Complex Property":{
				        "__metadata":{
		                    "type":"Full Name"
		                },
			            "first":"Entry 2 Name First",
			            "last":"Entry 2 Name Last"
			        },
			        "Expanded Entry City Property":"Entry 2 City Value",
			        "Expanded Entry State Property":"Entry 2 State Value"
			    }
			]
		},
	    "Main Entry Property 1":"Yash",
	    "Main Entry Property 2":"Kothari"
	}
}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());
    }

    /**
     * @var ProvidersWrapper
     */
    protected $mockProvider;

    public function testGetOutputNoResourceSets()
    {
        $this->mockProvider->shouldReceive('getResourceSets')->andReturn([]);
        $this->mockProvider->shouldReceive('getSingletons')->andReturn([]);

        $writer = new JsonODataV2Writer(PHP_EOL, true);
        $actual = $writer->writeServiceDocument($this->mockProvider)->getOutput();

        $expected = "{\n    \"d\":{\n        \"EntitySet\":[\n\n        ]\n    }\n}";

        $this->assertJsonStringEqualsJsonString($expected, $actual);
    }

    public function testGetOutputTwoResourceSets()
    {
        $fakeResourceSet1 = m::mock('POData\Providers\Metadata\ResourceSetWrapper');
        $fakeResourceSet1->shouldReceive('getName')->andReturn('Name 1');

        $fakeResourceSet2 = m::mock('POData\Providers\Metadata\ResourceSetWrapper');
        //TODO: this certainly doesn't seem right...see #73
        $fakeResourceSet2->shouldReceive('getName')->andReturn("XML escaped stuff \" ' <> & ?");

        $fakeResourceSets = [
            $fakeResourceSet1,
            $fakeResourceSet2,
        ];

        $this->mockProvider->shouldReceive('getResourceSets')->andReturn($fakeResourceSets);
        $this->mockProvider->shouldReceive('getSingletons')->andReturn([]);

        $writer = new JsonODataV2Writer(PHP_EOL, true);
        $actual = $writer->writeServiceDocument($this->mockProvider)->getOutput();

        $expected = "{\n    \"d\":{\n        \"EntitySet\":[\n            \"Name 1\",\"XML escaped stuff \\\" ' <> & ?\"\n        ]\n    }\n}";

        $this->assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * @dataProvider canHandleProvider
     * @param mixed $id
     * @param mixed $version
     * @param mixed $contentType
     * @param mixed $expected
     */
    public function testCanHandle($id, $version, $contentType, $expected)
    {
        $writer = new JsonODataV2Writer(PHP_EOL, true);

        $actual = $writer->canHandle($version, $contentType);

        $this->assertEquals($expected, $actual, strval($id));
    }

    public function canHandleProvider()
    {
        return [
            [100, Version::v1(), MimeTypes::MIME_APPLICATION_ATOMSERVICE, false],
            [101, Version::v2(), MimeTypes::MIME_APPLICATION_ATOMSERVICE, false],
            [102, Version::v3(), MimeTypes::MIME_APPLICATION_ATOMSERVICE, false],

            [200, Version::v1(), MimeTypes::MIME_APPLICATION_JSON, false],
            [201, Version::v2(), MimeTypes::MIME_APPLICATION_JSON, true],
            [202, Version::v3(), MimeTypes::MIME_APPLICATION_JSON, false],

            //TODO: is this second one right?  this should NEVER come up, but should we claim to handle this format when
            //it's invalid for V1? Ditto first of the next sections
            [300, Version::v1(), MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, false],
            [301, Version::v2(), MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, true],
            [302, Version::v3(), MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, false],

            [400, Version::v1(), MimeTypes::MIME_APPLICATION_JSON_NO_META, false],
            [401, Version::v2(), MimeTypes::MIME_APPLICATION_JSON_NO_META, true],
            [402, Version::v3(), MimeTypes::MIME_APPLICATION_JSON_NO_META, false],

            [500, Version::v1(), MimeTypes::MIME_APPLICATION_JSON_FULL_META, false],
            [501, Version::v2(), MimeTypes::MIME_APPLICATION_JSON_FULL_META, true],
            [502, Version::v3(), MimeTypes::MIME_APPLICATION_JSON_FULL_META, false],

            [600, Version::v1(), MimeTypes::MIME_APPLICATION_JSON_VERBOSE, false],
            [601, Version::v2(), MimeTypes::MIME_APPLICATION_JSON_VERBOSE, true],
            [602, Version::v3(), MimeTypes::MIME_APPLICATION_JSON_VERBOSE, true], //Notice this is a special case
        ];
    }

    public function testWriteEmptyODataEntry()
    {
        $entry                  = new ODataEntry();
        $entry->resourceSetName = 'Foobars';

        $foo = new JsonODataV2Writer(PHP_EOL, true, 'http://localhost/odata.svc');

        $actual   = $foo->write($entry)->getOutput();
        $expected = '"__metadata":{' . PHP_EOL . PHP_EOL . '        }';
        $this->assertTrue(false !== strpos($actual, $expected));
    }

    public function testWriteEmptyODataFeed()
    {
        $feed                  = new ODataFeed();
        $feed->id              = 'http://localhost/odata.svc/feedID';
        $feed->setTitle(new ODataTitle('title'));
        $feed->setSelfLink(new ODataLink(
            ODataConstants::ATOM_SELF_RELATION_ATTRIBUTE_VALUE,
            'Feed Title',
            null,
            'feedID'
        ));

        $foo      = new JsonODataV2Writer(PHP_EOL, true, 'http://localhost/odata.svc');
        $expected = '"d":{' . PHP_EOL . '        "results":[' . PHP_EOL . PHP_EOL . '        ]';
        $actual   = $foo->write($feed)->getOutput();
        $this->assertTrue(false !== strpos($actual, $expected));
    }
}
