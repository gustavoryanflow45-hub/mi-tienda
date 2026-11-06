// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'shipping_address.g.dart';

/// Snapshot de envío; los campos que falten pueden no venir o venir en null.
@JsonSerializable()
class ShippingAddress {
  const ShippingAddress({
    this.fullName,
    this.phone,
    this.email,
    this.address,
    this.city,
    this.state,
    this.country,
    this.postalCode,
  });
  
  factory ShippingAddress.fromJson(Map<String, Object?> json) => _$ShippingAddressFromJson(json);
  
  @JsonKey(name: 'full_name')
  final String? fullName;
  final String? phone;
  final String? email;
  final String? address;
  final String? city;
  final String? state;
  final String? country;
  @JsonKey(name: 'postal_code')
  final String? postalCode;

  Map<String, Object?> toJson() => _$ShippingAddressToJson(this);
}
