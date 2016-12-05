<?php

namespace Tvaliasek\Utils;

use Nette,
    Nette\Utils\Strings;

/**
 * Basic image manipulations with imagick lib support
 */

class Image extends Nette\Object {
    
    /**
     * default image sizes for processing responsive variants of image
     * @var array
     */
    public static $defaultImageSizes = array('lg'=>array('w'=>1280,'h'=>720), 
        'md'=>array('w'=>1024,'h'=>576),
        'sm'=>array('w'=>768,'h'=>432),
        'xs'=>array('w'=>512,'h'=>288),	
        'thumb'=>array('w'=>175,'h'=>175));

    /**
     * Default memory resouse limit for imagick in MB
     */
    const DEFAULT_RESOURCE_LIMIT = 112;
    /**
     * Default filesize limit in MB for usage of nette/image instead of imagick
     */
    const DEFAULT_GD_FILESIZE_LIMIT = 0;
    /**
     * Default compression quality level
     */
    const DEFAULT_QUALITY = 80;
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
     * Create instance of Image
     * @param string $filepath
     * @param int $memoryLimit
     * @param int $gdFilesizeLimit
     * @throws \Exception
     * @throws \Nette\FileNotFoundException
     */
    public function __construct($filepath, $memoryLimit = self::DEFAULT_RESOURCE_LIMIT, $gdFilesizeLimit = self::DEFAULT_GD_FILESIZE_LIMIT) {
        if(file_exists($filepath)){
            $filesize = filesize($filepath);
            $this->imagePath = $filepath;
            $mimeType = mime_content_type($this->imagePath);
           switch($mimeType){
                case 'image/jpeg':
                case 'image/jpg':
                    $this->extension = '.jpg';
                    break;
                case 'image/png':
                    $this->extension = '.png';
                    break;
                default:
                    throw new \Exception('Unsupported image mime type, only jpg and png allowed.');
            }
            $this->filename = basename($this->imagePath, $this->extension);
            $this->sanitizedFilename = Strings::webalize($this->filename);
            
            if($filesize > ($gdFilesizeLimit*1024*1024)){
                $this->image = new \Imagick($filepath);
                $this->image->setresourcelimit(\Imagick::RESOURCETYPE_MEMORY, $memoryLimit*1024*1024);
                $geometry = $this->image->getimagegeometry();
                $this->width = $geometry['width'];
                $this->height = $geometry['height'];
            } else {
                $image = ($mimeType=='image/png') ? imagecreatefrompng($filepath) : imagecreatefromjpeg($filepath);
                $this->image = new \Nette\Utils\Image($image);
                $this->width = $this->image->width;
                $this->height = $this->image->height;
            }
        } else {
            throw new \Nette\FileNotFoundException;
        }
    }
    
    /**
     * Get transparent pixel
     * @return \ImagickPixel
     */
    public static function getTransparent(){
        return new \ImagickPixel('#00000000');
    } 
    
    /**
     * Rotates image by orientation in EXIF data
     * @return \Tvaliasek\Utils\Image
     */
    public function rotateByExif() {
        $ex = @exif_read_data($this->imagePath, 'EXIF');
        if (!empty($ex['Orientation'])) {
            if($this->image instanceof \Nette\Utils\Image){
                switch ($ex['Orientation']) {
                    case 8:
                        $this->image->rotate(90, 0);
                        break;
                    case 3:
                        $this->image->rotate(180, 0);
                        break;
                    case 6:
                        $this->image->rotate(270, 0);
                        break;
                }
            } else {
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
        }
        return $this;
    }
    
    /**
     * Rotates image by number of degrees
     * @param int $degrees
     * @return mixed
     */
    public function rotate($degrees){
        return ($this->image instanceof \Nette\Utils\Image) ? $this->image->rotate(-1*$degrees, 0) : $this->image->rotateimage(self::getTransparent(), $degrees);
    }
    
    /**
     * Resize images by longest side
     * @param int $width
     * @param int $height
     * @return $this
     */
    private function resizeByLongest($width, $height) {
        if($this->image instanceof \Nette\Utils\Image){
            if ($this->width < $this->height) {
                $this->image->resize($height, $width);
            } else {
                $this->image->resize($width, $height);
            }
        } else {
            if ($this->width < $this->height) {
                $this->image->resizeimage($height, $width, \Imagick::FILTER_UNDEFINED, 0.7, true);
            } else {
                $this->image->resizeimage($width, $height, \Imagick::FILTER_UNDEFINED, 0.7, true);
            }
        }
        return $this;
    }

    /**
     * Create and save responsive variants of image
     * @param array $imageSizes
     * @param string $targetFolder
     * @param string $targetBaseName
     */
    public function processResizes($imageSizes = null, $targetFolder = null, $targetBaseName = null){
        $sizes = ($imageSizes!==null) ? $imageSizes : self::$defaultImageSizes;
        $folderPath = ($targetFolder!==null) ? $targetFolder : str_ireplace(basename($this->imagePath), '', $this->imagePath);
        $filename = ($targetBaseName = null) ? $targetBaseName : $this->sanitizedFilename; 
        $this->rotateByExif();
        if($this->image instanceof \Nette\Utils\Image){
            $this->processResizesGD($sizes, $folderPath, $filename);
        } else {
            $this->processResizesImagick($sizes, $folderPath, $filename);
        }
    }

    private function processResizesImagick($sizes, $folderPath, $filename) {
        $image = clone $this->image;
        $this->image->setcompressionquality(self::DEFAULT_QUALITY);
        
        foreach ($sizes as $sizeName => $size) {
            if (!is_dir($folderPath . '/resized/' . $sizeName)) {
                mkdir($folderPath . '/resized/' . $sizeName, 0754, true);
            }
            $filepath = $folderPath . '/resized/' . $sizeName . '/' . $filename . $this->extension;
            Tooler::unlinkIfExists($filepath);
            if ($sizeName == self::THUMBNAIL_SIZE_NAME) {
                $this->image->cropthumbnailimage($size['w'], $size['h']);
            } else {
                $this->resizeByLongest($size['w'], $size['h']);
            }
            $this->image->writeimage($filepath);
            $this->image = clone $image;
        }
        unset($image);
    }

    private function processResizesGD($sizes, $folderPath, $filename){
        $image = clone $this->image;
        foreach ($sizes as $sizeName => $size) {
            if (!is_dir($folderPath . '/resized/' . $sizeName)) {
                mkdir($folderPath . '/resized/' . $sizeName, 0754, true);
            }
            $filepath = $folderPath . '/resized/' . $sizeName . '/' . $filename . $this->extension;
            Tooler::unlinkIfExists($filepath);
            if ($sizeName == self::THUMBNAIL_SIZE_NAME) {
                $this->image->resize($size['w'], $size['h'], \Nette\Utils\Image::FILL);
                $this->image->resize($size['w'], $size['h'], \Nette\Utils\Image::EXACT);
            } else {
                $this->resizeByLongest($size['w'], $size['h']);
            }
            $this->image->save($filepath, self::DEFAULT_QUALITY);
            $this->image = clone $image;
        }
        unset($image);
    }

    /**
     * Delete responsive variants of image
     * @param boolean $deleteOriginal
     * @param array $imageSizes
     * @param string $targetFolder
     * @param string $targetBaseName
     * @return boolean true on success
     */
    public static function deleteResizes($deleteOriginal = false, $imageSizes = null, $targetFolder = null, $targetBaseName = null){
        $sizes = ($imageSizes!==null) ? $imageSizes : self::$defaultImageSizes;
        $folderPath = ($targetFolder!==null) ? $targetFolder : str_ireplace(basename($this->imagePath), '', $this->imagePath);
        $filename = ($targetBaseName = null) ? $targetBaseName : $this->sanitizedFilename; 
        foreach($sizes as $sizeName=>$size){
                $filepath = $folderPath.'/resized/'.$sizeName.'/'.$filename.$this->extension;
                Tooler::unlinkIfExists($filepath);
        }
        if($deleteOriginal==true){
            $this->image->destroy();
            Tooler::unlinkIfExists($this->imagePath);
        }
        return true;
    }
    
    /**
     * Creates thumbnail from PDF file and saves it to location
     * @param string $pdfPath
     * @param string $saveFilepath
     * @param int $width
     * @param int $height
     * @return boolean
     */
    public static function savePDFImage($pdfPath, $saveFilepath, $width, $height){
        if(file_exists($pdfPath)){
            $image = \Nette\Utils\Image::fromString($this->snapPDFThumbBlob($pdfPath));
            $image->resize($width, $height, \Nette\Utils\Image::FIT);
            Tooler::unlinkIfExists($saveFilepath);
            $image->save($saveFilepath, self::QUALITY);
            return true;
        }
        return false;
    }
    
    /**
     * Snaps first page of pdf and returns it as jpeg image blob
     * @param string $pdfPath
     * @return mixed
     */
    public static function snapPDFThumbBlob($pdfPath){
        $img = new \Imagick();
        $img->readimage($pdfPath)[0];
        $img->setimageformat('jpg');
        $img->setimagecompression(\Imagick::COMPRESSION_JPEG);
        $img->setimagecompressionquality(100);
        return $img->getimageblob();
    }
    
    /**
     * Resizes image by longest side and overwrites original
     * @param int $width
     * @param int $height
     */
    public function resizeOverwrite($width, $height){
        $this->resizeByLongest($width, $height);
        if($this->image instanceof \Nette\Utils\Image){
            $this->image->save($this->imagePath, self::QUALITY);
        } else {
            Tooler::unlinkIfExists($this->imagePath);
            $this->image->writeimage($this->imagePath);
        }
    }
}
