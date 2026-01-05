import '../database.dart';

class ProProfilesTable extends SupabaseTable<ProProfilesRow> {
  @override
  String get tableName => 'pro_profiles';

  @override
  ProProfilesRow createRow(Map<String, dynamic> data) => ProProfilesRow(data);
}

class ProProfilesRow extends SupabaseDataRow {
  ProProfilesRow(Map<String, dynamic> data) : super(data);

  @override
  SupabaseTable get table => ProProfilesTable();

  String get profileId => getField<String>('profile_id')!;
  set profileId(String value) => setField<String>('profile_id', value);

  String get companyName => getField<String>('company_name')!;
  set companyName(String value) => setField<String>('company_name', value);

  String? get city => getField<String>('city');
  set city(String? value) => setField<String>('city', value);

  String? get zip => getField<String>('zip');
  set zip(String? value) => setField<String>('zip', value);

  List<String> get tags => getListField<String>('tags');
  set tags(List<String>? value) => setListField<String>('tags', value);

  double? get rating => getField<double>('rating');
  set rating(double? value) => setField<double>('rating', value);

  int? get reviewsCount => getField<int>('reviews_count');
  set reviewsCount(int? value) => setField<int>('reviews_count', value);

  bool? get isVerified => getField<bool>('is_verified');
  set isVerified(bool? value) => setField<bool>('is_verified', value);

  String? get avatarUrl => getField<String>('avatar_url');
  set avatarUrl(String? value) => setField<String>('avatar_url', value);

  DateTime? get createdAt => getField<DateTime>('created_at');
  set createdAt(DateTime? value) => setField<DateTime>('created_at', value);

  DateTime? get updatedAt => getField<DateTime>('updated_at');
  set updatedAt(DateTime? value) => setField<DateTime>('updated_at', value);
}
