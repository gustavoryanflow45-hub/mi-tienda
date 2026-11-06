// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'buyer.dart';
import 'delivery_status.dart';
import 'nullable_date_time.dart';
import 'order_item.dart';
import 'seller_order_actions.dart';
import 'seller_order_payment_status.dart';
import 'shipping_address.dart';
import 'tracking.dart';

part 'seller_order.g.dart';

@JsonSerializable()
class SellerOrder {
  const SellerOrder({
    required this.id,
    required this.code,
    required this.paymentStatus,
    required this.paymentStatusLabel,
    required this.deliveryStatus,
    required this.deliveryStatusLabel,
    required this.actions,
    required this.currency,
    required this.sellerTotal,
    required this.items,
    required this.buyer,
    required this.shippingAddress,
    required this.tracking,
    required this.createdAt,
    required this.paidAt,
  });
  
  factory SellerOrder.fromJson(Map<String, Object?> json) => _$SellerOrderFromJson(json);
  
  final int id;
  final String code;
  @JsonKey(name: 'payment_status')
  final SellerOrderPaymentStatus paymentStatus;
  @JsonKey(name: 'payment_status_label')
  final String paymentStatusLabel;
  @JsonKey(name: 'delivery_status')
  final DeliveryStatus deliveryStatus;
  @JsonKey(name: 'delivery_status_label')
  final String deliveryStatusLabel;
  final SellerOrderActions actions;
  final String currency;

  /// Solo las líneas de este vendedor.
  @JsonKey(name: 'seller_total')
  final num sellerTotal;
  final List<OrderItem> items;
  final Buyer buyer;
  @JsonKey(name: 'shipping_address')
  final ShippingAddress shippingAddress;
  final Tracking tracking;
  @JsonKey(name: 'created_at')
  final NullableDateTime createdAt;
  @JsonKey(name: 'paid_at')
  final NullableDateTime paidAt;

  Map<String, Object?> toJson() => _$SellerOrderToJson(this);
}
