<?php
namespace Shop\Structure\Service\Load1c;

class Image
{
    private $config;
    private $color = array();
    private $water;
    private $minSizeWater;
    private $nameDir;
    private $img;

    function __construct($img, $width, $height, $nameDir, $border = true)
    {
        $this->img = $img;
        $this->nameDir = $nameDir;
        $image = basename($img);
        $image = basename($root = str_replace('/' . $image, '', $img)) . '/' . $image;

        $this->config = parse_ini_file('ini.ini');
        $this->water = "logo.png";
        $this->tmpDir = "tmp/1c/import_files/";
        $this->dirImage = "images/1c/";
        $this->minSizeWater = "150*150";
        if (!isset($this->config['font'])) {
            $this->config['font'] = 'arial.ttf';
        }
        $this->color1("e6e6e6");
        $filename = "images/1c/{$this->nameDir}/" . basename($this->img);
        if (!file_exists($filename)) {
            $this->resize($this->tmpDir . $image, $width, $height, "images/1c/{$nameDir}/", $border);
        }
    }

    public function getName()
    {
        return "images/1c/{$this->nameDir}/" . basename($this->img);
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
        $water = $this->water;
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

        if ($srcH < $newHeight) $newHeight = $srcH;
        if ($srcW < $newWidth) $newWidth = $srcW;
        // Пропорциональное уменьшение изображения
        $tmp = $srcW / $newWidth;
        $tmp2 = $srcH / $newHeight;
        $k = $tmp2;
        if ($tmp > $tmp2) $k = $tmp;
        // Подсчет новой высоты и ширины
        $h2 = $srcH / $k;
        $w2 = $srcW / $k;
        $newImage = imagecreatetruecolor($w2, $h2);
        $color = imagecolorallocate($newImage, $this->color['r'], $this->color['g'], $this->color['b']);
        imagefilledrectangle($newImage, 0, 0, $w2, $h2, $color);
        imagecopyresampled($newImage, $img, 0, 0, 0, 0, $w2, $h2, $srcW, $srcH);
        if (($w2 != $newWidth OR $h2 != $newHeight) AND $border) {
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
        //$tmp = explode('*', $this->minSizeWater);
        if ($tmp[0] < $newWidth AND $tmp[1] < $newHeight) {
            if ($water != NULL AND $newHeight > 40) {
                $tmp = ($newWidth - 10) / strlen($water);
                if (floor($tmp) >= 20) {
                    $fontSize = 20;
                } elseif (floor($tmp) <= 6) {
                    $fontSize = 0;
                } else {
                    $fontSize = floor($tmp);
                }
                if ($fontSize > 5) {
                    // Вывод текста на картинку
                    $white = imagecolorallocatealpha($newImage, 250, 250, 250, 75);
                    $gray = imagecolorallocatealpha($newImage, 100, 100, 100, 75);
                    $font = $this->config['font']; // Шрифт
                    $bbox = imagettfbbox($fontSize, 45, $font, $water); // определяем размер картинки со шрифтом

                    $x = $newWidth - ($bbox[2] - $bbox[0] + 10); // отступ с левого края
                    $y = $newHeight - ($bbox[1] - $bbox[7] + 10); // отступ с нижнего края
                    imagettftext($newImage, $fontSize + 2, 45, $x - 2, $y + 2, $gray, $font, $water); // вывод текста на картинку
                    imagettftext($newImage, $fontSize, 45, $x, $y, $white, $font, $water); // вывод текста на картинку
                }

            }
        }
        switch ($extension) {
            case 'gif':
                imagegif($newImage, $uri . $i['filename'] . '.' . $extension);
                break;
            case 'jpg':
            case 'jpeg':
                imagejpeg($newImage, $uri . $i['filename'] . '.' . $extension);
                break;
            case 'png':
                imagepng($newImage, $uri . $i['filename'] . '.' . $extension);
                break;
            default:
                break;
        }

    }

}