<?php

/*
|--------------------------------------------------------------------------
| Optimización de imágenes subidas
|--------------------------------------------------------------------------
|
| App\Services\ImageOptimizer lee de aquí cómo guardar cada tipo de imagen
| que sube un usuario: la reduce para que su lado mayor no pase de `max`
| píxeles, endereza las fotos de móvil según su EXIF y la recodifica en
| WebP (JPEG o PNG si GD no trae WebP) con la `quality` indicada.
|
| Una foto de teléfono de 4000×3000 pesa 3–6 MB; la ficha nunca la pinta a
| más de ~400px de alto, y las tarjetas a ~300px de ancho (×2 en pantallas
| retina). Servirla tal cual era el grueso del peso de cada página.
|
| Sin la extensión GD el servicio guarda el archivo original sin tocarlo,
| así que la subida no se rompe en un servidor que no la tenga.
|
*/

return [

    // Apagarlo guarda los archivos tal cual, como antes de existir el servicio.
    'enabled' => env('IMAGE_OPTIMIZE', true),

    // Formato de salida preferido: 'webp' o 'jpeg'. Si es 'webp' y GD no lo
    // soporta, un JPEG sale en JPEG y lo demás en PNG (por la transparencia).
    'format' => env('IMAGE_FORMAT', 'webp'),

    'profiles' => [
        // Tarjetas, carrito, miniaturas de la galería.
        'product_thumbnail' => ['max' => 800,  'quality' => 80],

        // Galería de la ficha, que también se amplía al pasar el ratón.
        'product_photo' => ['max' => 1600, 'quality' => 80],

        // Se pinta a 20–80px.
        'avatar' => ['max' => 400,  'quality' => 80],

        // Los revisa un admin para aprobar la tienda: tienen que poder
        // leerse, así que se reducen menos y con más calidad.
        'id_document' => ['max' => 2000, 'quality' => 85],

        // Comprobante de recarga: mismo criterio que el documento de identidad.
        // Los PDF pasan sin tocar.
        'payment_proof' => ['max' => 2000, 'quality' => 85],

        // Los tres siguientes no tienen formulario de subida: solo los usa
        // `php artisan images:optimize` sobre el contenido ya cargado.

        // Slider del inicio y banners promocionales, a todo el ancho.
        'banner' => ['max' => 1600, 'quality' => 80],

        // Icono del menú (16px) y tarjeta de categoría (~80px de alto).
        'category' => ['max' => 800,  'quality' => 80],

        // Logo de marca, pintado a 60px de alto.
        'brand_logo' => ['max' => 400,  'quality' => 85],
    ],

];
