// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'token_abilities.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TokenAbilities _$TokenAbilitiesFromJson(Map<String, dynamic> json) =>
    TokenAbilities(
      abilities: (json['abilities'] as List<dynamic>)
          .map((e) => e as String)
          .toList(),
    );

Map<String, dynamic> _$TokenAbilitiesToJson(TokenAbilities instance) =>
    <String, dynamic>{'abilities': instance.abilities};
