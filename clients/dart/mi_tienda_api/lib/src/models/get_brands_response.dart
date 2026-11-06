// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'brand.dart';

part 'get_brands_response.g.dart';

@JsonSerializable()
class GetBrandsResponse {
  const GetBrandsResponse({
    required this.data,
  });
  
  factory GetBrandsResponse.fromJson(Map<String, Object?> json) => _$GetBrandsResponseFromJson(json);
  
  final List<Brand> data;

  Map<String, Object?> toJson() => _$GetBrandsResponseToJson(this);
}
