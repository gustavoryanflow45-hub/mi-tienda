// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:dio/dio.dart';
import 'package:retrofit/retrofit.dart';

import '../models/checkout_orders_order_payphone_confirm_request_body.dart';
import '../models/checkout_orders_order_payphone_request_body.dart';
import '../models/post_checkout_orders_order_payphone_confirm_response.dart';
import '../models/post_checkout_orders_order_payphone_response.dart';
import '../models/post_checkout_orders_order_stripe_confirm_response.dart';
import '../models/post_checkout_orders_order_stripe_response.dart';

part 'pagos_client.g.dart';

@RestApi()
abstract class PagosClient {
  factory PagosClient(Dio dio, {String? baseUrl}) = _PagosClient;

  /// PaymentIntent para la PaymentSheet de flutter_stripe.
  ///
  /// Antes vuelve a sincronizar el pedido con el carrito.
  @POST('/checkout/orders/{order}/stripe')
  Future<PostCheckoutOrdersOrderStripeResponse> stripeIntent({
    @Path('order') required int order,
  });

  /// Verifica el pago en Stripe (sin esperar al webhook)
  @POST('/checkout/orders/{order}/stripe/confirm')
  Future<PostCheckoutOrdersOrderStripeConfirmResponse> stripeConfirm({
    @Path('order') required int order,
  });

  /// URL de pago de PayPhone para un navegador in-app.
  ///
  /// Antes vuelve a sincronizar el pedido con el carrito.
  @POST('/checkout/orders/{order}/payphone')
  Future<PostCheckoutOrdersOrderPayphoneResponse> payphoneStart({
    @Path('order') required int order,
    @Body() CheckoutOrdersOrderPayphoneRequestBody? body,
  });

  /// Confirma una transacción de PayPhone de este pedido
  @POST('/checkout/orders/{order}/payphone/confirm')
  Future<PostCheckoutOrdersOrderPayphoneConfirmResponse> payphoneConfirm({
    @Path('order') required int order,
    @Body() required CheckoutOrdersOrderPayphoneConfirmRequestBody body,
  });
}
