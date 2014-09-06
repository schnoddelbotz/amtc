<?php
  //error_reporting(E_ALL);
  //ini_set('display_errors','on');
  // YEAH!!! neeeds cleanup/fix!!
  /*
   *   
   */
  $cfgFile = "data/siteconfig.php";
  if ($_POST && !file_exists($cfgFile)) {
    header('Content-Type: application/json;charset=utf-8');
    $x = array("message"=>"Configuration written successfully", "data"=>$_POST);
    $cfgTpl = '<?php define("AMTC_PDOSTRING", \'%s\'); ?>';
    $phpArPdoString = addslashes($_POST['pdoString']);
    $phpArPdoString = preg_replace('@^sqlite://(.*)@', 'sqlite://unix(/\\1)', $phpArPdoString);
    $cfg = sprintf($cfgTpl, $phpArPdoString);
    if (!is_writable(dirname($cfgFile))) {
      $x = array("errorMsg"=>"Data directory not writable!");
    } elseif (false === file_put_contents($cfgFile, $cfg)) {
      $x = array("errorMsg"=>"Could not!");
    } else {
      if ($_POST['selectedDB'] == 'SQLite') {
        // create db if non-existant
        @touch($_POST['sqlitePath']);
      }
      if ($_POST['importDemo']==true) {
        //$x = array("errorMsg"=>$_POST['pdoString']."hahaha");
        $dbh = new PDO($_POST['pdoString']);
        foreach (Array('lib/db-model/install-db/sqlite.sql', 'lib/db-model/install-db/sqlite-exampledata.sql') as $f) {
          $sql = file_get_contents($f);
          $dbh->exec($sql);
        }
      }
      // fixme: add _htaccess thing
    }
    echo json_encode($x);
    exit(0);  
  }
?><!DOCTYPE html>
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
    <p>Try visiting the <a href="index.html">amtc-web frontend</a> now. Should it fail, check JavaScript console.</p>
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
                        {{input type="checkbox" checked=importDemo}} Install example data (some OUs, AMT option sets, and hosts)
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>Performance optimization</label>
                <div class="checkbox">
                    <label>
                        {{input type="checkbox" checked=installHtaccess}} Enable RewriteRules via .htaccess
                    </label>
                    <p class="help-block">Your webserver must have mod_rewrite enabled and AllowOverride must
                                          be configured to allow use of .htaccess files. <br>Given that, you may
                                          check this option to deliver gzip compressed HTML/CSS/JS to clients.</p>
                </div>
            </div>

            <div class="form-group">
              <div class="alert alert-danger">
                <em>Warning!</em> This setup tool currently offers no way yet to check config before writing it.<br>
                Once the configuration is submitted, this tool will be locked.<br> You will have
                to delete the configuration file (<code>data/siteconfig.php</code>) manually to re-enable it.
              </div>
              <button class="btn btn-default" {{action 'doneEditing'}}><span class="glyphicon glyphicon-floppy-disk"></span> Write configuration</button>
            </div>
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
    importDemo: null,
    installHtaccess: null,

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
          return 'sqlite:/' + this.get('sqlitePath');
        }
    }.property('selectedDB','sqlitePath','mysqlUser','mysqlPassword','mysqlHost','mysqlDB'),

    doneEditing: function() {
      var d = {
        dbtype: this.get('selectedDB'),
        sqlitePath: this.get('sqlitePath'),
        mysqlUser: this.get('mysqlUser'),
        mysqlHost: this.get('mysqlHost'),
        mysqlPassword: this.get('mysqlPassword'),
        mysqlDB: this.get('mysqlDB'),
        importDemo: this.get('importDemo'),
        installHtaccess: this.get('installHtaccess'),
        pdoString: this.get('pdoString')
      };
      $.ajax({type:"POST", url:"setup.php", data:jQuery.param(d), dataType:"json"}).then(function(response) {
        console.log(response);
        if (typeof response.errorMsg != "undefined")
          humane.log('<i class="glyphicon glyphicon-fire"></i> Save failed: '+response.errorMsg, { timeout: 0, clickToClose: true, addnCls: 'humane-error'});
        else {
          humane.log('<i class="glyphicon glyphicon-saved"></i> Saved successfully! Warping into amtc-web!', { timeout: 1500 });
          window.setTimeout( function(){
            window.location.href = 'index.html';
          }, 2000);
        }
      }, function(response){
        console.log("what happened?");
        //humane.log('<i class="glyphicon glyphicon-fire"></i> Failed to save! Please check console.',
          //{ timeout: 0, clickToClose: true, addnCls: 'humane-error' });
      });
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
