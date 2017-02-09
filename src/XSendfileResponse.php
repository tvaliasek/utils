<?php

/**
 * This file is NOT part of the Nette Framework (https://nette.org)
 * Based on code from Nette\Application\Responses\FileResponse from David Grudl (https://davidgrudl.com)
 */

namespace Tvaliasek\Utils;

use Nette;


/**
 * X-Sendfile file download response.
 */
class XSendfileResponse implements \Nette\Application\IResponse
{
	use Nette\SmartObject;

	/** @var string */
	private $file;

	/** @var string */
	private $contentType;

	/** @var string */
	private $name;

	/**
	 * @param  string  file path
	 * @param  string  imposed file name
	 */
	public function __construct($file, $name = NULL)
	{
		if (!is_file($file)) {
			throw new \Nette\Application\BadRequestException("File '$file' doesn't exist.");
		}
		
		if (function_exists('apache_get_modules') && !in_array('mod_xsendfile', apache_get_modules())) {
			throw new \Nette\Application\BadRequestException("X-Sendfile (mod_xsendfile) is not supported on your hosting.");
		}

		$this->file = $file;
		$this->name = $name ? $name : basename($file);
		$this->contentType = 'application/octet-stream';
	}


	/**
	 * Returns the path to a downloaded file.
	 * @return string
	 */
	public function getFile()
	{
		return $this->file;
	}


	/**
	 * Returns the file name.
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}


	/**
	 * Returns the MIME content type of a downloaded file.
	 * @return string
	 */
	public function getContentType()
	{
		return $this->contentType;
	}


	/**
	 * Sends response with appropriate headers.
	 * @return void
	 */
	public function send(\Nette\Http\IRequest $httpRequest, \Nette\Http\IResponse $httpResponse)
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
