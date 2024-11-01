<?php
    header('Content-type: image/svg+xml');
    $bgcolor = htmlspecialchars($_GET["col"]);
    if ($bgcolor == "") {
       $bgcolor = "FFFFFF";
    }
    $bgcolor = "#".$bgcolor;
    $bordercolor = htmlspecialchars($_GET["col2"]);
    if ($bordercolor == "") {
       $bordercolor = "000000";
    }
    $bordercolor = "#".$bordercolor;

?>
<?php
    echo '<?xml version="1.0" encoding="utf-8"?>';
?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1 Tiny//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11-tiny.dtd">
<svg version="1.1" baseProfile="tiny" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
	 x="0px" y="0px" width="64px" height="128px" viewBox="0 0 64 128" xml:space="preserve">
<polygon fill="<?php echo $bgcolor ?>" stroke="<?php echo $bordercolor ?>" stroke-miterlimit="10" points="3.875,7.795 60.125,64 3.875,120.205 "/>
</svg>
