<?php
/**
 * Created by PhpStorm.
 * User: tvaliasek
 * Date: 02.02.2019
 * Time: 11:06
 */

namespace Tvaliasek\Utils;

use Nette\SmartObject;

/**
 * Class ImageSize
 * Value object for holding image dimensions and resize method
 * @package Tvaliasek\Utils
 * @property-read int $width
 * @property-read int $height
 * @property-read string $name
 * @property-read string $resizeMethod
 */
class ImageSize
{
    use SmartObject;

    /**
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    private $height;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $resizeMethod;

    /**
     * ImageSize constructor.
     * @param string $name
     * @param int $width
     * @param int $height
     * @param string $resizeMethod
     */
    public function __construct(
        string $name,
        int $width,
        int $height,
        string $resizeMethod = Image::RESIZE_METHOD_CONTAIN
    )
    {
        if (!in_array($resizeMethod, [Image::RESIZE_METHOD_CONTAIN, Image::RESIZE_METHOD_COVER])) {
            throw new \InvalidArgumentException('Invalid value for resize method.');
        }
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('Invalid value for size name.');
        }
        if ($width < 0 || $height < 0) {
            throw new \InvalidArgumentException('Width and height cannot be negative.');
        }
        $this->name = $name;
        $this->width = $width;
        $this->height = $height;
        $this->resizeMethod = $resizeMethod;
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @return string
     */
    public function getResizeMethod(): string
    {
        return $this->resizeMethod;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param array $array
     * @return ImageSize
     * @throws \InvalidArgumentException
     */
    public static function fromArray(array $array): ImageSize
    {
        if (
            key_exists(Image::SIZES_NAME, $array) &&
            key_exists(Image::SIZES_WIDTH, $array) &&
            key_exists(Image::SIZES_HEIGHT, $array)
        ) {
            return (key_exists(Image::SIZES_METHOD, $array))
                ? new ImageSize($array[Image::SIZES_NAME], $array[Image::SIZES_WIDTH], $array[Image::SIZES_HEIGHT], $array[Image::SIZES_METHOD])
                : new ImageSize($array[Image::SIZES_NAME], $array[Image::SIZES_WIDTH], $array[Image::SIZES_HEIGHT]);
        } else {
            throw new \InvalidArgumentException('Invalid structure of input array');
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            Image::SIZES_NAME => $this->getName(),
            Image::SIZES_WIDTH => $this->getWidth(),
            Image::SIZES_HEIGHT => $this->getHeight(),
            Image::SIZES_METHOD => $this->getResizeMethod()
        ];
    }
}