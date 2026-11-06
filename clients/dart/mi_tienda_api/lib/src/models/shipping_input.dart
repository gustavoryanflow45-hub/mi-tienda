// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'shipping_input.g.dart';

@JsonSerializable()
class ShippingInput {
  const ShippingInput({
    required this.fullName,
    required this.phone,
    required this.email,
    required this.address,
    required this.city,
    this.state,
    this.country,
    this.postalCode,
  });
  
  factory ShippingInput.fromJson(Map<String, Object?> json) => _$ShippingInputFromJson(json);
  
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

  Map<String, Object?> toJson() => _$ShippingInputToJson(this);
}
