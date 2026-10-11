<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../admin/includes/cv-image-privacy.php';
$source = tempnam(sys_get_temp_dir(), 'cv-src-');
$output = tempnam(sys_get_temp_dir(), 'cv-mask-');
try {
    $image = imagecreatetruecolor(100, 200);
    imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
    imagepng($image, $source); imagedestroy($image);
    maskCvImage($source, [['x'=>.2,'y'=>.1,'w'=>.3,'h'=>.2]], $output);
    $masked = imagecreatefrompng($output);
    if (imagesx($masked)!==100 || imagesy($masked)!==200) throw new RuntimeException('Dimensions changed');
    foreach ([[20,20],[35,40],[50,60]] as [$x,$y]) if ((imagecolorat($masked,$x,$y)&0xffffff)!==0) throw new RuntimeException('Mask pixel remains visible');
    if ((imagecolorat($masked,10,10)&0xffffff)!==0xffffff) throw new RuntimeException('Unmasked content changed');
    imagedestroy($masked);
    foreach ([[], [['x'=>-.1,'y'=>0,'w'=>.1,'h'=>.1]], [['x'=>.9,'y'=>0,'w'=>.5,'h'=>.1]], [['x'=>0,'y'=>0,'w'=>0,'h'=>.1]]] as $invalid) {
        $rejected=false;
        try { maskCvImage($source,$invalid,$output); } catch (RuntimeException $error) { $rejected=true; }
        if (!$rejected) throw new RuntimeException('Invalid mask accepted');
    }
    maskCvImage($source, [['type'=>'pen','color'=>'#336699','width'=>.1,'points'=>[['x'=>.2,'y'=>.5],['x'=>.8,'y'=>.5]]]], $output);
    $masked=imagecreatefrompng($output);
    foreach ([[20,100],[50,100],[80,100]] as [$x,$y]) if ((imagecolorat($masked,$x,$y)&0xffffff)!==0x336699) throw new RuntimeException('Pen stroke/color failed');
    if ((imagecolorat($masked,50,80)&0xffffff)!==0xffffff) throw new RuntimeException('Pen affected surrounding content');
    imagedestroy($masked);
    $rejected=false;
    try { maskCvImage($source,[['type'=>'pen','color'=>'invalid','width'=>.1,'points'=>[['x'=>.2,'y'=>.5]]]],$output); } catch (RuntimeException $error) { $rejected=true; }
    if (!$rejected) throw new RuntimeException('Invalid color accepted');
    echo "PASS: solid masks, coloured pen strokes, preserved dimensions/content, invalid coordinates/colours rejected.\n";
} finally { unlink($source); unlink($output); }
