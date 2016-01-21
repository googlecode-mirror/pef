# Introduction #

对图片进行缩放


# Details #

Add your content here.  Format your content with:
  * Text in **bold** or _italic_
  * Headings, paragraphs, and lists
  * Automatic links to other wiki pages

```
<?php

/**
 * @author henry
 * @copyright 2011
 */

function imageResize($srcFile, $toW, $toH, $toFile = "")
{
    if ($toFile == "") {
        $toFile = $srcFile;
    }
    $info = "";
    $data = getimagesize($srcFile, $info);
    switch ($data[2]) {
        case 1:
            if (!function_exists("imagecreatefromgif")) {
                echo "你的GD库不能使用GIF格式的图片，请使用Jpeg或PNG格式！<a href='javascript:go(-1);'>返回</a>";
                exit();
            }
            $im = imagecreatefromgif($srcFile);
            break;
        case 2:
            if (!function_exists("imagecreatefromjpeg")) {
                echo "你的GD库不能使用jpeg格式的图片，请使用其它格式的图片！<a href='javascript:go(-1);'>返回</a>";
                exit();
            }
            $im = imagecreatefromjpeg($srcFile);
            break;
        case 3:
            $im = imagecreatefrompng($srcFile);
            break;
    }
    $srcW = imagesx($im);
    $srcH = imagesy($im);
    $toWH = $toW / $toH;
    $srcWH = $srcW / $srcH;
    if ($toWH <= $srcWH) {
        $ftoW = $toW;
        $ftoH = $ftoW * ($srcH / $srcW);
    } else {
        $ftoH = $toH;
        $ftoW = $ftoH * ($srcW / $srcH);
    }
    if ($srcW > $toW || $srcH > $toH) {
        if (function_exists("imagecreatetruecolor")) {
            @$ni = imagecreatetruecolor($ftoW, $ftoH);
            if ($ni)
                imagecopyresampled($ni, $im, 0, 0, 0, 0, $ftoW, $ftoH, $srcW, $srcH);
            else {
                $ni = imagecreate($ftoW, $ftoH);
                imagecopyresized($ni, $im, 0, 0, 0, 0, $ftoW, $ftoH, $srcW, $srcH);
            }
        } else {
            $ni = imagecreate($ftoW, $ftoH);
            imagecopyresized($ni, $im, 0, 0, 0, 0, $ftoW, $ftoH, $srcW, $srcH);
        }
        if (function_exists('imagejpeg'))
            imagejpeg($ni, $toFile);
        else
            imagepng($ni, $toFile);
        imagedestroy($ni);
    }
    imagedestroy($im);
}

?>
```