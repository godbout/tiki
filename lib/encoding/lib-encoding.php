<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Encoding
{
    public $_inputSupportedEncodings = ['ISO-8859-1', 'ISO-8859-15', 'UTF-8'];
    public $_ouputSupportedEncodings = ['ISO-8859-1', 'ISO-8859-15', 'UTF-8', ];
    public $_supportedEncodings = 'UTF-8,ISO-8859-1,ISO-8859-15';

    // Class constructor
    public function __construct($inputEncoding = 'ISO-8859-1', $outputEncoding = 'UTF-8')
    {

        // Check if mb_convert_encoding is installed
        if (function_exists('mb_convert_encoding') && $this->set_input_encoding($inputEncoding) && $this->set_output_encoding($outputEncoding)) {
            return true;
        }

        return false;
    }

    // Set default input encoding, return false if fails loading encoding
    public function set_input_encoding($encoding)
    {
        $this->_input_encoding = $encoding;

        return true;
    }

    // Set default output encoding, return false if fails loading encoding
    public function set_output_encoding($encoding)
    {
        $this->_output_encoding = $encoding;

        return true;
    }

    // Get default input encoding
    public function get_input_encoding()
    {
        return $this->_input_encoding;
    }

    // Get default output encoding
    public function get_output_encoding()
    {
        return $this->_output_encoding;
    }

    // Return encoding of a string
    public function detect_encoding($str)
    {
        return mb_detect_encoding($str, $this->_supportedEncodings);
    }

    // Convert string to another encoding, return false on failure
    public function convert_encoding($str, $inputEncoding = null, $outputEncoding = null)
    {
        if ($inputEncoding == null || $inputEncoding == '') {
            if ($this->get_input_encoding() == '') {
                $this->set_input_encoding($this->detect_encoding($str));
            }

            $inputEncoding = $this->get_input_encoding();
        }
        if ($outputEncoding == null || $inputEncoding == '') {
            if ($this->get_output_encoding() == '') {
                $this->set_output_encoding($this->detect_encoding($str));
            }

            $outputEncoding = $this->get_output_encoding();
        }

        // $returnStr = mb_convert_encoding ($str, $outputEncoding, $inputEncoding);
        $returnStr = iconv($inputEncoding, $outputEncoding . "//TRANSLIT", $str);
        // print $outputEncoding.' - '.$inputEncoding.' - '.$returnStr.'<br>';
        return $returnStr;
    }

    // Returns true if $string is valid UTF-8 and false otherwise.
    public function is_utf8($str)
    {
        // From http://w3.org/International/questions/qa-forms-utf-8.html
        return preg_match(
            '%^(?:
	         [\x09\x0A\x0D\x20-\x7E]            # ASCII
	       | [\xC2-\xDF][\x80-\xBF]            # non-overlong 2-byte
	       |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
	       | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
	       |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
	       |  \xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
	       | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
	       |  \xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
			)*$%xs',
            $str
        );
    }

    // Return array of supported input encodings
    public function get_input_supported_encodings()
    {
        return $this->_inputSupportedEncodings;
    }

    // Return array of supported output encodings
    public function get_output_supported_encodings()
    {
        return $this->_outputSupportedEncodings;
    }

    // Return array of supported encodings
    public function get_supported_encodings()
    {
        return $this->_supportedEncodings;
    }
}
