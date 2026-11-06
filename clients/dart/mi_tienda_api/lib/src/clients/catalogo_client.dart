// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:dio/dio.dart';
import 'package:retrofit/retrofit.dart';

import '../models/get_brands_response.dart';
import '../models/get_categories_response.dart';
import '../models/get_categories_slug_response.dart';
import '../models/get_home_response.dart';
import '../models/get_products_product_response.dart';
import '../models/get_products_response.dart';
import '../models/sort.dart';

part 'catalogo_client.g.dart';

@RestApi()
abstract class CatalogoClient {
  factory CatalogoClient(Dio dio, {String? baseUrl}) = _CatalogoClient;

  /// Portada (banners y tres secciones de hasta 12 productos)
  @GET('/home')
  Future<GetHomeResponse> home();

  /// Marcas activas
  @GET('/brands')
  Future<GetBrandsResponse> listBrands();

  /// Árbol de categorías activas (raíces con sus children)
  @GET('/categories')
  Future<GetCategoriesResponse> listCategories();

  /// Una categoría con sus subcategorías y su padre
  @GET('/categories/{slug}')
  Future<GetCategoriesSlugResponse> showCategory({
    @Path('slug') required String slug,
  });

  /// Productos a la venta, paginados.
  ///
  /// [category] - Slug; incluye subcategorías.
  @GET('/products')
  Future<GetProductsResponse> listProducts({
    @Query('sort') Sort? sort = Sort.newest,
    @Query('q') String? q,
    @Query('category') String? category,
    @Query('brand') int? brand,
    @Query('min_price') num? minPrice,
    @Query('max_price') num? maxPrice,
    @Query('featured') bool? featured,
    @Query('per_page') int? perPage,
    @Query('page') int? page,
  });

  /// Ficha (por slug o por id)
  @GET('/products/{product}')
  Future<GetProductsProductResponse> showProduct({
    @Path('product') required String product,
  });
}
