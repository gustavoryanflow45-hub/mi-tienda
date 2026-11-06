// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'nullable_date_time.dart';

part 'tracking.g.dart';

@JsonSerializable()
class Tracking {
  const Tracking({
    required this.confirmedAt,
    required this.warehouseAt,
    required this.dispatchedAt,
    required this.deliveredAt,
  });
  
  factory Tracking.fromJson(Map<String, Object?> json) => _$TrackingFromJson(json);
  
  @JsonKey(name: 'confirmed_at')
  final NullableDateTime confirmedAt;
  @JsonKey(name: 'warehouse_at')
  final NullableDateTime warehouseAt;
  @JsonKey(name: 'dispatched_at')
  final NullableDateTime dispatchedAt;
  @JsonKey(name: 'delivered_at')
  final NullableDateTime deliveredAt;

  Map<String, Object?> toJson() => _$TrackingToJson(this);
}
