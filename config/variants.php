<?php

/*
|--------------------------------------------------------------------------
| Variantes de producto (talla y color)
|--------------------------------------------------------------------------
|
| Todo producto puede tener color. La talla depende del tipo de variante de
| su categoría (columna categories.variant_type), que apunta a una de las
| entradas de 'types'.
|
| Para dar tallas a un rubro nuevo (guantes, anillos, mochilas...) basta con
| añadir una entrada aquí y poner ese tipo en las categorías que toque:
|
|     UPDATE categories SET variant_type = 'gloves' WHERE slug = 'guantes';
|
| No hace falta migración: variant_type es un string libre y una categoría
| con un tipo desconocido simplemente se comporta como 'none' (solo color).
|
*/

return [

    // Tipo que se asume cuando la categoría no tiene variant_type asignado.
    'default_type' => 'none',

    'types' => [

        // Sin tallas: el producto solo se distingue por color.
        'none' => [
            'label' => 'Sin tallas',
            'sizes' => [],
        ],

        // Ropa en general: camisetas, pantalones, chaquetas, vestidos.
        'apparel' => [
            'label'     => 'Tallas de ropa',
            'size_label' => 'Talla',
            'sizes'     => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'],
        ],

        // Pantalones y prendas que se venden por número de cintura.
        'waist' => [
            'label'      => 'Tallas por cintura',
            'size_label' => 'Cintura',
            'sizes'      => ['28', '30', '32', '34', '36', '38', '40', '42', '44'],
        ],

        // Calzado, en tallas US.
        'footwear' => [
            'label'      => 'Tallas de calzado',
            'size_label' => 'Talla US',
            'sizes'      => [
                '5', '5.5', '6', '6.5', '7', '7.5', '8', '8.5',
                '9', '9.5', '10', '10.5', '11', '11.5', '12', '13', '14',
            ],
        ],

        // Gorras, sombreros, cascos: llevan talla propia además de color.
        'headwear' => [
            'label'      => 'Tallas de gorra o casco',
            'size_label' => 'Talla',
            'sizes'      => ['XS', 'S', 'M', 'L', 'XL', 'Ajustable'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Paleta de colores
    |--------------------------------------------------------------------------
    |
    | Fuente única para los swatches del formulario del vendedor y para los
    | chips del detalle de producto. La clave es el valor que se guarda en
    | product_stocks.color; el hex es solo presentación.
    |
    | Antes cada vista tenía su propia lista y no coincidían: el formulario
    | ofrecía beige/teal/coral/gold y el detalle, que clasificaba por nombre,
    | los tomaba por tallas.
    |
    */
    'colors' => [
        'negro'    => ['label' => 'Negro',    'hex' => '#1a1a1a'],
        'blanco'   => ['label' => 'Blanco',   'hex' => '#ffffff'],
        'gris'     => ['label' => 'Gris',     'hex' => '#95a5a6'],
        'rojo'     => ['label' => 'Rojo',     'hex' => '#e74c3c'],
        'azul'     => ['label' => 'Azul',     'hex' => '#2980b9'],
        'navy'     => ['label' => 'Azul marino', 'hex' => '#1a237e'],
        'verde'    => ['label' => 'Verde',    'hex' => '#27ae60'],
        'amarillo' => ['label' => 'Amarillo', 'hex' => '#f1c40f'],
        'naranja'  => ['label' => 'Naranja',  'hex' => '#e67e22'],
        'rosa'     => ['label' => 'Rosa',     'hex' => '#e91e8c'],
        'morado'   => ['label' => 'Morado',   'hex' => '#9b59b6'],
        'cafe'     => ['label' => 'Café',     'hex' => '#795548'],
        'beige'    => ['label' => 'Beige',    'hex' => '#f5f0e8'],
        'turquesa' => ['label' => 'Turquesa', 'hex' => '#009688'],
        'coral'    => ['label' => 'Coral',    'hex' => '#ff7043'],
        'dorado'   => ['label' => 'Dorado',   'hex' => '#ffc107'],
        'plateado' => ['label' => 'Plateado', 'hex' => '#c0c0c0'],
    ],
];
