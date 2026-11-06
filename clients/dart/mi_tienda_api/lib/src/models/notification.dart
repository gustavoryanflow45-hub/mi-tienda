// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'notification_type.dart';
import 'nullable_date_time.dart';

part 'notification.g.dart';

@JsonSerializable()
class Notification {
  const Notification({
    required this.id,
    required this.type,
    required this.message,
    required this.payload,
    required this.read,
    required this.readAt,
    required this.createdAt,
  });
  
  factory Notification.fromJson(Map<String, Object?> json) => _$NotificationFromJson(json);
  
  final String id;

  /// order_status_updated → payload.order_id, order_code, status;.
  /// seller_new_order → payload.order_id, order_code, total;.
  /// seller_settled → payload.settlement_id, total_sales, commission, net_amount;.
  /// shop_status_updated → payload.shop_id, shop_name, status, approved.
  ///
  final NotificationType type;

  /// Texto guardado al crearse, en el idioma por defecto de la tienda.
  final String? message;

  /// Ids y datos para navegar; según `type`.
  final dynamic payload;
  final bool read;
  @JsonKey(name: 'read_at')
  final NullableDateTime readAt;
  @JsonKey(name: 'created_at')
  final NullableDateTime createdAt;

  Map<String, Object?> toJson() => _$NotificationToJson(this);
}
