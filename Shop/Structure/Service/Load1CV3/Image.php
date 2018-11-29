<?php

namespace Shop\Structure\Service\Load1CV3;

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
            case 'bmp':
                $img = $this->imageCreateFromBMP($image);
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
            case 'gif':
                imagegif($newImage, $uri . $i['filename'] . '.gif');
                break;
            case 'png':
                imagepng($newImage, $uri . $i['filename'] . '.png');
                break;
            case 'bnp':
                $this->imageBMP($newImage, $uri . $i['filename'] . '.bmp');
                break;
            default:
                break;
        }
    }

    private function imageCreateFromBMP($p_sFile)
    {
        $file = fopen($p_sFile, "rb");
        $read = fread($file, 10);
        while (!feof($file) && ($read <> ""))
            $read .= fread($file, 1024);
        $temp = unpack("H*", $read);
        $hex = $temp[1];
        $header = substr($hex, 0, 108);
        if (substr($header, 0, 4) == "424d") {
            $header_parts = str_split($header, 2);
            $width = hexdec($header_parts[19] . $header_parts[18]);
            $height = hexdec($header_parts[23] . $header_parts[22]);
            unset($header_parts);
        }
        $x = 0;
        $y = 1;
        $image = imagecreatetruecolor($width, $height);
        $body = substr($hex, 108);
        $body_size = (strlen($body) / 2);
        $header_size = ($width * $height);
        $usePadding = ($body_size > ($header_size * 3) + 4);
        for ($i = 0; $i < $body_size; $i += 3) {
            if ($x >= $width) {
                if ($usePadding)
                    $i += $width % 4;
                $x = 0;
                $y++;
                if ($y > $height)
                    break;
            }
            $i_pos = $i * 2;
            $r = hexdec($body[$i_pos + 4] . $body[$i_pos + 5]);
            $g = hexdec($body[$i_pos + 2] . $body[$i_pos + 3]);
            $b = hexdec($body[$i_pos] . $body[$i_pos + 1]);
            $color = imagecolorallocate($image, $r, $g, $b);
            imagesetpixel($image, $x, $height - $y, $color);
            $x++;
        }
        unset($body);
        return $image;
    }

    private function imageBMP(&$img, $filename = false)
    {
        $wid = imagesx($img);
        $hei = imagesy($img);
        $wid_pad = str_pad('', (4 - ceil($wid / 8) % 4) % 4, "\0");

        $size = 62 + (ceil($wid / 8) + strlen($wid_pad)) * $hei;

        //prepare & save header
        $header['identifier'] = 'BM';
        $header['file_size'] = $this->dword($size);
        $header['reserved'] = $this->dword(0);
        $header['bitmap_data'] = $this->dword(62);
        $header['header_size'] = $this->dword(40);
        $header['width'] = $this->dword($wid);
        $header['height'] = $this->dword($hei);
        $header['planes'] = $this->word(1);
        $header['bits_per_pixel'] = $this->word(1);
        $header['compression'] = $this->dword(0);
        $header['data_size'] = $this->dword(0);
        $header['h_resolution'] = $this->dword(0);
        $header['v_resolution'] = $this->dword(0);
        $header['colors'] = $this->dword(0);
        $header['important_colors'] = $this->dword(0);
        $header['white'] = chr(255) . chr(255) . chr(255) . chr(0);
        $header['black'] = chr(0) . chr(0) . chr(0) . chr(0);

        if ($filename) {
            $f = fopen($filename, "wb");
            foreach ($header AS $h) {
                fwrite($f, $h);
            }

            for ($y = $hei - 1; $y >= 0; $y--) {
                $str = '';
                for ($x = 0; $x < $wid; $x++) {
                    $rgb = imagecolorat($img, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $gs = (($r * 0.299) + ($g * 0.587) + ($b * 0.114));
                    if ($gs > 150) $color = 0;
                    else $color = 1;
                    $str = $str . $color;
                    if ($x == $wid - 1) {
                        $str = str_pad($str, 8, "0");
                    }
                    if (strlen($str) == 8) {
                        fwrite($f, chr((int)bindec($str)));
                        $str = '';
                    }
                }
                fwrite($f, $wid_pad);
            }
            fclose($f);

        } else {
            foreach ($header AS $h) {
                echo $h;
            }

            for ($y = $hei - 1; $y >= 0; $y--) {
                $str = '';
                for ($x = 0; $x < $wid; $x++) {
                    $rgb = imagecolorat($img, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $gs = (($r * 0.299) + ($g * 0.587) + ($b * 0.114));
                    if ($gs > 150) $color = 0;
                    else $color = 1;
                    $str = $str . $color;
                    if ($x == $wid - 1) {
                        $str = str_pad($str, 8, "0");
                    }
                    if (strlen($str) == 8) {
                        echo chr((int)bindec($str));
                        $str = '';
                    }
                }
                echo $wid_pad;
            }
        }
    }


    private function dword($n)
    {
        return pack("V", $n);
    }

    private function word($n)
    {
        return pack("v", $n);
    }
}
