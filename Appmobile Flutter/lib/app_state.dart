import 'package:flutter/material.dart';
import 'backend/supabase/supabase.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'flutter_flow/flutter_flow_util.dart';
import 'dart:convert';

class FFAppState extends ChangeNotifier {
  static FFAppState _instance = FFAppState._internal();

  factory FFAppState() {
    return _instance;
  }

  FFAppState._internal();

  static void reset() {
    _instance = FFAppState._internal();
  }

  Future initializePersistedState() async {
    prefs = await SharedPreferences.getInstance();
    _safeInit(() {
      _favorites = prefs.getStringList('ff_favorites')?.map((x) {
            try {
              return jsonDecode(x);
            } catch (e) {
              print("Can't decode persisted json. Error: $e.");
              return {};
            }
          }).toList() ??
          _favorites;
    });
    _safeInit(() {
      _favoriteIds = prefs.getStringList('ff_favoriteIds') ?? _favoriteIds;
    });
    _safeInit(() {
      _selectedDateTime = prefs.containsKey('ff_selectedDateTime')
          ? DateTime.fromMillisecondsSinceEpoch(
              prefs.getInt('ff_selectedDateTime')!)
          : _selectedDateTime;
    });
  }

  void update(VoidCallback callback) {
    callback();
    notifyListeners();
  }

  late SharedPreferences prefs;

  List<dynamic> _pros = [
    jsonDecode(
        '{\"pro_id\":\"000201\",\"company_name\":\"GS Piscines\",\"city\":\"Toulouse\",\"zip\":\"31000\",\"tags\":[\"entretien\",\"depannage\"],\"rating\":4.7,\"reviews_count\":126,\"is_verified\":true,\"avatar_url\":\"https://via.placeholder.com/80\",\"summary\":\"Spécialiste de l’entretien régulier et des réparations rapides. Intervient sur toutes marques de pompes et filtres, avec un suivi personnalisé\"}'),
    jsonDecode(
        '{\"pro_id\":\"000202\",\"company_name\":\"Water Sense\",\"city\":\"Lattes\",\"zip\":\"34970\",\"tags\":[\"analyse\",\"entretien\"],\"rating\":4.6,\"reviews_count\":89,\"is_verified\":false,\"avatar_url\":\"https://via.placeholder.com/80\",\"summary\":\"Expert en analyse de l’eau et équilibrage chimique. Conseils précis et solutions durables pour garder une eau claire et saine toute l’année.\"}'),
    jsonDecode(
        '{\"pro_id\":\"000203\",\"company_name\":\"OcéaPro Pool\",\"city\":\"Castelnau\",\"zip\":\"34170\",\"tags\":[\"reparation\",\"hivernage\"],\"rating\":4.7,\"reviews_count\":74,\"is_verified\":true,\"avatar_url\":\"https://via.placeholder.com/80\",\"summary\":\"Réparations, hivernage et remises en route. Interventions rapides et soignées avec un accompagnement de proximité.\"}')
  ];
  List<dynamic> get pros => _pros;
  set pros(List<dynamic> value) {
    _pros = value;
  }

  void addToPros(dynamic value) {
    pros.add(value);
  }

  void removeFromPros(dynamic value) {
    pros.remove(value);
  }

  void removeAtIndexFromPros(int index) {
    pros.removeAt(index);
  }

  void updateProsAtIndex(
    int index,
    dynamic Function(dynamic) updateFn,
  ) {
    pros[index] = updateFn(_pros[index]);
  }

  void insertAtIndexInPros(int index, dynamic value) {
    pros.insert(index, value);
  }

  List<dynamic> _history = [
    jsonDecode(
        '{\"row_id\":\"L-001\",\"kind\":\"lead\",\"event_at\":\"2025-09-10T09:42:00Z\",\"event_label\":\"Lundi 1 novembre 2025 - 11h45\",\"status\":\"new\",\"pro_name\":\"GS Piscines\",\"pro_id\":\"000201\",\"scheduled_start\":null,\"scheduled_end\":null}'),
    jsonDecode(
        '{\"row_id\":\"A-002\",\"kind\":\"appointment\",\"event_at\":\"2025-09-12T08:00:00Z\",\"event_label\":\"Lundi 11 novembre 2025 - 16h25\",\"status\":\"planned\",\"pro_name\":\"Water Sense\",\"pro_id\":\"000202\",\"scheduled_start\":\"2025-09-12T08:00:00Z\",\"scheduled_end\":\"2025-09-12T09:00:00Z\"}')
  ];
  List<dynamic> get history => _history;
  set history(List<dynamic> value) {
    _history = value;
  }

  void addToHistory(dynamic value) {
    history.add(value);
  }

  void removeFromHistory(dynamic value) {
    history.remove(value);
  }

  void removeAtIndexFromHistory(int index) {
    history.removeAt(index);
  }

  void updateHistoryAtIndex(
    int index,
    dynamic Function(dynamic) updateFn,
  ) {
    history[index] = updateFn(_history[index]);
  }

  void insertAtIndexInHistory(int index, dynamic value) {
    history.insert(index, value);
  }

  List<dynamic> _favorites = [];
  List<dynamic> get favorites => _favorites;
  set favorites(List<dynamic> value) {
    _favorites = value;
    prefs.setStringList(
        'ff_favorites', value.map((x) => jsonEncode(x)).toList());
  }

  void addToFavorites(dynamic value) {
    favorites.add(value);
    prefs.setStringList(
        'ff_favorites', _favorites.map((x) => jsonEncode(x)).toList());
  }

  void removeFromFavorites(dynamic value) {
    favorites.remove(value);
    prefs.setStringList(
        'ff_favorites', _favorites.map((x) => jsonEncode(x)).toList());
  }

  void removeAtIndexFromFavorites(int index) {
    favorites.removeAt(index);
    prefs.setStringList(
        'ff_favorites', _favorites.map((x) => jsonEncode(x)).toList());
  }

  void updateFavoritesAtIndex(
    int index,
    dynamic Function(dynamic) updateFn,
  ) {
    favorites[index] = updateFn(_favorites[index]);
    prefs.setStringList(
        'ff_favorites', _favorites.map((x) => jsonEncode(x)).toList());
  }

  void insertAtIndexInFavorites(int index, dynamic value) {
    favorites.insert(index, value);
    prefs.setStringList(
        'ff_favorites', _favorites.map((x) => jsonEncode(x)).toList());
  }

  List<String> _favoriteIds = [];
  List<String> get favoriteIds => _favoriteIds;
  set favoriteIds(List<String> value) {
    _favoriteIds = value;
    prefs.setStringList('ff_favoriteIds', value);
  }

  void addToFavoriteIds(String value) {
    favoriteIds.add(value);
    prefs.setStringList('ff_favoriteIds', _favoriteIds);
  }

  void removeFromFavoriteIds(String value) {
    favoriteIds.remove(value);
    prefs.setStringList('ff_favoriteIds', _favoriteIds);
  }

  void removeAtIndexFromFavoriteIds(int index) {
    favoriteIds.removeAt(index);
    prefs.setStringList('ff_favoriteIds', _favoriteIds);
  }

  void updateFavoriteIdsAtIndex(
    int index,
    String Function(String) updateFn,
  ) {
    favoriteIds[index] = updateFn(_favoriteIds[index]);
    prefs.setStringList('ff_favoriteIds', _favoriteIds);
  }

  void insertAtIndexInFavoriteIds(int index, String value) {
    favoriteIds.insert(index, value);
    prefs.setStringList('ff_favoriteIds', _favoriteIds);
  }

  DateTime? _selectedDateTime;
  DateTime? get selectedDateTime => _selectedDateTime;
  set selectedDateTime(DateTime? value) {
    _selectedDateTime = value;
    value != null
        ? prefs.setInt('ff_selectedDateTime', value.millisecondsSinceEpoch)
        : prefs.remove('ff_selectedDateTime');
  }

  bool _showGuide = false;
  bool get showGuide => _showGuide;
  set showGuide(bool value) {
    _showGuide = value;
  }
}

void _safeInit(Function() initializeField) {
  try {
    initializeField();
  } catch (_) {}
}

Future _safeInitAsync(Function() initializeField) async {
  try {
    await initializeField();
  } catch (_) {}
}
