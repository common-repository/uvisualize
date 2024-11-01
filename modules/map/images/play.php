<?php
  header('Content-type: image/svg+xml');
  $bgcolor = isset( $_GET["col"] ) ? htmlspecialchars($_GET["col"]) : "#000000";
  echo '<?xml version="1.0" encoding="utf-8" ?>';
?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="5px"
     width="512px" height="512px" viewBox="0 0 512 512" xml:space="preserve">
<path id="video-play-3-icon" d="M256,92.481c44.433,0,86.18,17.068,117.553,48.064C404.794,171.411,422,212.413,422,255.999
    s-17.206,84.588-48.448,115.455c-31.372,30.994-73.12,48.064-117.552,48.064s-86.179-17.07-117.552-48.064
    C107.206,340.587,90,299.585,90,255.999s17.206-84.588,48.448-115.453C169.821,109.55,211.568,92.481,256,92.481 M256,52.481
    c-113.771,0-206,91.117-206,203.518c0,112.398,92.229,203.52,206,203.52c113.772,0,206-91.121,206-203.52
    C462,143.599,369.772,52.481,256,52.481L256,52.481z M206.544,357.161V159.833l160.919,98.666L206.544,357.161z" style="fill:#<?php echo $bgcolor; ?>;"/>
</svg>