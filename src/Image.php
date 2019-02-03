<?php

namespace Tvaliasek\Utils;

use Nette\Utils\Strings;

/**
 * Class Image
 * Image manipulation using Imagick with disk cache
 * @package Tvaliasek\Utils
 */
class Image
{
    const RESIZE_METHOD_CONTAIN = 'contain';
    const RESIZE_METHOD_COVER = 'cover';

    /**
     * default image sizes for processing responsive variants of image
     * @var array
     */
    public static $defaultImageSizes = [
        'lg' => [
            'name' => 'lg',
            'w' => 1280,
            'h' => 720,
            'method' => 'contain'
        ],
        'thumb' => [
            'name' => 'thumb',
            'w' => 175,
            'h' => 175,
            'method' => 'cover'
        ]
    ];

    /**
     * Default compression quality level
     */
    const DEFAULT_QUALITY = 75;

    /**
     * Default thumbnail size name
     */
    const THUMBNAIL_SIZE_NAME = 'thumb';

    /**
     * Key of width in sizes array
     */
    const SIZES_WIDTH = 'w';

    /**
     * Key of height in sizes array
     */
    const SIZES_HEIGHT = 'h';

    const SIZES_METHOD = 'method';
    const SIZES_NAME = 'name';

    /**
     * main image
     * @var mixed
     */
    private $image;

    /**
     * Path to image file
     * @var string
     */
    private $imagePath;

    /**
     * Width of image
     * @var int
     */
    private $width;

    /**
     * Height of image
     * @var int
     */
    private $height;

    /**
     * Detected extension of image file
     * @var string
     */
    private $extension;

    /**
     * Original filename of image
     * @var string
     */
    private $filename;

    /**
     * Sanitized filename of image
     * @var string
     */
    private $sanitizedFilename;

    /**
     * Enable thumbs crop
     * @var bool
     */
    private $cropThumbs = true;

    /**
     * @var string
     */
    private $mimeType;

    /**
     * Image constructor.
     * @param string $filepath
     * @throws \ImagickException
     * @throws \InvalidArgumentException
     */
    public function __construct(
        string $filepath
    )
    {
        if (file_exists($filepath)) {
            $this->imagePath = $filepath;
            $mimeType = $this->mimeType = mime_content_type($this->imagePath);
            switch ($mimeType) {
                case 'image/jpeg':
                case 'image/jpg':
                    $this->extension = '.jpg';
                    break;
                case 'image/png':
                    $this->extension = '.png';
                    break;
                default:
                    throw new \InvalidArgumentException('Unsupported image mime type, only jpg and png allowed.');
            }
            $this->filename = basename($this->imagePath, $this->extension);
            $this->sanitizedFilename = Strings::webalize($this->filename);

            $this->image = new \Imagick($filepath);
            $this->image->setresourcelimit(\Imagick::RESOURCETYPE_MEMORY, (64 * 1024 * 1024));
            $geometry = $this->image->getimagegeometry();
            $this->width = $geometry['width'];
            $this->height = $geometry['height'];
        } else {
            throw new \InvalidArgumentException('File not found.');
        }
    }

    /**
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Get transparent pixel
     * @return \ImagickPixel
     */
    public static function getTransparent(): \ImagickPixel
    {
        return new \ImagickPixel('#00000000');
    }

    /**
     * Rotates image by orientation in EXIF data
     * @return Image
     */
    public function rotateByExif(): self
    {
        $ex = @exif_read_data($this->imagePath, 'EXIF');
        if (!empty($ex['Orientation'])) {
            switch ($ex['Orientation']) {
                case 8:
                    $this->image->rotateimage(self::getTransparent(), 270);
                    break;
                case 3:
                    $this->image->rotateimage(self::getTransparent(), 180);
                    break;
                case 6:
                    $this->image->rotateimage(self::getTransparent(), 90);
                    break;
            }
        }
        return $this;
    }

    /**
     * Rotates image by number of degrees
     * @param int $degrees
     * @return bool
     */
    public function rotate(int $degrees): bool
    {
        return $this->image->rotateimage(self::getTransparent(), $degrees);
    }

    /**
     * Resize images by longest side
     * @param int $width
     * @param int $height
     * @return Image
     */
    private function resizeByLongest(int $width, int $height): self
    {
        if ($this->width < $this->height) {
            $this->image->resizeimage($height, $width, \Imagick::FILTER_UNDEFINED, 0.7, true);
        } else {
            $this->image->resizeimage($width, $height, \Imagick::FILTER_UNDEFINED, 0.7, true);
        }
        return $this;
    }

    /**
     * Create and save responsive variants of image
     * @param ImageSizesCollection $imageSizes
     * @param string $targetFolder
     * @param string $targetBaseName
     * @throws \ImagickException
     */
    public function processResizes(
        ImageSizesCollection $imageSizes = null,
        string $targetFolder = null,
        string $targetBaseName = null
    ): void
    {
        $sizes = ($imageSizes !== null)
            ? $imageSizes
            : ImageSizesCollection::getDefault();
        $folderPath = ($targetFolder !== null)
            ? $targetFolder
            : str_ireplace(basename($this->imagePath), '', $this->imagePath);
        $filename = ($targetBaseName !== null)
            ? $targetBaseName
            : $this->sanitizedFilename;
        $this->rotateByExif();
        $this->processResizesImagick($sizes, $folderPath, $filename);
    }

    /**
     * @param ImageSizesCollection $sizes
     * @param string $folderPath
     * @param string $filename
     * @throws \ImagickException
     */
    private function processResizesImagick(
        ImageSizesCollection $sizes,
        string $folderPath,
        string $filename
    ): void
    {
        $backup = clone $this->image;

        foreach ($sizes as $size) {
            $this->image = clone $backup;
            $this->image->setcompressionquality(self::DEFAULT_QUALITY);
            if ($this->mimeType !== 'image/png') {
                $this->image->setInterlaceScheme(\Imagick::INTERLACE_PLANE);
            }
            if (!is_dir($folderPath . '/resized/' . $size->name)) {
                Tooler::createFolder($folderPath . '/resized/' . $size->name);
            }
            $filepath = $folderPath . '/resized/' . $size->name . '/' . $filename . $this->extension;
            Tooler::unlinkIfExists($filepath);
            if (
                ($size->name == self::THUMBNAIL_SIZE_NAME && $this->cropThumbs) ||
                $size->resizeMethod == self::RESIZE_METHOD_COVER
            ) {
                $this->image->cropthumbnailimage($size->width, $size->height);
            } else {
                $this->resizeByLongest($size->width, $size->height);
            }
            $this->image->writeimage($filepath);
            $this->image = clone $backup;
        }
        unset($backup);
    }

    /**
     * @param string $filename
     * @param int $width
     * @param int $height
     * @param string $method
     * @return Image
     * @throws \ImagickException
     */
    public function snapThumbImagick(
        string $filename,
        int $width,
        int $height,
        string $method = self::RESIZE_METHOD_CONTAIN
    ): Image
    {
        $this->rotateByExif();
        $backup = clone $this->image;
        $this->image->setcompressionquality(self::DEFAULT_QUALITY);
        $folderPath = str_ireplace(basename($this->imagePath), '', $this->imagePath);
        $filepath = preg_replace(
            '/\/{2,}/',
            '/',
            ($folderPath . '/' . str_ireplace($this->extension, '', $filename) . $this->extension)
        );
        Tooler::unlinkIfExists($filepath);
        if ($method == self::RESIZE_METHOD_COVER) {
            $this->image->cropthumbnailimage($width, $height);
        } else {
            $this->resizeByLongest($width, $height);
        }
        if ($this->mimeType !== 'image/png') {
            $this->image->setInterlaceScheme(\Imagick::INTERLACE_PLANE);
        }
        $this->image->writeimage($filepath);
        $this->image = clone $backup;
        unset($backup);
        return new Image($filepath);
    }

    /**
     * @param string $target
     * @param int $quality
     */
    public function writeImage(string $target, int $quality = self::DEFAULT_QUALITY): void
    {
        Tooler::unlinkIfExists($target);
        if ($this->getMimeType() !== 'image/png') {
            $this->image->setCompressionQuality($quality);
            $this->image->setInterlaceScheme(\Imagick::INTERLACE_PLANE);
        }
        $this->image->writeImage($target);
    }

    /**
     * @param bool $deleteOriginal
     * @param ImageSizesCollection|null $imageSizes
     * @param string|null $targetFolder
     * @param string|null $targetBaseName
     */
    public function deleteResizes(
        bool $deleteOriginal = false,
        ImageSizesCollection $imageSizes = null,
        string $targetFolder = null,
        string $targetBaseName = null
    ): void
    {
        $sizes = ($imageSizes !== null)
            ? $imageSizes
            : ImageSizesCollection::getDefault();
        $folderPath = ($targetFolder !== null)
            ? $targetFolder
            : str_ireplace(basename($this->imagePath), '', $this->imagePath);
        $filename = ($targetBaseName !== null)
            ? $targetBaseName
            : $this->sanitizedFilename;
        foreach ($sizes as $size) {
            $filepath = $folderPath . '/resized/' . $size->name . '/' . $filename . $this->extension;
            Tooler::unlinkIfExists($filepath);
            if (Tooler::isEmptyFolder($folderPath . '/resized/' . $size->name)) {
                Tooler::unlinkIfExists($folderPath . '/resized/' . $size->name);
            }
        }
        if (Tooler::isEmptyFolder($folderPath . '/resized')) {
            Tooler::unlinkIfExists($folderPath . '/resized');
        }
        if ($deleteOriginal) {
            $this->image->destroy();
            Tooler::unlinkIfExists($this->imagePath);
        }
    }

    /**
     * Snaps first page of pdf and returns it as jpeg image blob
     * @param string $pdfPath
     * @return string
     * @throws \ImagickException
     */
    public static function snapPDFThumbBlob(string $pdfPath): string
    {
        $img = new \Imagick();
        $img->readImage($pdfPath)[0];
        $img->setImageFormat('jpg');
        $img->setImageCompression(\Imagick::COMPRESSION_JPEG);
        $img->setImageCompressionQuality(100);
        return $img->getImageBlob();
    }

    /**
     * Resizes image by longest side and overwrites original
     * @param int $width
     * @param int $height
     */
    public function resizeOverwrite(
        int $width,
        int $height
    ): void
    {
        $this->resizeByLongest($width, $height);
        Tooler::unlinkIfExists($this->imagePath);
        if ($this->mimeType !== 'image/png') {
            $this->image->setInterlaceScheme(\Imagick::INTERLACE_PLANE);
        }
        $this->image->writeimage($this->imagePath);
    }

    /**
     * Enable | Disable thumbs cropping
     * @param bool $state
     * @return Image
     */
    public function setThumbsCrop(bool $state): self
    {
        $this->cropThumbs = $state === true;
        return $this;
    }

    /**
     * Apply watermark with opacity to center of image
     * @param string $watermarkPNGFile
     * @param float $opacity
     * @return Image
     * @throws \ImagickException
     * @throws \InvalidArgumentException
     */
    public function applyWatermark(
        string $watermarkPNGFile,
        float $opacity = 0.3
    ): Image
    {
        if (!file_exists($watermarkPNGFile)) {
            throw new \InvalidArgumentException('Watermark file not found.');
        }
        if (!Tooler::validateMimeType($watermarkPNGFile, 'image/png')) {
            throw new \InvalidArgumentException(
                'Invalid watermark image format. image/png required, ' .
                mime_content_type($watermarkPNGFile) . ' found.'
            );
        }
        $wm = new \Imagick($watermarkPNGFile);
        $wm->setresourcelimit(\Imagick::RESOURCETYPE_MEMORY, 64 * 1024 * 1024);
        $wm->resizeimage(ceil(($this->width / 2)), ceil(($this->height / 2)), \Imagick::FILTER_UNDEFINED, 0.7, true);
        $geometry = $wm->getimagegeometry();
        $wmWidth = $geometry['width'];
        $wmHeight = $geometry['height'];
        $x = ceil(($this->width / 2) - ($wmWidth / 2));
        $y = ceil(($this->height / 2) - ($wmHeight / 2));
        $wm->setImageOpacity($opacity);
        $this->image->compositeimage($wm, \Imagick::COMPOSITE_OVER, $x, $y);
        $this->image->mergeimagelayers(\Imagick::LAYERMETHOD_FLATTEN);
        return $this;
    }

    /**
     * @param \Imagick $image
     * @return \Imagick
     * @throws \ImagickException
     */
    public function convertImagickToJpg(\Imagick $image)
    {
        $white = new \Imagick();
        $geometry = $image->getImageGeometry();
        $white->newImage($geometry['width'], $geometry['height'], 'white');
        $white->compositeImage($image, \Imagick::COMPOSITE_OVER, 0, 0);
        $white->setImageFormat('jpg');
        return $white;
    }

    /**
     * @param int $quality
     * @return bool|string
     * @throws \ImagickException
     */
    public function convertToJpg(int $quality)
    {
        $image = $this->convertImagickToJpg($this->image);
        $image->setCompressionQuality($quality);
        $tmp = tempnam(sys_get_temp_dir(), 'image-');
        $image->writeImage($tmp);
        return $tmp;
    }
}
