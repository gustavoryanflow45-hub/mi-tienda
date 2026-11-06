// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'paginated.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

Paginated _$PaginatedFromJson(Map<String, dynamic> json) => Paginated(
  data: json['data'] as List<dynamic>,
  links: PaginationLinks.fromJson(json['links'] as Map<String, dynamic>),
  meta: PaginationMeta.fromJson(json['meta'] as Map<String, dynamic>),
);

Map<String, dynamic> _$PaginatedToJson(Paginated instance) => <String, dynamic>{
  'data': instance.data,
  'links': instance.links,
  'meta': instance.meta,
};
