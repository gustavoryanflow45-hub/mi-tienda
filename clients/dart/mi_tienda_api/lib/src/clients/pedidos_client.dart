// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:dio/dio.dart';
import 'package:retrofit/retrofit.dart';

import '../models/delivery_status.dart';
import '../models/get_orders_order_response.dart';
import '../models/get_orders_response.dart';
import '../models/payment_status.dart';

part 'pedidos_client.g.dart';

@RestApi()
abstract class PedidosClient {
  factory PedidosClient(Dio dio, {String? baseUrl}) = _PedidosClient;

  /// Pedidos del comprador, paginados
  @GET('/orders')
  Future<GetOrdersResponse> listOrders({
    @Query('payment_status') PaymentStatus? paymentStatus,
    @Query('delivery_status') DeliveryStatus? deliveryStatus,
    @Query('code') String? code,
    @Query('per_page') int? perPage,
    @Query('page') int? page,
  });

  /// Un pedido del comprador con su seguimiento
  @GET('/orders/{order}')
  Future<GetOrdersOrderResponse> showOrder({
    @Path('order') required int order,
  });
}
