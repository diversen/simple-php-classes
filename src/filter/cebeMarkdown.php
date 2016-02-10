<?php

namespace diversen\filter;

/**
 * In order to use this, you will need cebe-markdown: 
 * *composer require cebe/markdown*
 * It is just a wrapper around cebe-markdown
 * @example
<code>
 use diversen\filters\cebeMarkdown;
 $txt = cebeMarkdown::filter($txt);
</code>
*/
class cebeMarkdown {
    
    public static function filter ($text) {
        $parser = new \cebe\markdown\GithubMarkdown();
        return $parser->parse($text);
    }
}
