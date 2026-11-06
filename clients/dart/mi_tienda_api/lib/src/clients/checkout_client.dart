// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:dio/dio.dart';
import 'package:retrofit/retrofit.dart';

import '../models/checkout_orders_request_body.dart';
import '../models/get_checkout_orders_order_response.dart';
import '../models/get_checkout_response.dart';
import '../models/post_checkout_orders_response.dart';

part 'checkout_client.g.dart';

@RestApi()
abstract class CheckoutClient {
  factory CheckoutClient(Dio dio, {String? baseUrl}) = _CheckoutClient;

  /// Resumen, envío del perfil y pasarela sugerida (no crea nada)
  @GET('/checkout')
  Future<GetCheckoutResponse> checkoutSummary();

  /// Crea (o pone al día, con order_id) el pedido pendiente
  @POST('/checkout/orders')
  Future<PostCheckoutOrdersResponse> createOrder({
    @Body() required CheckoutOrdersRequestBody body,
  });

  /// Estado del pedido tras pagar
  @GET('/checkout/orders/{order}')
  Future<GetCheckoutOrdersOrderResponse> checkoutOrder({
    @Path('order') required int order,
  });
}
