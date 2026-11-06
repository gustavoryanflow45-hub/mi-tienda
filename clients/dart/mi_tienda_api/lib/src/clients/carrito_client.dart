// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:dio/dio.dart';
import 'package:retrofit/retrofit.dart';

import '../models/cart_envelope.dart';
import '../models/cart_items_item_request_body.dart';
import '../models/cart_items_request_body.dart';

part 'carrito_client.g.dart';

@RestApi()
abstract class CarritoClient {
  factory CarritoClient(Dio dio, {String? baseUrl}) = _CarritoClient;

  /// Carrito del usuario
  @GET('/cart')
  Future<CartEnvelope> showCart();

  /// Vacía el carrito
  @DELETE('/cart')
  Future<void> clearCart();

  /// Añade una combinación (suma si la línea ya existe, con tope en el stock)
  @POST('/cart/items')
  Future<CartEnvelope> addCartItem({
    @Body() required CartItemsRequestBody body,
  });

  /// Cambia la cantidad (limitada al stock)
  @PATCH('/cart/items/{item}')
  Future<CartEnvelope> updateCartItem({
    @Path('item') required int item,
    @Body() required CartItemsItemRequestBody body,
  });

  /// Quita una línea del carrito
  @DELETE('/cart/items/{item}')
  Future<CartEnvelope> removeCartItem({
    @Path('item') required int item,
  });
}
