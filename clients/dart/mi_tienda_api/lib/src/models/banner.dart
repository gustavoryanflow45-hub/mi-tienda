// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'banner.g.dart';

@JsonSerializable()
class Banner {
  const Banner({
    required this.id,
    required this.title,
    required this.imageUrl,
    required this.link,
  });
  
  factory Banner.fromJson(Map<String, Object?> json) => _$BannerFromJson(json);
  
  final int id;
  final String? title;
  @JsonKey(name: 'image_url')
  final String imageUrl;
  final String? link;

  Map<String, Object?> toJson() => _$BannerToJson(this);
}
