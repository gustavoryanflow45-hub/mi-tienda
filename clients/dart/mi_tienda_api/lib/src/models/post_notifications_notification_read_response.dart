// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'notification.dart';

part 'post_notifications_notification_read_response.g.dart';

@JsonSerializable()
class PostNotificationsNotificationReadResponse {
  const PostNotificationsNotificationReadResponse({
    required this.data,
  });
  
  factory PostNotificationsNotificationReadResponse.fromJson(Map<String, Object?> json) => _$PostNotificationsNotificationReadResponseFromJson(json);
  
  final Notification data;

  Map<String, Object?> toJson() => _$PostNotificationsNotificationReadResponseToJson(this);
}
