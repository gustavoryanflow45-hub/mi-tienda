// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'product_card.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProductCard _$ProductCardFromJson(Map<String, dynamic> json) => ProductCard(
  id: (json['id'] as num).toInt(),
  slug: json['slug'] as String,
  name: json['name'] as String,
  thumbnailUrl: json['thumbnail_url'] as String,
  price: json['price'] as num,
  originalPrice: json['original_price'] as num,
  discount: json['discount'] == null
      ? null
      : Discount.fromJson(json['discount'] as Map<String, dynamic>),
  unit: json['unit'] as String?,
  rating: json['rating'] as num,
  inStock: json['in_stock'] as bool,
  hasVariants: json['has_variants'] as bool,
  category: json['category'] == null
      ? null
      : CategoryRef.fromJson(json['category'] as Map<String, dynamic>),
  brand: json['brand'] == null
      ? null
      : NamedRef.fromJson(json['brand'] as Map<String, dynamic>),
);

Map<String, dynamic> _$ProductCardToJson(ProductCard instance) =>
    <String, dynamic>{
      'id': instance.id,
      'slug': instance.slug,
      'name': instance.name,
      'thumbnail_url': instance.thumbnailUrl,
      'price': instance.price,
      'original_price': instance.originalPrice,
      'discount': instance.discount,
      'unit': instance.unit,
      'rating': instance.rating,
      'in_stock': instance.inStock,
      'has_variants': instance.hasVariants,
      'category': instance.category,
      'brand': instance.brand,
    };
