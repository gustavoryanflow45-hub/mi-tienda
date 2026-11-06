// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'variant_info.g.dart';

@JsonSerializable()
class VariantInfo {
  const VariantInfo({
    required this.text,
    required this.size,
    required this.sizeLabel,
    required this.color,
    required this.colorLabel,
    required this.colorHex,
  });
  
  factory VariantInfo.fromJson(Map<String, Object?> json) => _$VariantInfoFromJson(json);
  
  /// Para mostrar: "Talla US 9 · Negro".
  final String text;
  final String? size;
  @JsonKey(name: 'size_label')
  final String? sizeLabel;
  final String? color;
  @JsonKey(name: 'color_label')
  final String? colorLabel;
  @JsonKey(name: 'color_hex')
  final String? colorHex;

  Map<String, Object?> toJson() => _$VariantInfoToJson(this);
}
