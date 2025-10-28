<?php
$lat = 37.7749; // Latitud del marcador
$lng = -122.4194; // Longitud del marcador
$zoom = 15; // Nivel de zoom
$width = 400; // Ancho de la imagen en píxeles
$height = 300; // Altura de la imagen en píxeles
$apiKey = 'AIzaSyC9O_0TE0QcMPctgeWm0_F3_MNz4BCvMnM'; // Tu clave de API de Google Maps

// Construir la URL del mapa estático con marcador
$url = "https://maps.googleapis.com/maps/api/staticmap?center=$lat,$lng&zoom=$zoom&size={$width}x{$height}&markers=color:red%7Clabel:A%7C$lat,$lng&key=$apiKey";

// Obtener la imagen del mapa desde la URL
$image = file_get_contents($url);

// Guardar la imagen localmente (opcional)
file_put_contents('../temp/mapa_con_marcador.png', $image);

// Mostrar la imagen en la página web
header('Content-Type: image/png');
echo $image;
