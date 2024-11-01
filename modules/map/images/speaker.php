<?php
  header('Content-type: image/svg+xml');
  $bgcolor = htmlspecialchars($_GET["col"]);
  echo '<?xml version="1.0" encoding="utf-8" ?>';
?><svg
xmlns:dc="http://purl.org/dc/elements/1.1/"
xmlns:cc="http://web.resource.org/cc/"
xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
xmlns:svg="http://www.w3.org/2000/svg"
xmlns="http://www.w3.org/2000/svg"
xml:space="preserve"
version="1.0"
id="layer1"
width="400pt" height="400pt"
viewBox="6 11 75 75"><metadata
id="metadata1"><rdf:RDF><cc:Work
rdf:about=""><dc:format>image/svg+xml</dc:format><dc:type
rdf:resource="http://purl.org/dc/dcmitype/StillImage" /></cc:Work></rdf:RDF></metadata><g
id="g1"><polygon
id="polygon1"
points="39.389,13.769 22.235,28.606 6,28.606 6,47.699 21.989,47.699 39.389,62.75 39.389,13.769"
style="stroke:<?php echo $bgcolor ?>;stroke-width:5;stroke-linejoin:round;fill:#<?php echo $bgcolor ?>;"
/><path id="path1"
d="M 48.128,49.03 C 50.057,45.934 51.19,42.291 51.19,38.377 C 51.19,34.399 50.026,30.703 48.043,27.577"
style="fill:none;stroke:#<?php echo $bgcolor ?>;stroke-width:5;stroke-linecap:round"/>
<path id="path2"
d="M 55.082,20.537 C 58.777,25.523 60.966,31.694 60.966,38.377 C 60.966,44.998 58.815,51.115 55.178,56.076"
style="fill:none;stroke:#<?php echo $bgcolor ?>;stroke-width:5;stroke-linecap:round"/>
<path id="path1"
d="M 61.71,62.611 C 66.977,55.945 70.128,47.531 70.128,38.378 C 70.128,29.161 66.936,20.696 61.609,14.01"
style="fill:none;stroke:#<?php echo $bgcolor ?>;stroke-width:5;stroke-linecap:round"/>
</g>
</svg>