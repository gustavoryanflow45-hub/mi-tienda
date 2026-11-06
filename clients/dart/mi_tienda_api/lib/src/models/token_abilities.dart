// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'token_abilities.g.dart';

@JsonSerializable()
class TokenAbilities {
  const TokenAbilities({
    required this.abilities,
  });
  
  factory TokenAbilities.fromJson(Map<String, Object?> json) => _$TokenAbilitiesFromJson(json);
  
  final List<String> abilities;

  Map<String, Object?> toJson() => _$TokenAbilitiesToJson(this);
}
