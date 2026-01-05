import '/components/nav_bar1_widget.dart';
import '/components/tilecard_widget.dart';
import '/flutter_flow/flutter_flow_theme.dart';
import '/flutter_flow/flutter_flow_util.dart';
import '/flutter_flow/flutter_flow_widgets.dart';
import 'dart:ui';
import '/index.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'ma_piscine_model.dart';
export 'ma_piscine_model.dart';

class MaPiscineWidget extends StatefulWidget {
  const MaPiscineWidget({super.key});

  static String routeName = 'MaPiscine';
  static String routePath = '/maPiscine';

  @override
  State<MaPiscineWidget> createState() => _MaPiscineWidgetState();
}

class _MaPiscineWidgetState extends State<MaPiscineWidget> {
  late MaPiscineModel _model;

  final scaffoldKey = GlobalKey<ScaffoldState>();

  @override
  void initState() {
    super.initState();
    _model = createModel(context, () => MaPiscineModel());
  }

  @override
  void dispose() {
    _model.dispose();

    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () {
        FocusScope.of(context).unfocus();
        FocusManager.instance.primaryFocus?.unfocus();
      },
      child: Scaffold(
        key: scaffoldKey,
        backgroundColor: FlutterFlowTheme.of(context).primaryBackground,
        body: NestedScrollView(
          floatHeaderSlivers: false,
          headerSliverBuilder: (context, _) => [
            SliverAppBar(
              pinned: false,
              floating: false,
              backgroundColor: FlutterFlowTheme.of(context).secondaryBackground,
              automaticallyImplyLeading: false,
              title: ClipRRect(
                borderRadius: BorderRadius.circular(8.0),
                child: Image.asset(
                  'assets/images/Coconut_400x120_(18).png',
                  width: 121.6,
                  height: 30.3,
                  fit: BoxFit.contain,
                ),
              ),
              actions: [
                InkWell(
                  splashColor: Colors.transparent,
                  focusColor: Colors.transparent,
                  hoverColor: Colors.transparent,
                  highlightColor: Colors.transparent,
                  onTap: () async {
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: Text(
                          'Notifications bientôt',
                          style: TextStyle(
                            color: FlutterFlowTheme.of(context).primaryText,
                          ),
                        ),
                        duration: Duration(milliseconds: 4000),
                        backgroundColor: FlutterFlowTheme.of(context).secondary,
                      ),
                    );
                  },
                  child: Icon(
                    Icons.notifications_none,
                    color: FlutterFlowTheme.of(context).secondaryText,
                    size: 24.0,
                  ),
                ),
                Align(
                  alignment: AlignmentDirectional(0.0, 0.0),
                  child: Padding(
                    padding: EdgeInsets.all(8.0),
                    child: Container(
                      width: 40.0,
                      height: 40.0,
                      clipBehavior: Clip.antiAlias,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                      ),
                      child: Image.network(
                        'https://picsum.photos/seed/319/600',
                        fit: BoxFit.cover,
                      ),
                    ),
                  ),
                ),
              ],
              centerTitle: false,
              elevation: 3.0,
            )
          ],
          body: Builder(
            builder: (context) {
              return SafeArea(
                top: false,
                child: Stack(
                  children: [
                    Padding(
                      padding: EdgeInsets.all(16.0),
                      child: Column(
                        mainAxisSize: MainAxisSize.max,
                        children: [
                          Expanded(
                            child: GridView(
                              padding: EdgeInsets.zero,
                              gridDelegate:
                                  SliverGridDelegateWithFixedCrossAxisCount(
                                crossAxisCount: 2,
                                crossAxisSpacing: 1.0,
                                mainAxisSpacing: 1.0,
                                childAspectRatio: 1.0,
                              ),
                              scrollDirection: Axis.vertical,
                              children: [
                                wrapWithModel(
                                  model: _model.tilecardModel1,
                                  updateCallback: () => safeSetState(() {}),
                                  child: TilecardWidget(
                                    icon: Icon(
                                      FFIcons.kkpool,
                                      color: FlutterFlowTheme.of(context)
                                          .secondary,
                                      size: 60.0,
                                    ),
                                  ),
                                ),
                                InkWell(
                                  splashColor: Colors.transparent,
                                  focusColor: Colors.transparent,
                                  hoverColor: Colors.transparent,
                                  highlightColor: Colors.transparent,
                                  onTap: () async {
                                    context.pushNamed(TestsWidget.routeName);
                                  },
                                  child: wrapWithModel(
                                    model: _model.tilecardModel2,
                                    updateCallback: () => safeSetState(() {}),
                                    child: TilecardWidget(
                                      title: 'Tests',
                                      subtitle: 'COMMENCER',
                                      icon: Icon(
                                        FFIcons.kktestPipe,
                                        color: FlutterFlowTheme.of(context)
                                            .secondary,
                                        size: 60.0,
                                      ),
                                    ),
                                  ),
                                ),
                                wrapWithModel(
                                  model: _model.tilecardModel3,
                                  updateCallback: () => safeSetState(() {}),
                                  child: TilecardWidget(
                                    title: 'Stock',
                                    subtitle: 'GESTION',
                                    icon: Icon(
                                      FFIcons.kkbuildingWarehouse,
                                      color: FlutterFlowTheme.of(context)
                                          .secondary,
                                      size: 60.0,
                                    ),
                                  ),
                                ),
                                wrapWithModel(
                                  model: _model.tilecardModel4,
                                  updateCallback: () => safeSetState(() {}),
                                  child: TilecardWidget(
                                    title: 'Traitements',
                                    subtitle: 'GESTION',
                                    icon: Icon(
                                      FFIcons.kwaterDrop,
                                      color: FlutterFlowTheme.of(context)
                                          .secondary,
                                      size: 60.0,
                                    ),
                                  ),
                                ),
                                InkWell(
                                  splashColor: Colors.transparent,
                                  focusColor: Colors.transparent,
                                  hoverColor: Colors.transparent,
                                  highlightColor: Colors.transparent,
                                  onTap: () async {
                                    context.pushNamed(
                                      AgendatraitementWidget.routeName,
                                      extra: <String, dynamic>{
                                        kTransitionInfoKey: TransitionInfo(
                                          hasTransition: true,
                                          transitionType:
                                              PageTransitionType.fade,
                                          duration: Duration(milliseconds: 0),
                                        ),
                                      },
                                    );
                                  },
                                  child: wrapWithModel(
                                    model: _model.tilecardModel5,
                                    updateCallback: () => safeSetState(() {}),
                                    child: TilecardWidget(
                                      title: 'Agenda',
                                      subtitle: 'ACCEDER',
                                      icon: Icon(
                                        FFIcons.kcalendar2Date,
                                        color: FlutterFlowTheme.of(context)
                                            .secondary,
                                        size: 60.0,
                                      ),
                                    ),
                                  ),
                                ),
                                wrapWithModel(
                                  model: _model.tilecardModel6,
                                  updateCallback: () => safeSetState(() {}),
                                  child: TilecardWidget(
                                    title: 'Météo',
                                    subtitle: 'VISUALISER',
                                    icon: Icon(
                                      FFIcons.kcloudSun,
                                      color: FlutterFlowTheme.of(context)
                                          .secondary,
                                      size: 60.0,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ].addToStart(SizedBox(height: 8.0)),
                      ),
                    ),
                    Align(
                      alignment: AlignmentDirectional(0.0, 1.0),
                      child: wrapWithModel(
                        model: _model.navBar1Model,
                        updateCallback: () => safeSetState(() {}),
                        child: NavBar1Widget(),
                      ),
                    ),
                  ],
                ),
              );
            },
          ),
        ),
      ),
    );
  }
}
