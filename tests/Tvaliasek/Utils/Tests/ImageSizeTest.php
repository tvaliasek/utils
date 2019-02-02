<?php

namespace Tvaliasek\Utils\Tests;

use PHPUnit\Framework\TestCase;
use Tvaliasek\Utils\Image;
use Tvaliasek\Utils\ImageSize;

class ImageSizeTest extends TestCase
{
    public static $testArray = [
        Image::SIZES_NAME => 'test',
        Image::SIZES_WIDTH => 800,
        Image::SIZES_HEIGHT => 600,
        Image::SIZES_METHOD => Image::RESIZE_METHOD_CONTAIN
    ];

    /**
     * @covers ::fromArray
     * @return ImageSize
     */
    public function testCanBeCreatedFromArray()
    {
        $size = ImageSize::fromArray(self::$testArray);
        $this->assertInstanceOf(ImageSize::class, $size);
        return $size;
    }

    /**
     * @depends testCanBeCreatedFromArray
     * @param ImageSize $size
     */
    public function testGetResizeMethod(ImageSize $size)
    {
        $this->assertTrue(($size->getResizeMethod() === Image::RESIZE_METHOD_CONTAIN));
    }

    /**
     * @depends testCanBeCreatedFromArray
     * @param ImageSize $size
     */
    public function testGetName(ImageSize $size)
    {
        $this->assertTrue(($size->getName() === 'test'));
    }

    /**
     * @depends testCanBeCreatedFromArray
     * @param ImageSize $size
     */
    public function testGetHeight(ImageSize $size)
    {
        $this->assertTrue(($size->getHeight() === 600));
    }

    /**
     * @depends testCanBeCreatedFromArray
     * @param ImageSize $size
     */
    public function testGetWidth(ImageSize $size)
    {
        $this->assertTrue(($size->getWidth() === 800));
    }

    /**
     * @covers __construct
     */
    public function testCanBeConstructed()
    {
        $size = new ImageSize(
            self::$testArray[Image::SIZES_NAME],
            self::$testArray[Image::SIZES_WIDTH],
            self::$testArray[Image::SIZES_HEIGHT],
            self::$testArray[Image::SIZES_METHOD]
        );
        $this->assertInstanceOf(ImageSize::class, $size);
        $size = new ImageSize(
            self::$testArray[Image::SIZES_NAME],
            self::$testArray[Image::SIZES_WIDTH],
            self::$testArray[Image::SIZES_HEIGHT]
        );
        $this->assertInstanceOf(ImageSize::class, $size);
    }

    public function testCannotBeConstructed()
    {
        try {
            $size = new ImageSize('fail', 123, 456, 'not-existing');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }

        try {
            $size = new ImageSize('fail', -1, 456, Image::RESIZE_METHOD_CONTAIN);
        } catch (\Exception $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }

        try {
            $size = new ImageSize('fail', 15, -10, Image::RESIZE_METHOD_CONTAIN);
        } catch (\Exception $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }

        try {
            $size = new ImageSize(' ', 15, 10, Image::RESIZE_METHOD_CONTAIN);
        } catch (\Exception $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }

        try {
            $size = new ImageSize('', 15, 10, Image::RESIZE_METHOD_CONTAIN);
        } catch (\Exception $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }
    }

    public function testCannotBeCreatedFromBadArray()
    {
        try {
            $size = ImageSize::fromArray([]);
        } catch (\Exception $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }
    }

    /**
     * @depends testCanBeCreatedFromArray
     * @param ImageSize $size
     */
    public function testToArray(ImageSize $size)
    {
        $this->assertEqualsCanonicalizing($size->toArray(), self::$testArray);
    }
}
