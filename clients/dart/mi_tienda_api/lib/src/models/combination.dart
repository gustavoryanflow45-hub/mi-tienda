// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'combination.g.dart';

@JsonSerializable()
class Combination {
  const Combination({
    required this.variation,
    required this.size,
    required this.color,
    required this.price,
    required this.originalPrice,
    required this.qty,
    required this.available,
  });
  
  factory Combination.fromJson(Map<String, Object?> json) => _$CombinationFromJson(json);
  
  /// Clave para `POST /cart/items`; "" sin variantes.
  final String variation;
  final String? size;
  final String? color;
  final num price;
  @JsonKey(name: 'original_price')
  final num originalPrice;
  final int qty;
  final bool available;

  Map<String, Object?> toJson() => _$CombinationToJson(this);
}
