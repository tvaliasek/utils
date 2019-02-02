<?php

/**
 * Based on code from Nette\Application\Responses\FileResponse from David Grudl (https://davidgrudl.com)
 */

namespace Tvaliasek\Utils;

use Nette;


/**
 * X-Sendfile file download response.
 */
class XSendfileResponse implements Nette\Application\IResponse
{
    use Nette\SmartObject;

    /** @var string */
    private $file;

    /** @var string */
    private $contentType;

    /** @var string */
    private $name;

    /**
     * XSendfileResponse constructor.
     * @param string $file
     * @param string|null $name
     * @throws \Exception
     */
    public function __construct(string $file, string $name = null)
    {
        if (!file_exists($file)) {
            throw new \InvalidArgumentException('Cannot find file: ' . $file);
        }
        if (function_exists('apache_get_modules') &&
            !in_array('mod_xsendfile', apache_get_modules())
        ) {
            throw new \Exception('X-Sendfile (mod_xsendfile) is not supported on your hosting.');
        }
        $this->file = $file;
        $this->name = $name ? $name : basename($file);
        $this->contentType = 'application/octet-stream';
    }


    /**
     * Returns the path to a downloaded file.
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }


    /**
     * Returns the file name.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }


    /**
     * Returns the MIME content type of a downloaded file.
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }


    /**
     * Sends response with appropriate headers.
     * @param Nette\Http\IRequest $httpRequest
     * @param Nette\Http\IResponse $httpResponse
     * @return void
     */
    public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse): void
    {
        $httpResponse->setContentType($this->contentType);
        $httpResponse->setHeader('Content-Disposition',
            'attachment'
            . '; filename="' . $this->name . '"'
            . '; filename*=utf-8\'\'' . rawurlencode($this->name));
        $length = filesize($this->file);
        $httpResponse->setHeader('Content-Length', $length);
        $httpResponse->setHeader('X-Sendfile', realpath($this->file));
    }
}
