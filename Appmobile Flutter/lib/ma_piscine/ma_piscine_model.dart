import '/components/nav_bar1_widget.dart';
import '/components/tilecard_widget.dart';
import '/flutter_flow/flutter_flow_theme.dart';
import '/flutter_flow/flutter_flow_util.dart';
import '/flutter_flow/flutter_flow_widgets.dart';
import 'dart:ui';
import '/index.dart';
import 'ma_piscine_widget.dart' show MaPiscineWidget;
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';

class MaPiscineModel extends FlutterFlowModel<MaPiscineWidget> {
  ///  State fields for stateful widgets in this page.

  // Model for Tilecard component.
  late TilecardModel tilecardModel1;
  // Model for Tilecard component.
  late TilecardModel tilecardModel2;
  // Model for Tilecard component.
  late TilecardModel tilecardModel3;
  // Model for Tilecard component.
  late TilecardModel tilecardModel4;
  // Model for Tilecard component.
  late TilecardModel tilecardModel5;
  // Model for Tilecard component.
  late TilecardModel tilecardModel6;
  // Model for NavBar1 component.
  late NavBar1Model navBar1Model;

  @override
  void initState(BuildContext context) {
    tilecardModel1 = createModel(context, () => TilecardModel());
    tilecardModel2 = createModel(context, () => TilecardModel());
    tilecardModel3 = createModel(context, () => TilecardModel());
    tilecardModel4 = createModel(context, () => TilecardModel());
    tilecardModel5 = createModel(context, () => TilecardModel());
    tilecardModel6 = createModel(context, () => TilecardModel());
    navBar1Model = createModel(context, () => NavBar1Model());
  }

  @override
  void dispose() {
    tilecardModel1.dispose();
    tilecardModel2.dispose();
    tilecardModel3.dispose();
    tilecardModel4.dispose();
    tilecardModel5.dispose();
    tilecardModel6.dispose();
    navBar1Model.dispose();
  }
}
