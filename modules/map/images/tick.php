<?php
  header('Content-type: image/svg+xml');
  $bgcolor = isset( $_GET["col"] ) ? htmlspecialchars($_GET["col"]) : "000000";
  echo '<?xml version="1.0" encoding="utf-8" ?>';
?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" width="512px" height="434.935px" viewBox="0 0 512 434.935" enable-background="new 0 0 512 434.935" xml:space="preserve">
<path d="M498.325,36.005L465.176,9.317c-16.352-13.132-25.844-12.983-39.833,4.3L184.337,311.024L72.188,217.842  c-15.474-12.997-25.155-12.302-37.875,3.598L8.719,254.758c-12.983,16.353-11.322,25.615,4.023,38.443l159.841,132.22  c16.453,13.827,25.716,12.396,38.442-3.078L502.484,75.825C516.177,59.372,515.353,49.542,498.325,36.005z" fill="#<?php echo $bgcolor; ?>"/>
</svg>