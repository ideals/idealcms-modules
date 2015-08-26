<?php

namespace Shop\Structure\Service\Load1cV2;

class Image
{
    private $color = array();
    private $water;
    private $img;

    public function __construct($img, $width, $height, $border = true)
    {
        $this->img = $img;
        $image = basename($img);
        $entry = substr($image, 0, 2);

        $this->water = 'TEXT';
        $this->tmpDir = DOCUMENT_ROOT . '/tmp/1c/1/';
        $this->dirImage = DOCUMENT_ROOT . '/images/1c';

        if (!file_exists("{$this->dirImage}/{$entry}")) {
            mkdir("{$this->dirImage}/{$entry}", 0750, true);
        }
        $this->color1("e6e6e6");

        $filename = "{$this->dirImage}/{$entry}/" . $image;


        if (file_exists($filename)) {
            unlink($filename);
        }
        $this->resize($this->img, $width, $height, "{$this->dirImage}/{$entry}/", $border);
    }

    private function color1($tmp)
    {
        $this->color['r'] = hexdec(substr($tmp, 0, 2));
        $this->color['g'] = hexdec(substr($tmp, 2, 2));
        $this->color['b'] = hexdec(substr($tmp, 4, 2));
    }

    private function resize($image, $newWidth = 100, $newHeight = 100, $uri = 'images/', $border = true)
    {
        $img = null;
        $i = pathinfo($image);
        $size = getimagesize($image);
        $srcW = $size[0];
        $srcH = $size[1];
        $extension = strtolower($i['extension']);
        switch ($extension) {
            case 'gif':
                $img = imagecreatefromgif($image);
                break;
            case 'jpg':
            case 'jpeg':
                $img = imagecreatefromjpeg($image);
                break;
            case 'png':
                $img = imagecreatefrompng($image);
                break;
            default:
                break;
        }

        if ($srcH < $newHeight) {
            $newHeight = $srcH;
        }
        if ($srcW < $newWidth) {
            $newWidth = $srcW;
        }
        // Пропорциональное уменьшение изображения
        $tmp = $srcW / $newWidth;
        $tmp2 = $srcH / $newHeight;
        $k = $tmp2;
        if ($tmp > $tmp2) {
            $k = $tmp;
        }
        // Подсчет новой высоты и ширины
        $h2 = $srcH / $k;
        $w2 = $srcW / $k;
        $newImage = imagecreatetruecolor($w2, $h2);
        $color = imagecolorallocate($newImage, $this->color['r'], $this->color['g'], $this->color['b']);
        imagefilledrectangle($newImage, 0, 0, $w2, $h2, $color);
        imagecopyresampled($newImage, $img, 0, 0, 0, 0, $w2, $h2, $srcW, $srcH);
        if (($w2 != $newWidth || $h2 != $newHeight) && $border) {
            // Если картинка была уменьшина в пропорциях
            // То она будет доведена до требуемого размера
            $tmpImage = imagecreatetruecolor($newWidth, $newHeight);
            imagesavealpha($tmpImage, true);
            $margeX = ($newWidth - $w2) / 2;
            $margeY = ($newHeight - $h2) / 2;
            $color = imagecolorallocate($tmpImage, $this->color['r'], $this->color['g'], $this->color['b']);
            imagefilledrectangle($tmpImage, 0, 0, $newWidth, $newHeight, $color);
            imagecopy($tmpImage, $newImage, $margeX, $margeY, 0, 0, $w2, $h2);
            $newImage = $tmpImage;
        }

        switch ($extension) {
            case 'jpg':
                imagejpeg($newImage, $uri . $i['filename'] . '.jpg');
                break;
            case 'jpeg':
                imagejpeg($newImage, $uri . $i['filename'] . '.jpeg');
                break;
            default:
                break;
        }
    }
}
