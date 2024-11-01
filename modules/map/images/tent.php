<?php
  header('Content-type: image/svg+xml');
  $bgcolor = isset( $_GET["col"] ) ? htmlspecialchars($_GET["col"]) : "000000";
  echo '<?xml version="1.0" encoding="utf-8" ?>';
?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" width="1024px" height="1024px" viewBox="0 0 1024 1024" enable-background="new 0 0 1024 1024" xml:space="preserve">
<path d="M563,138l37.6-48.4L563.2,60.4l-30.4,39l-0.2-0.2v0.2l-30.399-39l-37.4,29.2l37.8,48.4L0,851.8h352.4  C478.4,742.2,535,602.6,535,602.6h2c0,0,55,134.4,177.4,249.2L1023.8,851L563,138z" fill="#<?php echo $bgcolor; ?>"/>
</svg>