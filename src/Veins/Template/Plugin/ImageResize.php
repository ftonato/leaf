<?php

/*-
 * Copyright © 2011–2014 Federico Ulfo and a lot of awesome contributors
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * “Software”), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
 * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Leaf\Veins\Template\Plugin;

require_once __DIR__ . '/../Plugin.php';

class ImageResize extends \Leaf\Veins\Template\Plugin{

    protected $hooks = array('beforeParse');
    protected $quality = 80;
    protected $crop = TRUE;

    public function beforeParse(\ArrayAccess $context){
        // set variables
        $html = $context->code;
        $template_basedir = $context->template_basedir;
        $quality = $this->quality;
        $auto_crop = $this->crop;
        $conf = $context->conf;

        $img_cache_dir = $template_basedir = $conf['cache_dir'];


        // get the template base directory
        $template_directory = $conf['base_url'] . $conf['veins_dir'] . $template_basedir;

        // reduce the path
        $path = preg_replace('/\w+\/\.\.\//', '', $template_directory );

        $exp = $sub = array();

        $image_resized = false;

        // match the images
        if( preg_match_all( '/<img((?:\s*(src="(?<src>.*?)"))|(\s*(width="(?<width>.*?)"))|(\s*height="(?<height>.*?)")|(\s*resize="(?<resize>.*?)")|(\s*crop="(?<crop>.*?)"))*.*?>/', $html, $matches ) ){

            for( $i=0,$n=count($matches[0]); $i<$n; $i++ ){
                $tag = $matches[0][$i];
                $src = $matches['src'][$i];
                $w = $matches['width'][$i];
                $h = $matches['height'][$i];
                $resize = $matches['resize'][$i];
                if( $auto_crop )
                    $crop = $matches['crop'][$i] == 'false' ? false : true;
                else
                    $crop = $matches['crop'][$i] == 'true' ? true : false;

                if( $w > 0 && $h > 0 && $resize != 'false' ){
                    echo $src;exit;
                    $new_tag = preg_replace( '/(.*?)src="(.*?)"(.*?)/', '$1src="<?php echo Leaf\Veins\Template\Plugin\ImageResize::imgResize(\''.$src.'\', \''.$img_cache_dir.'\', \''.$w.'\', \''.$h.'\', \''.$quality.'\', \''.$crop.'\' ); ?>"$3', $tag );
                    $html = str_replace( $tag, $new_tag, $html );
                    $image_resized = true;
                }

            }

            if( $image_resized )
                $html = '<?php require_once(__FILE__); ?>' . $html;

        }

        $context->code = $html;
    }

    public function setQuality($quality) {
        $this->quality = (int) $quality;
        return $this;
    }

    public function setCrop($crop) {
        $this->crop = (string) $crop;
        return $this;
    }

    public static function imgResize( $src, $dest, $w, $h, $quality, $crop ){

        $ext = substr(strrchr($src, '.'),1);
        $dest = $dest . 'img.'. md5( $src . $crop . $quality ) . $w . 'x' . $h . '.' . $ext;


        if( !file_exists( $dest ) )
            static::rainImgResize( $src, $dest, $w, $h, $quality, $crop );
        return $dest;

    }

    public static function rainImgResize($src, $dst, $width, $height, $quality, $crop=0){

        if(!list($w, $h) = getimagesize($src)) return "Unsupported picture type!";

        $type = strtolower(substr(strrchr($src,"."),1));
        if($type == 'jpeg') $type = 'jpg';
        switch($type){
            case 'bmp': $img = imagecreatefromwbmp($src); break;
            case 'gif': $img = imagecreatefromgif($src); break;
            case 'jpg': $img = imagecreatefromjpeg($src); break;
            case 'png': $img = imagecreatefrompng($src); break;
            default : return "Unsupported picture type!";
        }

        // resize
        if($crop){
            if($w < $width or $h < $height) return "Picture is too small!";
            $ratio = max($width/$w, $height/$h);
            $h = $height / $ratio;
            $x = ($w - $width / $ratio) / 2;
            $w = $width / $ratio;
        }
        else{
            if($w < $width and $h < $height) return "Picture is too small!";
            $ratio = min($width/$w, $height/$h);
            $width = $w * $ratio;
            $height = $h * $ratio;
            $x = 0;
        }

        $new = imagecreatetruecolor($width, $height);

        // preserve transparency
        if($type == "gif" or $type == "png"){
            imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
            imagealphablending($new, false);
            imagesavealpha($new, true);
        }

        imagecopyresampled($new, $img, 0, 0, $x, 0, $width, $height, $w, $h);

        switch($type){
            case 'bmp': imagewbmp($new, $dst, $quality); break;
            case 'gif': imagegif($new, $dst, $quality); break;
            case 'jpg': imagejpeg($new, $dst, $quality); break;
            case 'png': imagepng($new, $dst, $quality); break;
        }
        return true;
    }

}