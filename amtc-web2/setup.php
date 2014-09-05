<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Intel AMT/vPro remote power Management">
    <meta name="author" content="jan@hacker.ch">
    <title>amtc-web - AMT/DASH remote power management</title>
    <link href="css/styles.css" rel="stylesheet">
    <link href="css/amtc-web.css" rel="stylesheet">
</head>
<body>

<?php
   if (file_exists("data/siteconfig.php"))
    die('<div class="jumbotron">
      <h1><span class="label label-danger">Nope!</span> Setup tool is blocked</h1>
      <p><br>Please rename existing data/siteconfig.php to re-run initial installation.</p>
    </div>
    ');
?>

<script type="text/x-handlebars">
<div id="wrapper">
  <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
    <div class="navbar-header">
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="index.html">
        amtc-web <i id="bolt" class="fa fa-bolt fa-fw grey flash"></i> 
        <i>remote. power. management.</i></a>
    </div>
    <!-- /.navbar-header -->

    <div class="navbar-default sidebar" role="navigation">
        <div class="sidebar-nav navbar-collapse">
            <ul class="nav" id="side-menu">
                <li class="sidebar-search">
                    
                    <!-- /input-group -->
                </li>
                <li>
                    <a class="active" href="#"><i class="fa fa-dashboard fa-fw"></i> Setup</a>
                </li>                    
                <li>
                    <a href="#"><i class="fa fa-ambulance fa-fw"></i> Help<span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level">
                        <li> <a href="index.html#/pages/1"><i class="fa fa-wrench fa-fw"></i> Configuring AMT</a> </li>
                        <li> <a href="index.html#/pages/2"><i class="fa fa-child fa-fw"></i> {{appName}} First Steps</a> </li>
                        <li> <a href="index.html#/pages/3"><i class="fa fa-github fa-fw"></i> About, Feedback, ...</a> </li>
                    </ul>
                    <!-- /.nav-second-level -->
                </li>
            </ul>
        </div>
        <!-- /.sidebar-collapse -->
    </div>
    <!-- /.navbar-static-side -->
  </nav>
  
  <div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">amtc-web initial setup</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->

    <div class="row">
        <div class="col-lg-12">
          <form role="form">
            
            <div class="form-group">
                <label>Select your desired PHP <a target="new" href="http://php.net/manual/en/intro.pdo.php">PDO DB Driver</a></label>
                {{view Ember.Select
                              class="form-control"
                              content=dbs
                              value=selectedDB
                              prompt="Select PDO driver"
                }}
            </div>

            {{#if isMySQL}}
              <div class="form-group">
                  <p>The user provided must exist and either be privileged to create the
                     named Database -- or it must be created for the user prior to amtc-web installtion.</p>
              </div>
              <div class="form-group">
                  <label>MySQL Database Server host</label>
                  {{input type="text" value=mysqlHost class="form-control" placeholder="mysql-server.example.com"}}
              </div>
              <div class="form-group">
                  <label>Database name</label>
                  {{input type="text" value=mysqlDB class="form-control" placeholder="amtc-web"}}
              </div>
              <div class="form-group">
                  <label>Username</label>
                  {{input type="text" value=mysqlUser class="form-control" placeholder="joe"}}
              </div>
              <div class="form-group">
                  <label>Password</label>
                  {{input type="text" value=mysqlPassword class="form-control" placeholder="joe2014"}}
              </div>
            {{/if}}

            {{#if isSQLite}}
              <div class="form-group">
                  <label>SQLite database file</label>
                  {{input type="text" value=sqlitePath class="form-control" placeholder="/path/to/dbfile.sqlite"}}
                  <p class="help-block">Note that the directory containing the file must be writable, too.</p>
              </div>
            {{/if}}

            {{#if selectedDB}}
              <div class="form-group">
                <fieldset disabled>
                  <label>Resulting PDO connection string</label>
                  {{input type="text" value=pdoString class="form-control" placeholder="/path/to/dbfile.sqlite"}}
                </fieldset>
              </div>
            {{/if}}

            <div class="form-group">
                <label>Example data</label>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" value=""> Install example data (some OUs, AMT option sets, and hosts)
                    </label>
                </div>
            </div>
            <button class="btn btn-default" {{action 'doneEditing'}}><span class="glyphicon glyphicon-floppy-disk"></span> Write configuration</button>
        </form>
      </div>
      <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
  </div>
</div>
</script>

<script src="js/jslibs.js"></script>
<script type="text/javascript">
  // init code as in index.html
  window.setTimeout( function(){
      $('#bolt').removeClass('flash');
  }, 3000);
  // actual sb-admin-2.js page/template initialization
  $(window).bind("load resize", function() {
    topOffset = 50;
    width = (this.window.innerWidth > 0) ? this.window.innerWidth : this.screen.width;
    if (width < 768) {
      $('div.navbar-collapse').addClass('collapse');
      topOffset = 100; // 2-row-menu
    } else {
      $('div.navbar-collapse').removeClass('collapse');
    }

    height = (this.window.innerHeight > 0) ? this.window.innerHeight : this.screen.height;
    height = height - topOffset;
    if (height < 1) height = 1;
    if (height > topOffset) {
      $("#page-wrapper").css("min-height", (height) + "px");
    }
  });
  $('#side-menu').metisMenu();

  // installer specfic ember app
  var App = Ember.Application.create({
  });
  App.ApplicationController = Ember.ObjectController.extend({  
    selectedDB: null,
    sqlitePath: null,
    mysqlUser: null,
    mysqlHost: null,
    mysqlPassword: null,
    mysqlDB: null,

    dbs: [
      "SQLite",
      "MySQL"
    ],
    
    isMySQL: function() {
        return (this.get('selectedDB')=='MySQL') ? true : false;
    }.property('selectedDB'),
    
    isSQLite: function() {
        return (this.get('selectedDB')=='SQLite') ? true : false;
    }.property('selectedDB'),

    pdoString: function() {
        if (this.get('selectedDB')=='MySQL') {
          return 'mysql://' + this.get('mysqlUser') + ':' + this.get('mysqlPassword') + "@" + this.get('mysqlHost') + "/" + this.get('mysqlDB');
        } else {
          return 'sqlite://unix(' + this.get('sqlitePath') + ')';
        }
    }.property('selectedDB','sqlitePath','mysqlUser','mysqlPassword','mysqlHost','mysqlDB'),

    doneEditing: function() {
        humane.log('<i class="glyphicon glyphicon-saved"></i> Not yet, sorry.',
            { timeout: 800 });
    }
  });
</script>

<noscript>
    <div class="jumbotron">
      <h1><span class="label label-danger">OMFG!</span> !#@$^~%-/</h1>
      <p><br>Please enable JavaScript in your browser to use amtc-web.</p>
    </div>
</noscript>

</body>
</html>
