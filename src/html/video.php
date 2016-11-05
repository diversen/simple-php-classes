<?php

namespace diversen\html;

use diversen\template\assets;

class video {

    public function videojsInclude() {

        static $loaded = null;
        $css = <<<EOF
.video-js {
    min-width:100%; 
    max-width:100%;

}
.vjs-fullscreen {padding-top: 0px}
EOF;
        if (!$loaded) {
            assets::setEndHTML(' <script src="https://vjs.zencdn.net/5.6.0/video.js"></script>');
            assets::setRelAsset('css', 'https://vjs.zencdn.net/5.6.0/video-js.css');
            assets::setStringCss($css, null, array('head' => true));
            $loaded = true;
            return $css;
        }
    }

    /**
     * 
     * @param string $title
     * @param array $formats files to use
     * @return string $html
     */
    function getVideojsHtml($title, $formats) {

        $mp4 = $formats['mp4'];
        $str = <<<EOF
<div class="wrapper">
 <div class="videocontent">
	
<video class="video-js vjs-default-skin" controls ="controls"
 preload="none"
 data-setup='{}'>
  <source type="video/mp4" src="$mp4">  
  <p class="vjs-no-js">
    To view this video please enable JavaScript, and consider upgrading to a web browser
    that <a href="http://videojs.com/html5-video-support/" target="_blank">supports HTML5 video</a>
  </p>
</video>
  </div>
</div>
EOF;
        return $str;
    }

    /**
     * 
     * @param string $title
     * @param array $formats files to use
     * @return string $html
     */
    public function getHtml5($title, $formats) {

        $mp4 = $formats['mp4'];
        $str = <<<EOF
<div class="video">
<video width="100%" preload="none" controls="controls">
  <source src="$mp4" type="video/mp4" /> 
  Your browser does not support HTML5 video.
</video>
</div>
EOF;
        return $str;
    }
}
