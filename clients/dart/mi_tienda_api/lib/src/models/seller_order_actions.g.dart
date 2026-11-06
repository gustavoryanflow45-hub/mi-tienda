// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'seller_order_actions.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SellerOrderActions _$SellerOrderActionsFromJson(Map<String, dynamic> json) =>
    SellerOrderActions(
      confirm: json['confirm'] as bool,
      sendToWarehouse: json['send_to_warehouse'] as bool,
    );

Map<String, dynamic> _$SellerOrderActionsToJson(SellerOrderActions instance) =>
    <String, dynamic>{
      'confirm': instance.confirm,
      'send_to_warehouse': instance.sendToWarehouse,
    };
