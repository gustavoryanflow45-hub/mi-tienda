// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tracking.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

Tracking _$TrackingFromJson(Map<String, dynamic> json) => Tracking(
  confirmedAt: json['confirmed_at'] == null
      ? null
      : DateTime.parse(json['confirmed_at'] as String),
  warehouseAt: json['warehouse_at'] == null
      ? null
      : DateTime.parse(json['warehouse_at'] as String),
  dispatchedAt: json['dispatched_at'] == null
      ? null
      : DateTime.parse(json['dispatched_at'] as String),
  deliveredAt: json['delivered_at'] == null
      ? null
      : DateTime.parse(json['delivered_at'] as String),
);

Map<String, dynamic> _$TrackingToJson(Tracking instance) => <String, dynamic>{
  'confirmed_at': instance.confirmedAt?.toIso8601String(),
  'warehouse_at': instance.warehouseAt?.toIso8601String(),
  'dispatched_at': instance.dispatchedAt?.toIso8601String(),
  'delivered_at': instance.deliveredAt?.toIso8601String(),
};
