// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

/// order_status_updated → payload.order_id, order_code, status;.
/// seller_new_order → payload.order_id, order_code, total;.
/// seller_settled → payload.settlement_id, total_sales, commission, net_amount;.
/// shop_status_updated → payload.shop_id, shop_name, status, approved.
///
@JsonEnum()
enum NotificationType {
  @JsonValue('order_status_updated')
  orderStatusUpdated('order_status_updated'),
  @JsonValue('seller_new_order')
  sellerNewOrder('seller_new_order'),
  @JsonValue('seller_settled')
  sellerSettled('seller_settled'),
  @JsonValue('shop_status_updated')
  shopStatusUpdated('shop_status_updated'),
  @JsonValue('other')
  other('other'),
  /// Default value for all unparsed values, allows backward compatibility when adding new values on the backend.
  $unknown(null);

  const NotificationType(this.json);

  factory NotificationType.fromJson(String json) => values.firstWhere(
        (e) => e.json == json,
        orElse: () => $unknown,
      );

  final String? json;

  @override
  String toString() => json?.toString() ?? super.toString();
  /// Returns all defined enum values excluding the $unknown value.
  static List<NotificationType> get $valuesDefined => values.where((value) => value != $unknown).toList();
}
