// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'user.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

User _$UserFromJson(Map<String, dynamic> json) => User(
  id: (json['id'] as num).toInt(),
  name: json['name'] as String,
  email: json['email'] as String,
  phone: json['phone'] as String?,
  avatarUrl: json['avatar_url'] as String?,
  type: UserType.fromJson(json['type'] as String),
  emailVerified: json['email_verified'] as bool,
  balance: json['balance'] as num,
  shop: json['shop'] == null
      ? null
      : UserShop.fromJson(json['shop'] as Map<String, dynamic>),
  createdAt: json['created_at'] == null
      ? null
      : DateTime.parse(json['created_at'] as String),
);

Map<String, dynamic> _$UserToJson(User instance) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'email': instance.email,
  'phone': instance.phone,
  'avatar_url': instance.avatarUrl,
  'type': _$UserTypeEnumMap[instance.type]!,
  'email_verified': instance.emailVerified,
  'balance': instance.balance,
  'shop': instance.shop,
  'created_at': instance.createdAt?.toIso8601String(),
};

const _$UserTypeEnumMap = {
  UserType.customer: 'customer',
  UserType.seller: 'seller',
  UserType.$unknown: r'$unknown',
};
