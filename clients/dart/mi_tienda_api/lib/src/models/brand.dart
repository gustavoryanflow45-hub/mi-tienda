// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'brand.g.dart';

@JsonSerializable()
class Brand {
  const Brand({
    required this.id,
    required this.name,
    required this.slug,
    required this.logoUrl,
  });
  
  factory Brand.fromJson(Map<String, Object?> json) => _$BrandFromJson(json);
  
  final int id;
  final String name;
  final String? slug;
  @JsonKey(name: 'logo_url')
  final String? logoUrl;

  Map<String, Object?> toJson() => _$BrandToJson(this);
}
