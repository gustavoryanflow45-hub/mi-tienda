// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'banner.dart';
import 'product_card.dart';

part 'home_feed.g.dart';

@JsonSerializable()
class HomeFeed {
  const HomeFeed({
    required this.banners,
    required this.featured,
    required this.newValue,
    required this.bestSelling,
  });
  
  factory HomeFeed.fromJson(Map<String, Object?> json) => _$HomeFeedFromJson(json);
  
  final List<Banner> banners;
  final List<ProductCard> featured;

  /// The name has been replaced because it contains a keyword. Original name: `new`.
  @JsonKey(name: 'new')
  final List<ProductCard> newValue;
  @JsonKey(name: 'best_selling')
  final List<ProductCard> bestSelling;

  Map<String, Object?> toJson() => _$HomeFeedToJson(this);
}
