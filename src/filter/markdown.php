<?php

namespace diversen\filter;

use \Michelf\MarkdownExtra as MD;

/**
 * Markdown wrapper around michelf/markdown. You will need to: 
 * *composer require michelf/php-markdown*
 *
 * @example
  <code>
  use diversen\filters\markdown;
  $txt = markdown::filter($txt);
  </code>
 */
class markdown extends MD {

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
            $result = "<img class=\"media_image\" src=\"$url\" alt=\"$alt_text\"";
            if (isset($this->titles[$link_id])) {
                $title = $this->titles[$link_id];
                $title = $this->encodeAttribute($title);
                $result .= " title=\"$title\"";
            }
            $result .= $this->empty_element_suffix;
            $result = $this->hashPart($result);
        } else {
            # If there's no such link ID, leave intact:
            $result = $whole_match;
        }

        return $result;
    }

    protected function _doImages_inline_callback($matches) {
        $whole_match = $matches[1];
        $alt_text = $matches[2];
        $url = $matches[3] == '' ? $matches[4] : $matches[3];
        $title = & $matches[7];

        $alt_text = $this->encodeAttribute($alt_text);
        $url = $this->encodeAttribute($url);
        $result = "<img class=\"media_image\" src=\"$url\" alt=\"$alt_text\"";
        if (isset($title)) {
            $title = $this->encodeAttribute($title);
            $result .= " title=\"$title\""; # $title already quoted
        }
        $result .= $this->empty_element_suffix;

        return $this->hashPart($result);
    }

    /**
     * Filter markdown withou allowing any entities or markup
     * @param  string  $text the text to transform
     * @return string $text the transformed text
     */
    public static function filter($text) {
        static $md = null;
        if (!$md) {
            $md = new self();
        }
        $md->no_entities = true;
        $md->no_markup = true;
        return $md->transform($text);
    }

}
