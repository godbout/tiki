<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Lib\Image;

/**
 *
 */
class ImagickOld extends ImageAbstract
{

	/**
	 * @param $image
	 * @param bool $isfile
	 * @param string $format
	 */
	public function __construct($image, $isfile = false, $format = 'jpeg')
	{
		if ($isfile) {
			$this->filename = $image;
			parent::__construct(null, false);
		} else {
			parent::__construct($image, false);
		}
	}

	protected function loadData()
	{
		if (! $this->loaded) {
			if (! empty($this->filename)) {
				$this->data = imagick_readimage($this->filename);
				$this->loaded = true;
			} elseif (! empty($this->data)) {
				$this->data = imagick_blob2image($this->data);
				$this->loaded = true;
			}
			if ($this->loaded && ($t = imagick_failedreason($this->data))) {
				$this->data = null;
			}
		}
	}

	/**
	 * @param $x
	 * @param $y
	 * @return mixed
	 */
	protected function resizeImage($x, $y)
	{
		if ($this->data) {
			return imagick_scale($this->data, $x, $y);
		}
	}

	public function resizeThumb()
	{
		if ($this->thumb !== null) {
			$this->data = imagick_blob2image($this->thumb);
			$this->loaded = true;
		} else {
			$this->loadData();
		}
		if ($this->data) {
			return parent::resizeThumb();
		}
	}

	/**
	 * @return mixed
	 */
	public function getMimeType()
	{
		$this->loadData();
		if ($this->data) {
			return imagick_getmimetype($this->data);
		}
	}

	/**
	 * @param $format
	 */
	public function setFormat($format)
	{
		$this->loadData();
		$this->format = $format;
		if ($this->data) {
			imagick_convert($this->data, strtoupper(trim($format)));
		}
	}

	/**
	 * @return string
	 */
	public function getFormat()
	{
		return $this->format;
	}

	/**
	 * @return mixed
	 */
	public function display()
	{
		$this->loadData();
		if ($this->data) {
			return imagick_image2blob($this->data);
		}
	}

	/**
	 * @param $angle
	 * @return bool
	 */
	public function rotate($angle)
	{
		$this->loadData();
		if ($this->data) {
			imagick_rotate($this->data, -$angle);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param $format
	 * @return bool
	 */
	public function isSupported($format)
	{
		// not handled yet: html, mpeg, pdf
		return in_array(
			strtolower($format),
			[
				'art',
				'avi',
				'avs',
				'bmp',
				'cin',
				'cmyk',
				'cur',
				'cut',
				'dcm',
				'dcx',
				'dib',
				'dpx',
				'epdf',
				'fits',
				'gif',
				'gray',
				'ico',
				'jng',
				'jpg',
				'jpeg',
				'mat',
				'miff',
				'mono',
				'mng',
				'mpc',
				'msl',
				'mtv',
				'mvg',
				'otb',
				'p7',
				'palm',
				'pbm',
				'pcd',
				'pcds',
				'pcl',
				'pcx',
				'pdb',
				'pfa',
				'pfb',
				'pgm',
				'picon',
				'pict',
				'pix',
				'png',
				'pnm',
				'ppm',
				'psd',
				'ptif',
				'pwp',
				'rgb',
				'rgba',
				'rla',
				'rle',
				'sct',
				'sfw',
				'sgi',
				'sun',
				'tga',
				'tim',
				'txt',
				'uil',
				'uyvy',
				'vicar',
				'viff',
				'wbmp',
				'wpg',
				'xbm',
				'xcf',
				'xpm',
				'xwd',
				'yuv'
			]
		);
	}

	/**
	 * @return mixed
	 */
	public function getHeight()
	{
		$this->loadData();
		if ($this->data) {
			return imagick_getheight($this->data);
		}
	}

	/**
	 * @return mixed
	 */
	public function getWidth()
	{
		$this->loadData();
		if ($this->data) {
			return imagick_getwidth($this->data);
		}
	}
}
