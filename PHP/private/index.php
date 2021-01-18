<?php
    $devices = [];
    $LOGDIR = __DIR__.'/../log/';
    $PERIOD = '30 minutes';

    $files = scandir($LOGDIR);
    foreach ( $files as $file ) {
        if ( strpos($file, '.'.date('Ymd')) !== false ) {
            $data = file_get_contents($LOGDIR.$file);
            $json = json_decode($data, true);
            if ( !array_key_exists($json['app_id'].'__'.$json['dev_id'], $devices) ) {
                $devices[$json['app_id'].'__'.$json['dev_id']] = [];
            }
            $devices[$json['app_id'].'__'.$json['dev_id']][$json['metadata']['time']] = $json['payload_fields'];
        }
    }
?>
<html>
<head>
  <meta charset="utf-8">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" integrity="sha512-Rksm5RenBEKSKFjgI3a41vrjkw4EVPlJ3+OiI65vTjIdo9brlAacEuKOiQ5OFh7cOI1bkDwLqdLw3Zg0cRJAAQ==" crossorigin="" />
  <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" integrity="sha512-/Nsx9X4HebavoBvEBuyp3I7od5tA0UzAxs+j83KgC8PU0kgB4XiK4Lfe4y4cgBtaRJQEIFCW+oC506aPT2L1zw==" crossorigin=""></script>
  <title>Device finder</title>
  <style type="text/css">
    .map { height: 75%; }
  </style>
</head>
<body>
    <?php foreach ( $devices as $device => $data ): ?>
    <h1><?php echo $device ?></h1>
    <div id="<?php echo $device ?>" class="map"></div>
    <script type="text/javascript">
        var points = {<?php
            foreach ( $data as $date => $point ) {
                $date = preg_replace(['/T/', '/\..*$/'], [' ', ''], $date).' UTC';
                if ( $point['Position']['Latitude'] == 0 && $point['Position']['Longitude'] == 0 ) {
                    continue;
                }
                if ( strtotime($date) < strtotime($PERIOD.' ago') ) {
                    continue;
                }
                echo '"'.$date.'":{ lat: '.$point['Position']['Latitude'].', lon: '.$point['Position']['Longitude'].', comment: "Date et heure: '.date('H:i:s',strtotime($date)).', Charge: '.$point['BatCharge'].', Mouvement: '.$point['MotionDetection'].', Alarme: '.$point['alarme'].'" },'."\n";
            }
        ?>};
        window.onload = function(){
            macarte = L
                .map('<?php echo $device ?>')
                .setView([<?php echo $point['Position']['Latitude'] ?>, <?php echo $point['Position']['Longitude'] ?>], 16)
            ;
            L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
                attribution: 'données © <a href="//osm.org/copyright">OpenStreetMap</a>/ODbL - rendu <a href="//openstreetmap.fr">OSM France</a>',
                minZoom: 1,
                maxZoom: 18
            }).addTo(macarte);
            
            var markers = [];
            var size = 5;
            for (point in points) {
                size += 1;
                var marker = L.marker( [points[point].lat, points[point].lon], { icon: L.icon({
                    iconSize: [size, size],
                    iconUrl: './dog.png',
                    origin: {x: 256, y: 0},
                    anchor: {x: "-10px", y: "-32px"}
                }) } )
                    .addTo(macarte);
                marker.bindPopup(points[point].comment);
                markers.push(marker);
            }
            
            // zoom adaptatif
            var group = new L.featureGroup(markers);
            macarte.fitBounds(group.getBounds().pad(0.5));
       };
    </script>
    <?php endforeach ?>
</body>
</html>
