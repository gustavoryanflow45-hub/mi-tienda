// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'user_shop.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

UserShop _$UserShopFromJson(Map<String, dynamic> json) => UserShop(
  id: (json['id'] as num).toInt(),
  name: json['name'] as String,
  status: UserShopStatus.fromJson(json['status'] as String),
);

Map<String, dynamic> _$UserShopToJson(UserShop instance) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'status': _$UserShopStatusEnumMap[instance.status]!,
};

const _$UserShopStatusEnumMap = {
  UserShopStatus.pending: 'pending',
  UserShopStatus.approved: 'approved',
  UserShopStatus.rejected: 'rejected',
  UserShopStatus.$unknown: r'$unknown',
};
