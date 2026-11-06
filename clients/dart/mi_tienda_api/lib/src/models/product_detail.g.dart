// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'product_detail.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProductDetail _$ProductDetailFromJson(
  Map<String, dynamic> json,
) => ProductDetail(
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
  photos: (json['photos'] as List<dynamic>).map((e) => e as String).toList(),
  shortDescription: json['short_description'] as String?,
  description: json['description'] as String?,
  minQty: (json['min_qty'] as num).toInt(),
  shippingCost: json['shipping_cost'] as num,
  shop: json['shop'] == null
      ? null
      : NamedRef.fromJson(json['shop'] as Map<String, dynamic>),
  variants: ProductVariants.fromJson(json['variants'] as Map<String, dynamic>),
  related: (json['related'] as List<dynamic>?)
      ?.map((e) => ProductCard.fromJson(e as Map<String, dynamic>))
      .toList(),
);

Map<String, dynamic> _$ProductDetailToJson(ProductDetail instance) =>
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
      'photos': instance.photos,
      'short_description': instance.shortDescription,
      'description': instance.description,
      'min_qty': instance.minQty,
      'shipping_cost': instance.shippingCost,
      'shop': instance.shop,
      'variants': instance.variants,
      'related': instance.related,
    };
