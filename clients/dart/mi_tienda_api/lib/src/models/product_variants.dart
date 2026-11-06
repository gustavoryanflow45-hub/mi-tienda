// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'color.dart';
import 'combination.dart';

part 'product_variants.g.dart';

@JsonSerializable()
class ProductVariants {
  const ProductVariants({
    required this.sizeLabel,
    required this.sizes,
    required this.colors,
    required this.combinations,
  });
  
  factory ProductVariants.fromJson(Map<String, Object?> json) => _$ProductVariantsFromJson(json);
  
  @JsonKey(name: 'size_label')
  final String sizeLabel;
  final List<String> sizes;
  final List<Color> colors;
  final List<Combination> combinations;

  Map<String, Object?> toJson() => _$ProductVariantsToJson(this);
}
