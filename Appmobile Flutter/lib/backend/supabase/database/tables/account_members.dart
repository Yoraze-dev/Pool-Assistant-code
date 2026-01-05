import '../database.dart';

class AccountMembersTable extends SupabaseTable<AccountMembersRow> {
  @override
  String get tableName => 'account_members';

  @override
  AccountMembersRow createRow(Map<String, dynamic> data) =>
      AccountMembersRow(data);
}

class AccountMembersRow extends SupabaseDataRow {
  AccountMembersRow(Map<String, dynamic> data) : super(data);

  @override
  SupabaseTable get table => AccountMembersTable();

  String get accountId => getField<String>('account_id')!;
  set accountId(String value) => setField<String>('account_id', value);

  String get profileId => getField<String>('profile_id')!;
  set profileId(String value) => setField<String>('profile_id', value);

  String get role => getField<String>('role')!;
  set role(String value) => setField<String>('role', value);

  DateTime? get createdAt => getField<DateTime>('created_at');
  set createdAt(DateTime? value) => setField<DateTime>('created_at', value);
}
