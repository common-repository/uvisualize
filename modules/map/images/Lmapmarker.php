<?php
    header('Content-type: image/svg+xml');
    // #4796D2
    $bgcolor = htmlspecialchars($_GET["col"]);
    if ($bgcolor == "") {
       $bgcolor = "4796D2";
    }
    $bgcolor = "#".$bgcolor;
    // make darker border
    function adjustBrightness($hex, $steps) {
        $steps = max(-255, min(255, $steps));

        // Normalize into a six character long hex string
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
        }

        // Split into three parts: R, G and B
        $color_parts = str_split($hex, 2);
        $return = '#';

        foreach ($color_parts as $color) {
            $color   = hexdec($color); // Convert to decimal
            $color   = max(0,min(255,$color + $steps)); // Adjust color
            $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
        }

        return $return;
    }
    $bordercol = adjustBrightness($bgcolor,-50);
?>
<?php
    echo '<?xml version="1.0" encoding="utf-8"?>'
?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1 Tiny//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11-tiny.dtd">
<svg version="1.1" baseProfile="tiny" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
	 x="0px" y="0px" width="25px" height="41px" viewBox="0 0 25 41" xml:space="preserve">
<path fill="<?php echo $bgcolor ?>" stroke="<?php echo $bordercol ?>" stroke-miterlimit="10" d="M24.132,12.465c0-6.403-5.208-11.593-11.632-11.593
	S0.868,6.063,0.868,12.465c0,1.699,0.375,3.308,1.035,4.761H1.9L12.5,40l10.6-22.773h-0.003
	C23.756,15.773,24.132,14.164,24.132,12.465z M12.5,17.227c-2.638,0-4.777-2.132-4.777-4.761S9.862,7.704,12.5,7.704
	c2.638,0,4.777,2.132,4.777,4.762S15.138,17.227,12.5,17.227z"/>
<circle fill="#FFFFFF" cx="12.5" cy="12.503" r="4.777"/>
</svg>
