// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'banner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

Banner _$BannerFromJson(Map<String, dynamic> json) => Banner(
  id: (json['id'] as num).toInt(),
  title: json['title'] as String?,
  imageUrl: json['image_url'] as String,
  link: json['link'] as String?,
);

Map<String, dynamic> _$BannerToJson(Banner instance) => <String, dynamic>{
  'id': instance.id,
  'title': instance.title,
  'image_url': instance.imageUrl,
  'link': instance.link,
};
