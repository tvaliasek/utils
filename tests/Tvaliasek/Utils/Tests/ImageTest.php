<?php

namespace Tvaliasek\Utils\Tests;

use Tvaliasek\Utils\Image;
use PHPUnit\Framework\TestCase;
use Tvaliasek\Utils\ImageSizesCollection;
use Tvaliasek\Utils\Tooler;

class ImageTest extends TestCase
{
    protected $jpg = __DIR__ . '/assets/test.jpg';
    protected $png = __DIR__ . '/assets/test.png';
    protected $bmp = __DIR__ . '/assets/test.bmp';
    protected $jpgCopy = __DIR__ . '/assets/copy.jpg';
    protected $pngCopy = __DIR__ . '/assets/copy.png';

    private function getGeometry(string $imagePath)
    {
        $image = new \Imagick($imagePath);
        return $image->getImageGeometry();
    }

    private function clearImages()
    {
        if (file_exists($this->jpgCopy)) {
            unlink($this->jpgCopy);
        }
        if (file_exists($this->pngCopy)) {
            unlink($this->pngCopy);
        }
    }

    private function copyImages()
    {
        $this->clearImages();
        copy($this->jpg, $this->jpgCopy);
        copy($this->png, $this->pngCopy);
    }

    /**
     * @covers ::__construct
     * @throws \ImagickException
     */
    public function testCanBeCreated()
    {
        $jpg = new Image($this->jpg);
        $png = new Image($this->png);
        $exception = null;
        try {
            $bmp = new Image($this->bmp);
        } catch (\InvalidArgumentException $e) {
            $exception = $e;
        }
        $this->assertInstanceOf(Image::class, $jpg);
        $this->assertInstanceOf(Image::class, $png);
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
    }

    public function testResizeOverwrite()
    {
        $this->copyImages();
        $jpg = new Image($this->jpgCopy);
        $png = new Image($this->pngCopy);
        $jpg->resizeOverwrite(640, 480);
        $png->resizeOverwrite(640, 480);
        $jpgGeometry = $this->getGeometry($this->jpgCopy);
        $pngGeometry = $this->getGeometry($this->pngCopy);
        $this->assertTrue($jpgGeometry['width'] === 640);
        $this->assertTrue($jpgGeometry['height'] === 480);
        $this->assertTrue($pngGeometry['width'] === 640);
        $this->assertTrue($pngGeometry['height'] === 480);
        $this->clearImages();
    }

    public function testProcessResizes()
    {
        $this->copyImages();
        $jpg = new Image($this->jpgCopy);
        $png = new Image($this->pngCopy);
        $sizes = ImageSizesCollection::getDefault();
        $jpg->processResizes();
        $png->processResizes();
        $jpg->processResizes(null, __DIR__ . '/alternative', 'baseName');
        $resizedFolder = __DIR__ . '/assets/resized';
        $this->assertDirectoryExists($resizedFolder);
        $this->assertDirectoryExists(__DIR__ . '/alternative/resized');

        foreach ($sizes as $size) {
            $this->assertDirectoryExists(__DIR__ . '/alternative/resized/' . $size->name);
            $this->assertDirectoryExists($resizedFolder . '/' . $size->name);
            $this->assertFileExists(__DIR__ . '/alternative/resized/' . $size->name . '/baseName.jpg');
            $this->assertFileExists($resizedFolder . '/' . $size->name . '/copy.jpg');
            $this->assertFileExists($resizedFolder . '/' . $size->name . '/copy.png');
            $alternativeGeometry = $this->getGeometry(__DIR__ . '/alternative/resized/' . $size->name . '/baseName.jpg');
            $jpgGeometry = $this->getGeometry($resizedFolder . '/' . $size->name . '/copy.jpg');
            $pngGeometry = $this->getGeometry($resizedFolder . '/' . $size->name . '/copy.png');
            $this->assertTrue($alternativeGeometry['width'] <= $size->width);
            $this->assertTrue($alternativeGeometry['height'] <= $size->height);
            $this->assertTrue($jpgGeometry['width'] <= $size->width);
            $this->assertTrue($jpgGeometry['height'] <= $size->height);
            $this->assertTrue($pngGeometry['width'] <= $size->width);
            $this->assertTrue($pngGeometry['height'] <= $size->height);
        }
        Tooler::unlinkIfExists(__DIR__ . '/alternative');
    }

    /**
     * @depends testProcessResizes
     */
    public function testDeleteResizes()
    {
        $resizedFolder = __DIR__ . '/assets/resized';
        $sizes = ImageSizesCollection::getDefault();
        $jpg = new Image($this->jpgCopy);
        $png = new Image($this->pngCopy);
        $jpg->deleteResizes(true);
        $this->assertFileNotExists($this->jpgCopy);
        foreach ($sizes as $size) {
            $this->assertFileNotExists($resizedFolder . '/' . $size->name . '/copy.jpg');
            $this->assertDirectoryExists($resizedFolder . '/' . $size->name);
        }
        $png->deleteResizes(true);
        $this->assertFileNotExists($this->pngCopy);
        $this->assertDirectoryNotExists($resizedFolder);
    }

    public function testConvertToJpg()
    {
        $this->copyImages();
        $image = new Image($this->pngCopy);
        $tempJpg = $image->convertToJpg(70);
        $this->assertFileExists($tempJpg);
        copy($tempJpg, __DIR__ . '/assets/converted.jpg');
        Tooler::unlinkIfExists($tempJpg);
        $jpg = new Image(__DIR__ . '/assets/converted.jpg');
        $this->assertTrue(in_array($jpg->getMimeType(), ['image/jpeg', 'image/jpg']));
        Tooler::unlinkIfExists(__DIR__ . '/assets/converted.jpg');
        $this->clearImages();
    }

    public function testRotate()
    {
        $this->copyImages();
        $jpgGeometry = $this->getGeometry($this->jpgCopy);
        $jpg = new Image($this->jpgCopy);
        $jpg->rotate(90);
        $jpg->writeImage(__DIR__ . '/assets/rotated.jpg');
        $jpgRotatedGeometry = $this->getGeometry(__DIR__ . '/assets/rotated.jpg');
        $this->assertTrue($jpgGeometry['width'] === $jpgRotatedGeometry['height']);
        $pngGeometry = $this->getGeometry($this->pngCopy);
        $png = new Image($this->pngCopy);
        $png->rotate(90);
        $png->writeImage(__DIR__ . '/assets/rotated.png');
        $pngRotatedGeometry = $this->getGeometry(__DIR__ . '/assets/rotated.png');
        $this->assertTrue($pngGeometry['width'] === $pngRotatedGeometry['height']);
        Tooler::unlinkIfExists(__DIR__ . '/assets/rotated.png');
        Tooler::unlinkIfExists(__DIR__ . '/assets/rotated.jpg');
    }

    public function testFlip()
    {
        $this->copyImages();
        $jpg = new Image($this->jpgCopy);
        $this->assertTrue($jpg->flipVertically());
        $this->assertTrue($jpg->flipHorizontally());
    }

    public function testCropAt()
    {
        $this->copyImages();
        $jpg = new Image($this->jpgCopy);
        $jpg->cropAt(568, 0, 1024, 200);
        $jpg->resizeOverwrite(800, 156);
        $newGeometry = $this->getGeometry($this->jpgCopy);
        $this->assertTrue(($newGeometry['width'] >= 799 && $newGeometry['width'] <= 800));
        $this->assertTrue(($newGeometry['height'] >= 155 && $newGeometry['height'] <= 156));
    }

    public static function tearDownAfterClass()
    {
        Tooler::unlinkIfExists(__DIR__ . '/assets/copy.png');
        Tooler::unlinkIfExists(__DIR__ . '/assets/copy.jpg');
        Tooler::unlinkIfExists(__DIR__ . '/assets/resized');
        Tooler::unlinkIfExists(__DIR__ . '/assets/rotated.jpg');
        Tooler::unlinkIfExists(__DIR__ . '/assets/rotated.png');
        Tooler::unlinkIfExists(__DIR__ . '/alternative');
    }
}
