<?php
  header('Content-type: image/svg+xml');
  $bgcolor = isset( $_GET["col"] ) ? htmlspecialchars($_GET["col"]) : "000000";
  echo '<?xml version="1.0" encoding="utf-8" ?>';
?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" width="512px" height="358.399px" viewBox="0 0 512 358.399" enable-background="new 0 0 512 358.399" xml:space="preserve">
<path d="M307.2,307.724c-28.288-28.05-74.112-28.05-102.4,0l51.2,50.676L307.2,307.724z M409.6,206.337  c-84.862-83.994-222.337-83.994-307.199,0l51.199,50.675c56.575-55.938,148.226-55.938,204.801,0L409.6,206.337z M512,104.949  c-141.438-139.932-370.562-139.932-512,0l51.2,50.675c113.149-112.013,296.45-112.013,409.6,0L512,104.949z" fill="#<?php echo $bgcolor; ?>"/>
</svg>