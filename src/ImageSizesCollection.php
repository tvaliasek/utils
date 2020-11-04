<?php

namespace Tvaliasek\Utils;


use Ds\Collection;
use Exception;
use Traversable;

/**
 * Class ImageSizesCollection
 * @package Tvaliasek\Utils
 */
class ImageSizesCollection implements Collection
{
    /**
     * @var array ImageSize[]
     */
    private $sizes = [];
    private $pointer = 0;
    private $keys = [];

    /**
     * ImageSizesCollection constructor.
     * @param ImageSize ...$sizes
     */
    public function __construct(
        ImageSize ...$sizes
    )
    {
        foreach ($sizes as $size) {
            $this->sizes[$size->getName()] = $size;
            $this->keys[] = $size->getName();
        }
    }

    /**
     * @param array $sizes
     * @return ImageSizesCollection
     * @throws \InvalidArgumentException
     */
    public static function fromArray(array $sizes): ImageSizesCollection
    {
        $tmp = [];
        foreach ($sizes as $item) {
            $tmp[] = ImageSize::fromArray($item);
        }
        return new ImageSizesCollection(...$tmp);
    }

    /**
     * Removes all values from the collection.
     */
    function clear(): void
    {
        $this->sizes = [];
    }

    /**
     * Returns the size of the collection.
     *
     * @return int
     */
    function count(): int
    {
        return count($this->sizes);
    }

    /**
     * Returns a shallow copy of the collection.
     *
     * @return Collection a copy of the collection.
     */
    function copy(): Collection
    {
        $tmp = [];
        foreach ($this->sizes as $size) {
            $tmp[] = $size;
        }
        return new ImageSizesCollection(...$tmp);
    }

    /**
     * Returns whether the collection is empty.
     *
     * This should be equivalent to a count of zero, but is not required.
     * Implementations should define what empty means in their own context.
     *
     * @return bool
     */
    function isEmpty(): bool
    {
        return empty($this->sizes);
    }

    /**
     * Returns an array representation of the collection.
     *
     * @return array
     */
    function toArray(): array
    {
        $tmp = [];
        foreach ($this->sizes as $size) {
            $tmp[$size->getName()] = $size->toArray();
        }
        return $tmp;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param string $name
     * @return ImageSize|null
     */
    public function getImageSize(string $name): ?ImageSize
    {
        if (key_exists($name, $this->sizes)) {
            return $this->sizes[$name];
        }
        return null;
    }

    public function current(): ImageSize
    {
        return $this->getImageSize($this->keys[$this->pointer]);
    }

    public function next(): void
    {
        $this->pointer++;
    }

    public function key(): ?string
    {
        return (key_exists($this->pointer, $this->keys)) ? $this->keys[$this->pointer] : null;
    }

    public function valid(): bool
    {
        return key_exists($this->pointer, $this->keys);
    }

    public function rewind(): void
    {
        $this->pointer = 0;
    }

    public function getNames() : array
    {
        $names = [];
        foreach ($this->sizes as $size) {
            $names[] = $size->getName();
        }
        return $names;
    }

    public static function getDefault() : ImageSizesCollection
    {
        return self::fromArray(Image::$defaultImageSizes);
    }

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->sizes);
    }
}