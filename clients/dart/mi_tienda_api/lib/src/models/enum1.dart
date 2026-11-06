// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

/// Name not received and was auto-generated.
@JsonEnum()
enum Enum1 {
  @JsonValue('BEP20')
  bep20('BEP20'),
  @JsonValue('TRC20')
  trc20('TRC20'),
  @JsonValue('ERC20')
  erc20('ERC20'),
  /// Default value for all unparsed values, allows backward compatibility when adding new values on the backend.
  $unknown(null);

  const Enum1(this.json);

  factory Enum1.fromJson(String json) => values.firstWhere(
        (e) => e.json == json,
        orElse: () => $unknown,
      );

  final String? json;

  @override
  String toString() => json?.toString() ?? super.toString();
  /// Returns all defined enum values excluding the $unknown value.
  static List<Enum1> get $valuesDefined => values.where((value) => value != $unknown).toList();
}
