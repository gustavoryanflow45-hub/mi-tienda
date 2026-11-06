// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'home_feed.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

HomeFeed _$HomeFeedFromJson(Map<String, dynamic> json) => HomeFeed(
  banners: (json['banners'] as List<dynamic>)
      .map((e) => Banner.fromJson(e as Map<String, dynamic>))
      .toList(),
  featured: (json['featured'] as List<dynamic>)
      .map((e) => ProductCard.fromJson(e as Map<String, dynamic>))
      .toList(),
  newValue: (json['new'] as List<dynamic>)
      .map((e) => ProductCard.fromJson(e as Map<String, dynamic>))
      .toList(),
  bestSelling: (json['best_selling'] as List<dynamic>)
      .map((e) => ProductCard.fromJson(e as Map<String, dynamic>))
      .toList(),
);

Map<String, dynamic> _$HomeFeedToJson(HomeFeed instance) => <String, dynamic>{
  'banners': instance.banners,
  'featured': instance.featured,
  'new': instance.newValue,
  'best_selling': instance.bestSelling,
};
