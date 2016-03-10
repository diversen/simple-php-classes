<?php

namespace diversen\filter;

use diversen\conf as conf;
use diversen\file;
use diversen\log;
use diversen\db\q;


/**
 * Markdown filter that uploads images to a database, and substitute full path
 * image links with online path to a controller. 
 */
class mdUploadImages extends \Michelf\Markdown {

    /**
     * Name of the reference given to image class
     * @var string 
     */
    public $reference;
    
    /**
     * Parent id of upload image
     * @var int
     */
    public $parentId;
    
    /**
     * Var holding image user id
     * @var int 
     */
    public $userId; 
    
    
    protected function _doImages_reference_callback($matches) {
        $whole_match = $matches[1];
        $alt_text = $matches[2];
        $link_id = strtolower($matches[3]);

        if ($link_id == "") {
            $link_id = strtolower($alt_text); # for shortcut links like ![this][].
        }

        $alt_text = $this->encodeAttribute($alt_text);
        if (isset($this->urls[$link_id])) {
            $url = $this->encodeAttribute($this->urls[$link_id]);
            
            $type = $this->getType($url);
            if ($type == 'mp4') {
                $full_path = conf::pathHtdocs() . $url;
                return "![$alt_text]($url)";
            }
            
            if ($this->isImage($url)) {
                return "![$alt_text](" . $this->uploadImage($url) . ")";
            }
            return;
        } 
    }
    
        

    protected function _doImages_inline_callback($matches) {
        $whole_match = $matches[1];
        $alt_text = $matches[2];
        $url = $matches[3] == '' ? $matches[4] : $matches[3];
        $title = & $matches[7];

        $alt_text = $this->encodeAttribute($alt_text);
        $url = $this->encodeAttribute($url);

        $type = $this->getType($url);
        if ($type == 'mp4') {
            $full_path = conf::pathHtdocs() . $url;
            return "![$alt_text]($url)";
        }

        if ($this->isImage($url)) {
            return "![$alt_text](" . $this->uploadImage($url) . ")";
        }

        return;

    }
    
    /**
     * Get type of extension
     * @param type $url
     * @return type
     */
    protected function getType($url) {
        $type = file::getExtension($url);
        return strtolower($type);        
    }


    protected function doImages($text) {
        #
        # Turn Markdown image shortcuts into <img> tags.
        #
		#
		# First, handle reference-style labeled images: ![alt text][id]
        #
		$text = preg_replace_callback('{
			(				# wrap whole match in $1
			  !\[
				(' . $this->nested_brackets_re . ')		# alt text = $2
			  \]

			  [ ]?				# one optional space
			  (?:\n[ ]*)?		# one optional newline followed by spaces

			  \[
				(.*?)		# id = $3
			  \]

			)
			}xs', array(&$this, '_doImages_reference_callback'), $text);

        #
        # Next, handle inline images:  ![alt text](url "optional title")
        # Don't forget: encode * and _
        #
		$text = preg_replace_callback('{
			(				# wrap whole match in $1
			  !\[
				(' . $this->nested_brackets_re . ')		# alt text = $2
			  \]
			  \s?			# One optional whitespace character
			  \(			# literal paren
				[ \n]*
				(?:
					<(\S*)>	# src url = $3
				|
					(' . $this->nested_url_parenthesis_re . ')	# src url = $4
				)
				[ \n]*
				(			# $5
				  ([\'"])	# quote char = $6
				  (.*?)		# title = $7
				  \6		# matching quote
				  [ \n]*
				)?			# title is optional
			  \)
			)
			}xs', array(&$this, '_doImages_inline_callback'), $text);

        return $text;
    }
   
    /**
     * Checks broken media
     * @param type $url
     * @return boolean
     */
    protected function isImage($url) {
        
        $type = file::getExtension($url);
        if ($type == 'mp4') {
            log::error($url);
            return false;
        }   
        return true;
    }
    
    protected function uploadImage($url) {

        // Array ( [name] => Angus_cattle_18.jpg [type] => image/jpeg [tmp_name] => /tmp/php5lPQZT [error] => 0 [size] => 52162 )

        $ary = [];
        
        $name = file::getFilename($url) . "." . file::getExtension($url);
        
        $ary['name'] = $name;
        $ary['type'] = file::getMime($url);
        $ary['tmp_name'] = $url;
        $ary['error'] = 0;
        $ary['size'] = 0;
        
        $i = new \modules\image\uploadBlob();
        $res = $i->insertFileDirect($ary, $this->reference, $this->parentId, $this->userId);
        if ($res) {
            $id = q::lastInsertId();
            $row = $i->getSingleFileInfo($id);
            return $i->getFullWebPath($row);
        } else {
            log::error("Could not upload image: $name");
            return false;
        }
    }
    
    public function filter ($text) {
        return $this->doImages($text);
    }
}
