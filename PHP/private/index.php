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
    <h1></h1>
    <div id="map" class="map"></div>
    <script type="text/javascript">
        var colors = ['lightblue', 'lightgreen', 'orange', 'gray', 'pink'];
        var points = [];
        points.push({<?php
            foreach ( $devices as $device => $data ) {
                foreach ( $data as $date => $point ) {
                    $date = preg_replace(['/T/', '/\..*$/'], [' ', ''], $date).' UTC';
                    if ( $point['Position']['Latitude'] == 0 && $point['Position']['Longitude'] == 0 ) {
                        continue;
                    }
                    if ( strtotime($date) < strtotime($PERIOD.' ago') ) {
                        continue;
                    }
                    foreach ( ['BatCharge', 'MotionDetection', 'Alarm'] as $key ) {
                        if ( !array_key_exists($key, $point) ) {
                            $point[$key] = '';
                        }
                    }
                    echo '"'.$date.'":{ lat: '.$point['Position']['Latitude'].', lon: '.$point['Position']['Longitude'].', comment: "Date et heure: '.date('H:i:s',strtotime($date)).', Device: '.$device.', Charge: '.$point['BatCharge'].', Mouvement: '.$point['MotionDetection'].', Alarme: '.$point['Alarm'].'" },'."\n";
                }
            }
        ?>});
        window.onload = function(){
            macarte = L
                .map('map')
                .setView([<?php echo $point['Position']['Latitude'] ?>, <?php echo $point['Position']['Longitude'] ?>], 16)
            ;
            L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
                attribution: 'données © <a href="//osm.org/copyright">OpenStreetMap</a>/ODbL - rendu <a href="//openstreetmap.fr">OSM France</a>',
                minZoom: 1,
                maxZoom: 18
            }).addTo(macarte);
            
            var bounds;
            for ( i in points ) {
                var latlngs = [];
                var size = 5;
                for ( point in points[i] ) {
                    size++;
                    var marker = L.marker(
                        [points[i][point].lat, points[i][point].lon], { icon: L.icon({
                            iconSize: [size, size],
                            iconUrl: './dog.png',
                            origin: {x: 256, y: 0},
                            anchor: {x: "-10px", y: "-32px"}
                        }) }
                    ).addTo(macarte);
                    latlngs.push(marker.getLatLng());
                    marker.bindPopup(points[i][point].comment);
                }
                // polyline
                var polyline = L.polyline(latlngs, {color: colors[i]}).addTo(macarte);
                if ( bounds === undefined ) {
                    bounds = polyline.getBounds();
                    continue;
                }
                bounds.extend(polyline.getBounds());
            }
            
            // zoom adaptatif
            macarte.fitBounds(bounds.pad(1));
        };
    </script>
</body>
</html>
