// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'home_feed.dart';

part 'get_home_response.g.dart';

@JsonSerializable()
class GetHomeResponse {
  const GetHomeResponse({
    required this.data,
  });
  
  factory GetHomeResponse.fromJson(Map<String, Object?> json) => _$GetHomeResponseFromJson(json);
  
  final HomeFeed data;

  Map<String, Object?> toJson() => _$GetHomeResponseToJson(this);
}
