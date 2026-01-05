import '../database.dart';

class AccountsTable extends SupabaseTable<AccountsRow> {
  @override
  String get tableName => 'accounts';

  @override
  AccountsRow createRow(Map<String, dynamic> data) => AccountsRow(data);
}

class AccountsRow extends SupabaseDataRow {
  AccountsRow(Map<String, dynamic> data) : super(data);

  @override
  SupabaseTable get table => AccountsTable();

  String get id => getField<String>('id')!;
  set id(String value) => setField<String>('id', value);

  String get kind => getField<String>('kind')!;
  set kind(String value) => setField<String>('kind', value);

  String get name => getField<String>('name')!;
  set name(String value) => setField<String>('name', value);

  String? get ownerId => getField<String>('owner_id');
  set ownerId(String? value) => setField<String>('owner_id', value);

  DateTime? get createdAt => getField<DateTime>('created_at');
  set createdAt(DateTime? value) => setField<DateTime>('created_at', value);

  DateTime? get updatedAt => getField<DateTime>('updated_at');
  set updatedAt(DateTime? value) => setField<DateTime>('updated_at', value);
}
