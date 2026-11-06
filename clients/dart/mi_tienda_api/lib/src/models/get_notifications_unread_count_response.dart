// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'unread_count.dart';

part 'get_notifications_unread_count_response.g.dart';

@JsonSerializable()
class GetNotificationsUnreadCountResponse {
  const GetNotificationsUnreadCountResponse({
    required this.data,
  });
  
  factory GetNotificationsUnreadCountResponse.fromJson(Map<String, Object?> json) => _$GetNotificationsUnreadCountResponseFromJson(json);
  
  final UnreadCount data;

  Map<String, Object?> toJson() => _$GetNotificationsUnreadCountResponseToJson(this);
}
