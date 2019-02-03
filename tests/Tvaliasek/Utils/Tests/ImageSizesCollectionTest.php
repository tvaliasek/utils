<?php

use Tvaliasek\Utils\ImageSizesCollection;
use PHPUnit\Framework\TestCase;
use Tvaliasek\Utils\Image;
use Tvaliasek\Utils\ImageSize;

class ImageSizesCollectionTest extends TestCase
{
    const TEST_NAME1 = 'test';
    const TEST_NAME2 = 'test2';

    public static $testArray1 = [
        Image::SIZES_NAME => self::TEST_NAME1,
        Image::SIZES_WIDTH => 800,
        Image::SIZES_HEIGHT => 600,
        Image::SIZES_METHOD => Image::RESIZE_METHOD_CONTAIN
    ];

    public static $testArray2 = [
        Image::SIZES_NAME => self::TEST_NAME2,
        Image::SIZES_WIDTH => 640,
        Image::SIZES_HEIGHT => 480,
        Image::SIZES_METHOD => Image::RESIZE_METHOD_COVER
    ];

    public function testCanBeConstructed()
    {
        $sizes = [
            ImageSize::fromArray(self::$testArray1),
            ImageSize::fromArray(self::$testArray2)
        ];
        $collection1 = new ImageSizesCollection(...$sizes);
        $collection2 = new ImageSizesCollection(
            ImageSize::fromArray(self::$testArray1),
            ImageSize::fromArray(self::$testArray2)
        );
        $this->assertInstanceOf(ImageSizesCollection::class, $collection1);
        $this->assertInstanceOf(ImageSizesCollection::class, $collection2);
        return $collection1;
    }

    /**
     * @depends testCanBeConstructed
     * @param ImageSizesCollection $collection
     */
    public function testGetNames(ImageSizesCollection $collection)
    {
        $actual = $collection->getNames();
        $expected = [
            self::TEST_NAME1,
            self::TEST_NAME2
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * @depends testCanBeConstructed
     * @param ImageSizesCollection $collection
     */
    public function testIsEmpty(ImageSizesCollection $collection)
    {
        $this->assertTrue(!$collection->isEmpty());
    }

    /**
     * @depends testCanBeConstructed
     * @param ImageSizesCollection $collection
     */
    public function testGetImageSize(ImageSizesCollection $collection)
    {
        $this->assertEquals(self::$testArray1, $collection->getImageSize(self::TEST_NAME1)->toArray());
    }

    /**
     * @depends testCanBeConstructed
     * @param ImageSizesCollection $collection
     */
    public function testToArray(ImageSizesCollection $collection)
    {
        $expect = [
          self::TEST_NAME1 => self::$testArray1,
          self::TEST_NAME2 => self::$testArray2
        ];
        $this->assertEquals($expect, $collection->toArray());
    }

    /**
     * @depends testCanBeConstructed
     * @param ImageSizesCollection $collection
     */
    public function testCount(ImageSizesCollection $collection)
    {
        $this->assertTrue($collection->count() === 2);
    }

    /**
     * @depends testCanBeConstructed
     * @param ImageSizesCollection $collection
     */
    public function testJsonSerialize(ImageSizesCollection $collection)
    {
        $expect = [
            self::TEST_NAME1 => self::$testArray1,
            self::TEST_NAME2 => self::$testArray2
        ];
        $this->assertEquals($expect, $collection->jsonSerialize());
        $this->assertTrue(json_encode($collection->jsonSerialize()) !== false);
    }

    public function testFromArray()
    {
        $input = [
            self::$testArray1,
            self::$testArray2
        ];
        $collection = ImageSizesCollection::fromArray($input);
        $this->assertInstanceOf(ImageSizesCollection::class, $collection);
    }

    public function testGetDefault()
    {
        $sizes = [];
        foreach (Image::$defaultImageSizes as $size) {
            $sizes[] = ImageSize::fromArray($size);
        }
        $expected = new ImageSizesCollection(...$sizes);
        $actual = ImageSizesCollection::getDefault();
        $this->assertEquals($expected, $actual);
    }

    public function testCopy()
    {
        $input = [
            self::$testArray1,
            self::$testArray2
        ];
        $collection = ImageSizesCollection::fromArray($input);
        $collection2 = $collection->copy();
        $this->assertEquals($collection, $collection2);
    }

    public function testClear()
    {
        $input = [
            self::$testArray1,
            self::$testArray2
        ];
        $collection = ImageSizesCollection::fromArray($input);
        $collection->clear();
        $this->assertTrue($collection->count() === 0);
    }

    /**
     * @depends testCanBeConstructed
     * @param ImageSizesCollection $collection
     * @return ImageSizesCollection
     */
    public function testKey(ImageSizesCollection $collection) : ImageSizesCollection
    {
        $this->assertTrue($collection->key() === self::TEST_NAME1);
        return $collection;
    }

    /**
     * @depends testKey
     * @param ImageSizesCollection $collection
     * @return ImageSizesCollection
     */
    public function testNext(ImageSizesCollection $collection)
    {
        $collection->next();
        $this->assertTrue($collection->key() === self::TEST_NAME2);
        return $collection;
    }

    /**
     * @depends testNext
     * @param ImageSizesCollection $collection
     * @return ImageSizesCollection
     */
    public function testCurrent(ImageSizesCollection $collection)
    {
        $expected = ImageSize::fromArray(self::$testArray2);
        $this->assertEquals($expected, $collection->current());
        return $collection;
    }

    /**
     * @depends testCurrent
     * @param ImageSizesCollection $collection
     * @return ImageSizesCollection
     */
    public function testRewind(ImageSizesCollection $collection)
    {
        $this->assertTrue($collection->key() === self::TEST_NAME2);
        $collection->rewind();
        $this->assertTrue($collection->key() === self::TEST_NAME1);
        return $collection;
    }

    /**
     * @depends testRewind
     * @param ImageSizesCollection $collection
     */
    public function testValid(ImageSizesCollection $collection)
    {
        $this->assertTrue($collection->valid());
        $collection->next();
        $this->assertTrue($collection->valid());
        $collection->next();
        $this->assertTrue(!$collection->valid());
    }
}
