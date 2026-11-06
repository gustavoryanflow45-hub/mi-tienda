// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'notification.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

Notification _$NotificationFromJson(Map<String, dynamic> json) => Notification(
  id: json['id'] as String,
  type: NotificationType.fromJson(json['type'] as String),
  message: json['message'] as String?,
  payload: json['payload'],
  read: json['read'] as bool,
  readAt: json['read_at'] == null
      ? null
      : DateTime.parse(json['read_at'] as String),
  createdAt: json['created_at'] == null
      ? null
      : DateTime.parse(json['created_at'] as String),
);

Map<String, dynamic> _$NotificationToJson(Notification instance) =>
    <String, dynamic>{
      'id': instance.id,
      'type': _$NotificationTypeEnumMap[instance.type]!,
      'message': instance.message,
      'payload': instance.payload,
      'read': instance.read,
      'read_at': instance.readAt?.toIso8601String(),
      'created_at': instance.createdAt?.toIso8601String(),
    };

const _$NotificationTypeEnumMap = {
  NotificationType.orderStatusUpdated: 'order_status_updated',
  NotificationType.sellerNewOrder: 'seller_new_order',
  NotificationType.sellerSettled: 'seller_settled',
  NotificationType.shopStatusUpdated: 'shop_status_updated',
  NotificationType.other: 'other',
  NotificationType.$unknown: r'$unknown',
};
