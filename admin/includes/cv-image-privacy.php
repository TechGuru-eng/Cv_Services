<?php
function maskCvImage(string $source, array $boxes, string $output): void {
    if (!extension_loaded('gd')) throw new RuntimeException('Activez l’extension PHP GD dans Hostinger pour masquer les images.');
    $info = getimagesize($source);
    if (!$info || $info[0] * $info[1] > 12000000) throw new RuntimeException('Image invalide ou trop grande (12 millions de pixels maximum).');
    if (!$boxes || count($boxes) > 100) throw new RuntimeException('Dessinez entre 1 et 100 zones à masquer.');
    $image = imagecreatefromstring(file_get_contents($source));
    if (!$image) throw new RuntimeException('Impossible de lire cette image.');
    try {
        $black = imagecolorallocate($image, 0, 0, 0);
        foreach ($boxes as $box) {
            $hex = $box['color'] ?? '#000000';
            if (!is_string($hex) || !preg_match('/^#[0-9a-f]{6}$/i', $hex)) throw new RuntimeException('Couleur invalide.');
            $color = imagecolorallocate($image, hexdec(substr($hex,1,2)), hexdec(substr($hex,3,2)), hexdec(substr($hex,5,2)));
            if (($box['type'] ?? '') === 'pen') {
                $width = $box['width'] ?? null;
                $points = $box['points'] ?? null;
                if (!is_numeric($width) || !is_finite((float)$width) || $width <= 0 || $width > .25 || !is_array($points) || !count($points) || count($points)>2000) throw new RuntimeException('Trait invalide.');
                $pixels = max(1, (int)round($width*$info[0]));
                imagesetthickness($image, $pixels);
                $previous = null;
                foreach ($points as $point) {
                    foreach (['x','y'] as $key) if (!isset($point[$key]) || !is_numeric($point[$key]) || !is_finite((float)$point[$key]) || $point[$key]<0 || $point[$key]>1) throw new RuntimeException('Point invalide.');
                    $x=(int)round($point['x']*$info[0]); $y=(int)round($point['y']*$info[1]);
                    if ($previous) imageline($image,$previous[0],$previous[1],$x,$y,$color);
                    imagefilledellipse($image,$x,$y,$pixels,$pixels,$color);
                    $previous=[$x,$y];
                }
                continue;
            }
            foreach (['x','y','w','h'] as $key) if (!isset($box[$key]) || !is_numeric($box[$key]) || !is_finite((float)$box[$key])) throw new RuntimeException('Zone invalide.');
            if ($box['x'] < 0 || $box['y'] < 0 || $box['w'] <= 0 || $box['h'] <= 0 || $box['x'] + $box['w'] > 1.000001 || $box['y'] + $box['h'] > 1.000001) throw new RuntimeException('Zone hors de l’image.');
            imagefilledrectangle($image, (int)floor($box['x']*$info[0]), (int)floor($box['y']*$info[1]), min($info[0]-1,(int)ceil(($box['x']+$box['w'])*$info[0])), min($info[1]-1,(int)ceil(($box['y']+$box['h'])*$info[1])), $color);
        }
        if (!imagepng($image, $output)) throw new RuntimeException('Impossible d’enregistrer l’image masquée.');
    } finally { imagedestroy($image); }
}
