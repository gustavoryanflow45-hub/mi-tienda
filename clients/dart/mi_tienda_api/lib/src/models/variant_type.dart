// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'variant_type.g.dart';

@JsonSerializable()
class VariantType {
  const VariantType({
    required this.key,
    required this.label,
    required this.sizeLabel,
    required this.sizes,
  });
  
  factory VariantType.fromJson(Map<String, Object?> json) => _$VariantTypeFromJson(json);
  
  final String key;
  final String label;
  @JsonKey(name: 'size_label')
  final String? sizeLabel;
  final List<String> sizes;

  Map<String, Object?> toJson() => _$VariantTypeToJson(this);
}
