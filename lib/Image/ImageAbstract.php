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
class ImageAbstract
{
	protected $data = null;
	protected $format = 'jpeg';
	protected $height = null;
	protected $width = null;
	protected $classname = 'ImageAbstract';
	protected $filename = null;
	protected $thumb = null;
	protected $loaded = false;
	protected $metadata = null;			//to hold metadata from the FileMetadata class

	/**
	 * @param $image
	 * @param bool $isfile
	 */
	public function __construct($image, $isfile = false)
	{
		if (! empty($image) || $this->filename !== null) {
			if (is_readable($this->filename) && function_exists('exif_thumbnail') && in_array(image_type_to_mime_type(exif_imagetype($this->filename)), ['image/jpeg', 'image/tiff'])) {
				$this->thumb = @exif_thumbnail($this->filename);
				if (trim($this->thumb) == "") {
					$this->thumb = null;
				}
			}
			$this->classname = get_class($this);
			if ($isfile) {
				$this->filename = $image;
			} else {
				$this->data = $image;
			}
		}
	}

	protected function _load_data()
	{
		if (! $this->loaded) {
			if (! empty($this->filename)) {
				$this->data = $this->get_from_file($this->filename);
				$this->loaded = true;
			} elseif (! empty($this->data)) {
				$this->loaded = true;
			}
		}
	}

	/**
	 * @return bool
	 */
	public function is_empty()
	{
		return empty($this->data) && empty($this->filename);
	}

	/**
	 * @param $filename
	 * @return null|string
	 */
	public function get_from_file($filename)
	{
		$content = null;
		if (is_readable($filename)) {
			$f = fopen($filename, 'rb');
			$size = filesize($filename);
			$content = fread($f, $size);
			fclose($f);
		}
		return $content;
	}

	/**
	 * @param $x
	 * @param $y
	 */
	protected function _resize($x, $y)
	{
	}

	/**
	 * @param int $x
	 * @param int $y
	 */
	public function resize($x = 0, $y = 0)
	{
		$this->_load_data();
		if ($this->data) {
			$x0 = $this->get_width();
			$y0 = $this->get_height();

			if ($x > 0 || $y > 0) {
				if ($x <= 0) {
					$x = $x0 * ( $y / $y0 );
				}
				if ($y <= 0) {
					$y = $y0 * ( $x / $x0 );
				}
				$this->_resize($x + 0, $y + 0);
			}
		}
	}

	/**
	 * @param $max
	 */
	public function resizemax($max)
	{
		$this->_load_data();
		if ($this->data) {
			$x0 = $this->get_width();
			$y0 = $this->get_height();
			if ($x0 <= 0 || $y0 <= 0 || $max <= 0) {
				return;
			}
			if ($x0 > $max || $y0 > $max) {
				$r = $max / ( ( $x0 > $y0 ) ? $x0 : $y0 );
				$this->scale($r);
			}
		}
	}

	public function resizethumb()
	{
		require_once('tiki-setup.php');
		global $prefs;
		$this->resizemax($prefs['fgal_thumb_max_size']);
	}

	/**
	 * @param $r
	 */
	public function scale($r)
	{
		$this->_load_data();
		$x0 = $this->get_width();
		$y0 = $this->get_height();
		if ($x0 <= 0 || $y0 <= 0 || $r <= 0) {
			return;
		}
		$this->_resize($x0 * $r, $y0 * $r);
	}

	/**
	 * @return string
	 */
	public function get_mimetype()
	{
		return 'image/' . strtolower($this->get_format());
	}

	/**
	 * @param $format
	 */
	public function set_format($format)
	{
		$this->format = $format;
	}

	/**
	 * @return string
	 */
	public function get_format()
	{
		if ($this->format == '') {
			$this->set_format('jpeg');
			return 'jpeg';
		} else {
			return $this->format;
		}
	}

	/**
	 * @return null
	 */
	public function display()
	{
		$this->_load_data();
		return $this->data;
	}

	/**
	 * @param $format
	 * @return bool
	 */
	public function convert($format)
	{
		if ($this->is_supported($format)) {
			$this->set_format($format);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param $angle
	 */
	public function rotate($angle)
	{
	}

	/**
	 * @param $format
	 * @return bool
	 */
	public function is_supported($format)
	{
		return false;
	}

	/**
	 * @return string
	 */
	public function get_icon_default_format()
	{
		return 'png';
	}

	/**
	 * @return int
	 */
	public function get_icon_default_x()
	{
		return 16;
	}

	/**
	 * @return int
	 */
	public function get_icon_default_y()
	{
		return 16;
	}

	/**
	 * @param $extension
	 * @param int $x
	 * @param int $y
	 * @return bool|null|string
	 */
	public function icon($extension, $x = 0, $y = 0)
	{
		$keep_original = ( $x == 0 && $y == 0 );

		$format = $this->get_icon_default_format();
		$icon_format = '';

		if (! $keep_original) {
			$icon_format = $format;

			if ($this->is_supported('png')) {
				$format = 'png';
			} elseif ($this->is_supported('svg')) {
				$format = 'svg';
			} else {
				return false;
			}
		}

		$name = "img/icons/mime/large/$extension.$format";
		if (! file_exists($name)) {
			$name = "img/icons/mime/large/unknown.$format";
		}

		if (! $keep_original && $format != 'svg') {
			$icon = Image::create($name, true, $format);
			if ($format != $icon_format) {
				$icon->convert($icon_format);
			}
			if ($x < $this->_get_width() && $y < $this->_get_height()) {
				$icon->resize($x, $y);
			}

			return $icon->display();
		} else {
			return $this->get_from_file($name);
		}
	}

	/**
	 * @return null
	 */
	protected function _get_height()
	{
		return null;
	}

	/**
	 * @return null
	 */
	protected function _get_width()
	{
		return null;
	}

	/**
	 * @return null
	 */
	public function get_height()
	{
		if ($this->height === null) {
			$this->height = $this->_get_height();
		}
		return $this->height;
	}

	/**
	 * @return null
	 */
	public function get_width()
	{
		if ($this->width === null) {
			$this->width = $this->_get_width();
		}
		return $this->width;
	}

	/**
	 * @param null $filename
	 * @param bool $ispath
	 * @param bool $extended
	 * @param bool $bestarray
	 * @return FileMetadata|null
	 */
	public function getMetadata($filename = null, $ispath = true, $extended = true, $bestarray = true)
	{
		include_once('lib/metadata/metadatalib.php');
		if ($filename === null) {
			if (! empty($this->filename)) {
				$filename = $this->filename;
				$ispath = true;
			} elseif (! empty($this->data)) {
				$filename = $this->data;
				$ispath = false;
			}
		}
		if (! is_object($this->metadata) || get_class($this->metadata) != 'FileMetadata') {
			$metadata = new FileMetadata;
			$this->metadata = $metadata->getMetadata($filename, $ispath, $extended);
		}
		return $this->metadata;
	}

	/**
	 * Allow adding text as overlay to a image
	 * @param $text
	 * @return string || boolean
	 */
	public function addTextToImage($text)
	{
	}
}
