<?php

namespace App\Tests\App\Service;

use App\Service\Collection;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    public function testToArray(): void
    {
        $source = [
            'a',
            'b' => [
                'c' => 'd',
            ],
        ];

        $collection = new Collection($source);

        $this->assertEquals($source, $collection->toArray());
    }

    public function testPluckWithEmptyData(): void
    {
        $this->assertEquals(new Collection(), (new Collection())->pluck('name'));
    }

    public function testPluckWithArray(): void
    {
        $source = [
            'a',
            'b' => [
                'name' => 'd',
            ],
        ];

        $collection = new Collection($source);

        $this->assertEquals(['d'], $collection->pluck('name')->toArray());
    }

    public function testPluckWithPublicMethod(): void
    {
        $testObjectClass = new class {
            private $name = 'chindit';
            public function name(): string
            {
                return $this->name;
            }
        };
        $testObject = new $testObjectClass();

        $collection = new Collection([
            'a',
            'b' => [
                'name' => 'd',
            ],
            'd' => $testObject
        ]);

        $this->assertEquals(['d', 'chindit'], $collection->pluck('name')->toArray());
    }

    public function testPluckWithPublicGetter(): void
    {
        $testObjectClass = new class {
            private $name = 'chindit';
            public function getName(): string
            {
                return $this->name;
            }
        };
        $testObject = new $testObjectClass();

        $collection = new Collection([
            'a',
            'b' => [
                'name' => 'd',
            ],
            'd' => $testObject
        ]);

        $this->assertEquals(['d', 'chindit'], $collection->pluck('name')->toArray());
    }

    public function testPluckWithPublicAttribute(): void
    {
        $testObjectClass = new class {
            public $name = 'chindit';
        };
        $testObject = new $testObjectClass();

        $collection = new Collection([
            'a',
            'b' => [
                'name' => 'd',
            ],
            'd' => $testObject
        ]);

        $this->assertEquals(['d', 'chindit'], $collection->pluck('name')->toArray());
    }

    public function testPush(): void
    {
        $collection = new Collection(['apple', 'pear']);

        $collection->push('orange');

        $this->assertEquals(['apple', 'pear', 'orange'], $collection->toArray());
    }

    public function testContainsWithNotFoundData(): void
    {
        $collection = new Collection(['apple', 'pear', 'orange']);

        $this->assertFalse($collection->contains('banana'));
    }

    public function testContainsWithValidData(): void
    {
        $collection = new Collection(['apple', 'pear', 'orange']);

        $this->assertTrue($collection->contains('orange'));
    }

    public function testMapWithInvalidParam(): void
    {
        $collection = new Collection(['apple', 'pear', 'orange']);

        $this->assertEquals($collection, $collection->map('yeah'));
    }

    public function testMapWithCallback(): void
    {
        $sourceCollection = new Collection(['apple', 'pear', 'orange']);

        $this->assertEquals(['applee', 'peare', 'orangee'], $sourceCollection->map(function($item)
        {
            return $item . 'e';
        })
        ->toArray());
    }

    public function testFirstWithNoData(): void
    {
        $this->assertNull((new Collection())->first());
    }

    public function testFirst(): void
    {
        $collection = new Collection(['apple', 'pear', 'orange']);

        $this->assertEquals('apple', $collection->first());
    }

    public function testFlattenWithSingleLevelArray(): void
    {
        $collection = new Collection(['apple', 'pear', 'orange']);

        $this->assertEquals($collection, $collection->flatten());
    }

    public function testFlattenWithMultipleLevelArray(): void
    {
        $collection = new Collection(
            ['apple',
             'fruits' => [
                 'banana',
                'exotics' => [
                    'coco',
                    'mango',
                    'very_exotic' => [
                        'lychee',
                        'durian',
                    ],
                 ],
             ],
             'orange'
            ]
        );

        $expectedCollection = new Collection(['apple', 'banana', 'coco', 'mango', 'lychee', 'durian', 'orange']);

        $this->assertEquals($expectedCollection, $collection->flatten());
    }

    public function testFlattenWithLimitedDepth(): void
    {
        $collection = new Collection(
            ['apple',
             'fruits' => [
                 'banana',
                 'exotics' => [
                     'coco',
                     'mango',
                     'very_exotic' => [
                         'lychee',
                         'durian',
                     ],
                 ],
             ],
             'orange'
            ]
        );

        $expectedCollection = new Collection(['apple', 'banana', 'coco', 'mango', ['lychee', 'durian'], 'orange']);

        $this->assertEquals($expectedCollection, $collection->flatten(2));
    }

    public function testAllWithEmptyDataSet(): void
    {
        $this->assertEmpty((new Collection())->all());
    }

    public function testAll(): void
    {
        $collection = new Collection(
            [
                'a' => 1,
                'b' => 2,
                'c' => 3,
                'd' => ['e' => 4]
            ]
        );

        $this->assertEquals([1, 2, 3, ['e' => 4]], $collection->all());
    }

    public function testIterator(): void
    {
        $collection = new Collection(
            [
                'a' => 1,
                'b' => 2,
                'c' => 3,
                'd' => ['e' => 4]
            ]
        );

        $this->assertEquals(1, $collection->current());
        $collection->next();
        $this->assertEquals(2, $collection->current());
        $this->assertEquals('b', $collection->key());
        $collection->rewind();
        $this->assertEquals('a', $collection->key());
    }
}
