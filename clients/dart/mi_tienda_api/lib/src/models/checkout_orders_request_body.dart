// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'shipping_input.dart';

part 'checkout_orders_request_body.g.dart';

@JsonSerializable()
class CheckoutOrdersRequestBody {
  const CheckoutOrdersRequestBody({
    required this.fullName,
    required this.phone,
    required this.email,
    required this.address,
    required this.city,
    this.state,
    this.country,
    this.postalCode,
    this.orderId,
  });
  
  factory CheckoutOrdersRequestBody.fromJson(Map<String, Object?> json) => _$CheckoutOrdersRequestBodyFromJson(json);
  
  @JsonKey(name: 'full_name')
  final String fullName;
  final String phone;
  final String email;
  final String address;
  final String city;
  final String? state;
  final String? country;
  @JsonKey(name: 'postal_code')
  final String? postalCode;

  /// Pedido propio sin pagar a reutilizar.
  @JsonKey(name: 'order_id')
  final int? orderId;

  Map<String, Object?> toJson() => _$CheckoutOrdersRequestBodyToJson(this);
}
