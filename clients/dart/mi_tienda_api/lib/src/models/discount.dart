// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'discount_type.dart';

part 'discount.g.dart';

@JsonSerializable()
class Discount {
  const Discount({
    required this.type,
    required this.value,
  });
  
  factory Discount.fromJson(Map<String, Object?> json) => _$DiscountFromJson(json);
  
  final DiscountType type;
  final num value;

  Map<String, Object?> toJson() => _$DiscountToJson(this);
}
