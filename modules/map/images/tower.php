<?php
  header('Content-type: image/svg+xml');
  $bgcolor = isset( $_GET["col"] ) ? htmlspecialchars($_GET["col"]) : "000000";
  echo '<?xml version="1.0" encoding="utf-8" ?>';
?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" width="1024px" height="1024px" viewBox="0 0 1024 1024" enable-background="new 0 0 1024 1024" xml:space="preserve">
<path d="M895,944.6H777.8c-3.8-26.199-8.6-59-15.399-101.199C747.6,749.4,725.8,613.8,697,436.4V196.6h42.8L506.2,0L284.6,196.6h44  v244.2h1.4c-44.6,250.2-72.6,418-84.2,503.8H129c-22,0-39.6,17.801-39.6,39.801c0,21.8,17.6,39.6,39.6,39.6h765.8  c21.8,0,39.601-17.8,39.601-39.6C934.6,962.2,916.8,944.6,895,944.6z M699,857.6L555.2,695L651,586.4L699,857.6z M515,659.8  L385.6,515.6L395.4,450.2L627.6,449.6l11.2,68.801L515,659.8z M381.6,197h261v82.4h-261V197z M370.2,573.8L482,698.2L312.8,886.6  L370.2,573.8z M324.4,944.6l189.8-207l187,207H324.4z" fill="#<?php echo $bgcolor; ?>"/>
</svg>