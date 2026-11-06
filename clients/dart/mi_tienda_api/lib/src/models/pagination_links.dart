// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'pagination_links.g.dart';

@JsonSerializable()
class PaginationLinks {
  const PaginationLinks({
    required this.first,
    required this.last,
    required this.prev,
    required this.next,
  });
  
  factory PaginationLinks.fromJson(Map<String, Object?> json) => _$PaginationLinksFromJson(json);
  
  final String? first;
  final String? last;
  final String? prev;
  final String? next;

  Map<String, Object?> toJson() => _$PaginationLinksToJson(this);
}
