// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'dart:convert';
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:retrofit/retrofit.dart';

import '../models/delivery_status.dart';
import '../models/enum0.dart';
import '../models/get_seller_dashboard_response.dart';
import '../models/get_seller_orders_order_response.dart';
import '../models/get_seller_orders_response.dart';
import '../models/get_seller_products_options_response.dart';
import '../models/get_seller_products_product_response.dart';
import '../models/get_seller_products_response.dart';
import '../models/post_seller_orders_order_confirm_response.dart';
import '../models/post_seller_orders_order_send_to_warehouse_response.dart';
import '../models/post_seller_products_product_toggle_published_response.dart';
import '../models/post_seller_products_response.dart';
import '../models/put_seller_products_product_response.dart';
import '../models/stock_input.dart';

part 'vendedor_client.g.dart';

@RestApi()
abstract class VendedorClient {
  factory VendedorClient(Dio dio, {String? baseUrl}) = _VendedorClient;

  /// Resumen del vendedor: ventas del ciclo, pendientes y última liquidación
  @GET('/seller/dashboard')
  Future<GetSellerDashboardResponse> sellerDashboard();

  /// Categorías, marcas, tipos de talla y colores para el formulario
  @GET('/seller/products/options')
  Future<GetSellerProductsOptionsResponse> sellerProductOptions();

  /// Productos del vendedor, paginados
  @GET('/seller/products')
  Future<GetSellerProductsResponse> sellerListProducts({
    @Query('q') String? q,
    @Query('per_page') int? perPage,
    @Query('page') int? page,
  });

  /// Crea un producto con sus combinaciones e imágenes.
  ///
  /// [discountType] - Name not received and was auto-generated.
  ///
  /// [stocks] - En multipart: stocks[0][size], stocks[0][color], stocks[0][price], stocks[0][qty], stocks[0][sku]….
  ///
  /// [thumbnail] - Imagen principal (jpg, png o webp).
  ///
  /// [photos] - Hasta 5 fotos.
  @MultiPart()
  @POST('/seller/products')
  Future<PostSellerProductsResponse> sellerCreateProduct({
    @Part(name: 'name') required String name,
    @Part(name: 'category_id') required int categoryId,
    @Part(name: 'unit_price') required num unitPrice,
    @Part(name: 'unit') required String unit,
    @Part(name: 'stocks') required List<StockInput> stocks,
    @Part(name: 'thumbnail') required File thumbnail,
    @Part(name: 'brand_id') int? brandId,
    @Part(name: 'purchase_price') num? purchasePrice,
    @Part(name: 'discount') num? discount,
    @Part(name: 'discount_type') Enum0? discountType,
    @Part(name: 'variant_type') String? variantType,
    @Part(name: 'short_description') String? shortDescription,
    @Part(name: 'description') String? description,
    @Part(name: 'min_qty') int? minQty,
    @Part(name: 'low_stock_qty') int? lowStockQty,
    @Part(name: 'shipping_cost') num? shippingCost,
    @Part(name: 'published') bool? published,
    @Part(name: 'featured') bool? featured,
    @Part(name: 'todays_deal') bool? todaysDeal,
    @Part(name: 'photos') List<File>? photos,
  });

  /// Un producto del vendedor con su stock
  @GET('/seller/products/{product}')
  Future<GetSellerProductsProductResponse> sellerShowProduct({
    @Path('product') required int product,
  });

  /// Edición; lo que no se manda se conserva.
  ///
  /// Con imágenes, `multipart/form-data` enviado como **POST con.
  /// `_method=PUT`** (PHP no lee archivos en un PUT). `stocks`, si llega,.
  /// reemplaza todas las combinaciones; `photos[]` reemplaza todas las fotos.
  @MultiPart()
  @PUT('/seller/products/{product}')
  Future<PutSellerProductsProductResponse> sellerUpdateProduct({
    @Path('product') required int product,
  });

  /// Publica o despublica un producto
  @POST('/seller/products/{product}/toggle-published')
  Future<PostSellerProductsProductTogglePublishedResponse> sellerTogglePublished({
    @Path('product') required int product,
  });

  /// Ventas pagadas con líneas del vendedor
  @GET('/seller/orders')
  Future<GetSellerOrdersResponse> sellerListOrders({
    @Query('delivery_status') DeliveryStatus? deliveryStatus,
    @Query('code') String? code,
    @Query('per_page') int? perPage,
    @Query('page') int? page,
  });

  /// Una venta: las líneas del vendedor y los datos del comprador
  @GET('/seller/orders/{order}')
  Future<GetSellerOrdersOrderResponse> sellerShowOrder({
    @Path('order') required int order,
  });

  /// pending → confirmed
  @POST('/seller/orders/{order}/confirm')
  Future<PostSellerOrdersOrderConfirmResponse> sellerConfirmOrder({
    @Path('order') required int order,
  });

  /// confirmed → warehouse
  @POST('/seller/orders/{order}/send-to-warehouse')
  Future<PostSellerOrdersOrderSendToWarehouseResponse> sellerSendToWarehouse({
    @Path('order') required int order,
  });
}
