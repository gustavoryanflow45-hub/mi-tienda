// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'notifications_meta.g.dart';

@JsonSerializable()
class NotificationsMeta {
  const NotificationsMeta({
    required this.unreadCount,
  });
  
  factory NotificationsMeta.fromJson(Map<String, Object?> json) => _$NotificationsMetaFromJson(json);
  
  /// No leídas en total (con o sin el filtro).
  @JsonKey(name: 'unread_count')
  final int unreadCount;

  Map<String, Object?> toJson() => _$NotificationsMetaToJson(this);
}
