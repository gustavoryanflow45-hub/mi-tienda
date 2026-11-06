// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'delivery_status.dart';
import 'nullable_date_time.dart';
import 'order_item.dart';
import 'order_payment_gateway.dart';
import 'order_payment_status.dart';
import 'shipping_address.dart';
import 'totals.dart';
import 'tracking.dart';

part 'order.g.dart';

@JsonSerializable()
class Order {
  const Order({
    required this.id,
    required this.code,
    required this.paymentStatus,
    required this.paymentStatusLabel,
    required this.paymentGateway,
    required this.deliveryStatus,
    required this.deliveryStatusLabel,
    required this.currency,
    required this.totals,
    required this.shippingAddress,
    required this.items,
    required this.tracking,
    required this.createdAt,
    required this.paidAt,
  });
  
  factory Order.fromJson(Map<String, Object?> json) => _$OrderFromJson(json);
  
  final int id;
  final String code;
  @JsonKey(name: 'payment_status')
  final OrderPaymentStatus paymentStatus;
  @JsonKey(name: 'payment_status_label')
  final String paymentStatusLabel;
  @JsonKey(name: 'payment_gateway')
  final OrderPaymentGateway? paymentGateway;
  @JsonKey(name: 'delivery_status')
  final DeliveryStatus deliveryStatus;
  @JsonKey(name: 'delivery_status_label')
  final String deliveryStatusLabel;
  final String currency;
  final Totals totals;
  @JsonKey(name: 'shipping_address')
  final ShippingAddress shippingAddress;
  final List<OrderItem> items;
  final Tracking tracking;
  @JsonKey(name: 'created_at')
  final NullableDateTime createdAt;
  @JsonKey(name: 'paid_at')
  final NullableDateTime paidAt;

  Map<String, Object?> toJson() => _$OrderToJson(this);
}
