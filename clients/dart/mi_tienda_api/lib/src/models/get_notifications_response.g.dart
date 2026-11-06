// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_notifications_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetNotificationsResponse _$GetNotificationsResponseFromJson(
  Map<String, dynamic> json,
) => GetNotificationsResponse(
  links: PaginationLinks.fromJson(json['links'] as Map<String, dynamic>),
  data: (json['data'] as List<dynamic>?)
      ?.map((e) => Notification.fromJson(e as Map<String, dynamic>))
      .toList(),
  meta: json['meta'] == null
      ? null
      : NotificationsMeta.fromJson(json['meta'] as Map<String, dynamic>),
);

Map<String, dynamic> _$GetNotificationsResponseToJson(
  GetNotificationsResponse instance,
) => <String, dynamic>{
  'data': instance.data,
  'links': instance.links,
  'meta': instance.meta,
};
