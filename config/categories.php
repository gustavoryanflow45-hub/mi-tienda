<?php

/*
|--------------------------------------------------------------------------
| Catálogo de categorías de la tienda
|--------------------------------------------------------------------------
|
| Fuente única del árbol de categorías raíz. Category::syncCatalog() lo
| aplica a la tabla: lo llaman la migración 2026_10_09_120000 y también
| legacy:restore, porque cada repoblado desde la MySQL heredada vuelve a
| traer las 10 categorías viejas en inglés ("Women's Fashion", "Toy"...).
|
| Cada entrada es slug => opciones:
|   - name:    nombre visible (es un dato, no pasa por __()).
|   - legacy:  slugs de categorías viejas que se convierten en esta. Se
|              renombran en su sitio, conservando id, imagen y productos,
|              en vez de borrarse: products.category_id sigue apuntando bien.
|
| El orden del array es el orden del menú. Una categoría que exista en la
| tabla y no figure aquí no se borra (podría tener productos); solo queda al
| final del orden.
|
| "Destacados" no está aquí a propósito: no es una categoría donde un
| vendedor pueda colgar un producto, sino la vista de los productos con
| featured = 1, y la página de categorías la pinta como primera pestaña.
|
| El tipo de talla de cada una vive en config('variants.category_types').
|
*/

return [

    'catalog' => [
        'hogar-y-cocina'                   => ['name' => 'Hogar y cocina', 'legacy' => ['home-decoration']],
        'ropa-de-mujer'                    => ['name' => 'Ropa de mujer', 'legacy' => ['womens-fashion']],
        'calzado-de-mujer'                 => ['name' => 'Calzado de mujer'],
        'lenceria-y-pijamas-de-mujer'      => ['name' => 'Lencería y pijamas de mujer'],
        'ropa-de-hombre'                   => ['name' => 'Ropa de hombre', 'legacy' => ['mens-fashion']],
        'calzado-de-hombre'                => ['name' => 'Calzado de hombre'],
        'hombre-de-talla-grande'           => ['name' => 'Hombre de talla grande'],
        'ropa-interior-y-pijamas-de-hombre' => ['name' => 'Ropa interior y pijamas de hombre'],
        'deporte-y-aire-libre'             => ['name' => 'Deporte y aire libre', 'legacy' => ['sports-outdoor']],
        'joyeria-y-accesorios'             => ['name' => 'Joyería y accesorios', 'legacy' => ['jewelry-watches']],
        'belleza-y-cuidado-personal'       => ['name' => 'Belleza y cuidado personal', 'legacy' => ['beauty-health-hair']],
        'juguetes'                         => ['name' => 'Juguetes', 'legacy' => ['toys']],
        'automotriz'                       => ['name' => 'Automotriz'],
        'moda-infantil'                    => ['name' => 'Moda infantil'],
        'calzado-de-ninos'                 => ['name' => 'Calzado de niños'],
        'bebe-y-maternidad'                => ['name' => 'Bebé y maternidad'],
        'bolsos-y-equipaje'                => ['name' => 'Bolsos y equipaje'],
        'patio-cesped-y-jardin'            => ['name' => 'Patio, césped y jardín'],
        'manualidades'                     => ['name' => 'Manualidades'],
        'tecnologia'                       => ['name' => 'Tecnología', 'legacy' => ['electronics']],
        'negocios-industria-y-ciencia'     => ['name' => 'Negocios, industria y ciencia'],
        'herramientas-y-hogar'             => ['name' => 'Herramientas y hogar'],
        'electrodomesticos'                => ['name' => 'Electrodomésticos'],
        'oficina-y-escuela'                => ['name' => 'Oficina y escuela', 'legacy' => ['computer-accessories']],
        'salud-y-hogar'                    => ['name' => 'Salud y hogar'],
        'mascotas'                         => ['name' => 'Mascotas'],
        'telefonos-y-accesorios'           => ['name' => 'Teléfonos y accesorios', 'legacy' => ['mobile-phones']],
        'hogar-inteligente'                => ['name' => 'Hogar inteligente'],
        'instrumentos-musicales'           => ['name' => 'Instrumentos musicales'],
        'comida-y-despensa'                => ['name' => 'Comida y despensa'],
        'ropa-de-playa'                    => ['name' => 'Ropa de playa'],
        'muebles'                          => ['name' => 'Muebles'],
        'regalos-y-artesanias'             => ['name' => 'Regalos y artesanías'],
        'construccion-y-bienes-raices'     => ['name' => 'Construcción y bienes raíces'],
        'ecuador-productos'                => ['name' => 'Ecuador productos'],
    ],

];
