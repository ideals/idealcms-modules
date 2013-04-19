<?php
class Image
{
    private $config;
    private $color = array();
    private $water;

    function __construct($img)
    {

        $this->config = parse_ini_file('config.ini');
        $this->water = (isset($this->config['water'])) ? $this->config['water'] : NULL;
        $this->tmpDir = (isset($this->config['tmp_dir'])) ? $this->config['tmp_dir'] : "tmp/1c/";
        if (!isset($this->config['font'])) {
            $this->config['font'] = 'arial.ttf';
        }
        $this->color();
        foreach ($this->config['sizeimg'] as $size) {
            $tmp = explode('*', $size);
            $this->resize($this->tmpDir . $img, $tmp[0], $tmp[1], $this->config['dirImage']);
        }
    }

    private function color()
    {
        $this->color['r'] = hexdec(substr($this->config['color'], 0, 2));
        $this->color['g'] = hexdec(substr($this->config['color'], 2, 2));
        $this->color['b'] = hexdec(substr($this->config['color'], 4, 2));
    }

    private function resize($image, $newwidth = 100, $newheight = 100, $uri = 'image/')
    {
        $water = $this->water;
        $k = 1;
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

        // Пропорциональное уменьшение изображения
        $tmp = $srcW / $newwidth;
        $tmp2 = $srcH / $newheight;
        $k = $tmp2;
        if ($tmp > $tmp2) $k = $tmp;
        // Подсчет новой высоты и ширины
        $h2 = $srcH / $k;
        $w2 = $srcW / $k;
        $newi = imagecreatetruecolor($w2, $h2);
        $color = imagecolorallocate($newi, $this->color['r'], $this->color['g'], $this->color['b']);
        imagefilledrectangle($newi, 0, 0, $w2, $h2, $color);
        imagecopyresampled($newi, $img, 0, 0, 0, 0, $w2, $h2, $srcW, $srcH);
        if ($w2 != $newwidth OR $h2 != $newheight) {
            // Если картинка была уменьшина в пропорциях
            // То она будет доведена до требуемого размера
            $newi2 = imagecreatetruecolor($newwidth, $newheight);
            imagesavealpha($newi2, true);
            $margeX = ($newwidth - $w2) / 2;
            $margeY = ($newheight - $h2) / 2;
            $color = imagecolorallocate($newi2, $this->color['r'], $this->color['g'], $this->color['b']);
            imagefilledrectangle($newi2, 0, 0, $newwidth, $newheight, $color);
            imagecopy($newi2, $newi, $margeX, $margeY, 0, 0, $w2, $h2);
            $newi = $newi2;
        }
        $tmp = explode('*', $this->config['sizemin']);
        if ($tmp[0] < $newwidth AND $tmp[1] < $newheight) {
            if ($water != NULL AND $newheight > 40) {
                $tmp = ($newwidth - 10) / strlen($water);
                if (floor($tmp) >= 20) {
                    $fontSize = 20;
                } elseif (floor($tmp) <= 6) {
                    $fontSize = 0;
                } else {
                    $fontSize = floor($tmp);
                }
                if ($fontSize > 5) {
                    // Вывод текста на картинку
                    $white = imagecolorallocatealpha($newi, 250, 250, 250, 75);
                    $gray = imagecolorallocatealpha($newi, 100, 100, 100, 75);
                    $font = $this->config['font']; // Шрифт
                    $bbox = imagettfbbox($fontSize, 45, $font, $water); // определяем размер картинки со шрифтом

                    $x = $newwidth - ($bbox[2] - $bbox[0] + 10); // отступ с левого края
                    $y = $newheight - ($bbox[1] - $bbox[7] + 10); // отступ с нижнего края
                    imagettftext($newi, $fontSize + 2, 45, $x - 2, $y + 2, $gray, $font, $water); // вывод текста на картинку
                    imagettftext($newi, $fontSize, 45, $x, $y, $white, $font, $water); // вывод текста на картинку
                }

            }
        }
        switch ($extension) {
            case 'gif':
                imagegif($newi, $uri . $i['filename'] . '_' . $newheight . '.' . $extension);
                break;
            case 'jpg':
            case 'jpeg':
                imagejpeg($newi, $uri . $i['filename'] . '_' . $newheight . '.' . $extension);
                break;
            case 'png':
                imagepng($newi, $uri . $i['filename'] . '_' . $newheight . '.' . $extension);
                break;
            default:
                break;
        }

    }

}