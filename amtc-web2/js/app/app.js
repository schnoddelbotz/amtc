/*
 *
 *
 * amtc-web EmberJS app
 *
 * http://emberjs.com/guides/concepts/naming-conventions/
 * http://ember-addons.github.io/bootstrap-for-ember/ ?
 *
 */ 


var attr = DS.attr;
var hasMany = DS.hasMany;
var App = Ember.Application.create({
  // http://discuss.emberjs.com/t/equivalent-to-document-ready-for-ember/2766
  ready: function() {
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

    // AMTCWEB_IS_CONFIGURED gets defined via included script rest-api.php/rest-config.js
    if (typeof AMTCWEB_IS_CONFIGURED != 'undefined' && AMTCWEB_IS_CONFIGURED===false && !window.location.hash.match('#/pages')) {
      // unconfigured system detected. inform user and relocate to setup.php
      humane.log('<i class="glyphicon glyphicon-fire"></i> '+
                 'No configuration file found!<br>warping into setup ...', { timeout: 3000 });
      window.setTimeout( function(){
        window.location.href = '#setup'; // how to use transitionToRoute here?
      }, 3100);
    }

    // just for demo... we have a flashing bolt as progress indicator :-)
    window.setTimeout( function(){
      $('#bolt').removeClass('flash');
    }, 1500);
    // to trigger flash on ajax activity
    $(document).ajaxStart(function () {
      $('#bolt').addClass('flash');
    });
    // and to calm it down again when done
    $(document).ajaxStop(function () {
      $('#bolt').removeClass('flash');
    });
  }
});

// Routes 

App.Router.map(function() {
  this.route('setup');
  this.resource('logs');
  this.resource('energy');
  this.resource('schedule');

  this.resource('ous', function() {
    this.route('new');
  });
  this.resource('ou', { path: '/ou/:id' }, function() {
    this.route('edit');
    this.route('newhost');
    this.route('monitor');
    this.resource('hosts', function() {
      
    });
  });

  this.resource('optionsets', function() {
    this.route('new');
  });
  this.resource('optionset', { path: '/optionset/:id' }, function() {
    this.route('edit');
  });
  
  this.resource('pages', function() {
    this.resource('page', { path: ':id' });
  });
});

Ember.Route.reopen({
  // http://stackoverflow.com/questions/13120474/emberjs-scroll-to-top-when-changing-view
  render: function(controller, model) {
    this._super();
    window.scrollTo(0, 0);
  }
});

App.PageRoute = Ember.Route.extend({
  model: function(params) {
    console.log("PageRoute model() fetching single page");
    return this.store.find('page', params.id);
  }
});

App.OuRoute = Ember.Route.extend({
  model: function(params) {
    console.log("App.OuRoute model(), find and set currentOU -> " + params.id);
    this.set('currentOU', params.id); // hmm, unneeded? better...how?
    return this.store.find('ou', params.id);
  },
});
App.OusRoute = Ember.Route.extend({
  model: function(params) {
    console.log("App.OusRoute model(), FETCH OUS");
    return this.store.find('ou');
  }
});
App.OusNewRoute = Ember.Route.extend({
  model: function() {
    console.log("OusNewRoute model() creating new OU");
    return this.store.createRecord('ou');
  }
});
App.HostsRoute = Ember.Route.extend({
  model: function(params) {
    // the monitor needs the hosts. hmmmm. am i doing right here? :-/
    console.log("HostRoute model(): fetch hosts");
    console.log(params);
    return this.store.find('host', params.id); /*  FIXME: + , this.get('ou.id') ??? */
    // FIXME ember-inspector shows one nulled record
  }
}); 

App.OptionsetRoute = Ember.Route.extend({
  model: function(params) {
    console.log("OptionsetRoute model() for id " + params.id);
    //this.set('currentOU', params.id); // hmm, unneeded? better...how?
    return this.store.find('optionset', params.id);
  },
});
App.OptionsetsRoute = Ember.Route.extend({
  model: function() {
    console.log("OptionsetsRoute model() fetching optionsets");
    return this.store.find('optionset');
  }
});
App.OptionsetsNewRoute = Ember.Route.extend({
  model: function() {
    console.log("OptionsetsNewRoute model() creating new optionset");
    return this.store.createRecord('optionset');
  }
});
App.NotificationsRoute = Ember.Route.extend({
  model: function() {
    console.log("NotificationsRoute model() fetching notifications");
    return this.store.find('notification');
  }
});


// Views

App.ApplicationView = Ember.View.extend({
  didInsertElement: function() {
    $('#side-menu').metisMenu(); // initialize metisMenu 
  }
});
App.IndexView = Ember.View.extend({
  templateName: 'index',
  didInsertElement: function() {

  // in sb-admin-2 demo, this came in via morris-data.js
  // should be retreived via REST in real life...
  Morris.Area({
    element: 'morris-area-chart',
    data: [{
        period: '2012-02-24 05:45',
        windows: 6,
        linux: null,
        unreachable: 2
    }, {
        period: '2012-02-24 06:00',
        windows: 13,
        linux: 4,
        unreachable: 4
    }, {
        period: '2012-02-24 06:15',
        windows: 20,
        linux: 7,
        unreachable: 3
    }, {
        period: '2012-02-24 06:30',
        windows: 54,
        linux: 12,
        unreachable: 14
    }, {
        period: '2012-02-24 06:45',
        windows: 112,
        linux: 27,
        unreachable: 4
    }, {
        period: '2012-02-24 07:00',
        windows: 140,
        linux: 57,
        unreachable: 3
    }, {
        period: '2012-02-24 07:15',
        windows: 70,
        linux: 90,
        unreachable: 70
    }, {
        period: '2012-02-24 07:30',
        windows: 140,
        linux: 110,
        unreachable: 0
    }, {
        period: '2012-02-24 07:45',
        windows: 120,
        linux: 80,
        unreachable: 0
    }, {
        period: '2012-02-24 08:00',
        windows: 120,
        linux: 67,
        unreachable: 13
    }],
    xkey: 'period',
    ykeys: ['linux', 'unreachable', 'windows'],
    labels: ['Linux', 'unreachable', 'Windows'],
    pointSize: 2,
    hideHover: 'auto',
    resize: true
    });
  }   
});

// Controllers
// see http://emberjs.com/guides/routing/generated-objects/
App.ApplicationController = Ember.Controller.extend({
  appName: 'amtc-web', // available as {{appName}} throughout app template
  needs: ["ou","ous"],

  // the initial value of the `search` property
  search: '',
  actions: {
    query: function() {
      // the current value of the text field
      var query = this.get('search');
      this.transitionToRoute('search', { query: query });
    },
    selectNode: function(node) {
      console.log('TreeMenuComponent node: ' + node);
      this.set('selectedNode', node.get('id'));
      this.transitionToRoute('ou.monitor', node.get('id') )
    }

  },
});
App.IndexController = Ember.ObjectController.extend({
  needs: ["notifications"],
  xxx: Ember.computed.alias("controllers.notifications"),

  /*notifications: function() {
    return this.get('store').find('notification');
  }.property(),
  */
  ouTree: null, // fixme. remove.
});
// Index page notification messages ('job completed') et al
App.NotificationsController = Ember.ArrayController.extend({
  notifications: function() {
    console.log("NotificationsController notifications() - fetching.");
    return this.get('store').find('notification');
  }.property()
});
// Organizational Units
App.OuController = Ember.ObjectController.extend({
  needs: ["optionsets","ous"],
  currentOU: null,
  isEditing: false,
  ouTree: null,
});
App.OuEditController = Ember.ObjectController.extend({
  needs: ["optionsets","ous"],
  actions: {
    removeOu: function () {
      if (confirm("Really delete this OU?")) {
        console.log('FINALLY Remove it' + this.get('controllers.ous.ous'));
        var device = this.get('model');
        console.log("DEV id: "+device.id);
        console.log("DEL: "+device.get('isDeleted'));
        device.deleteRecord();
        console.log("DEL: "+device.get('isDeleted'));

        device.save().then(function(x) {
          console.log('DELETE SUCCESS');
          humane.log('<i class="glyphicon glyphicon-saved"></i> Deleted successfully',
            { timeout: 1500, clickToClose: false });
          console.log("FIXME - transtionToRoute doesnt work here...");
          window.location.href = '#/ous';
        }, function(response){
          var res = jQuery.parseJSON(response.responseText);
          var msg = (typeof res.exceptionMessage=='undefined') ? 
                    'Check console, please.' : res.exceptionMessage;
          humane.log('<i class="glyphicon glyphicon-fire"></i> Ooops! Fatal error:'+
                     '<p>'+msg+'</p>', { timeout: 0, clickToClose: true });
          device.rollback();
        });
      }
    },

    edit: function() {
      this.set('isEditing', true);
    },

    doneEditingReturn: function() {
      this.set('isEditing', false);
      this.get('model').save().then(function() {
        humane.log('<i class="glyphicon glyphicon-saved"></i> Saved successfully',
            { timeout: 800 });
        window.location.href = '#/ous';
      }, function(response){
          var res = jQuery.parseJSON(response.responseText);
          var msg = (typeof res.exceptionMessage=='undefined') ? 
                    'Check console, please.' : res.exceptionMessage;
          humane.log('<i class="glyphicon glyphicon-fire"></i> Ooops! Fatal error:'+
                     '<p>'+msg+'</p>', { timeout: 0, clickToClose: true });
      } );
    }
  } 
});
App.OusController = Ember.ArrayController.extend({
  ous: function() {
    console.log("OusController ous() - fetching.");
    return this.get('store').find('ou');
  }.property()
});
App.OusIndexController = Ember.ObjectController.extend({
  needs: ["ous"],
});
App.OusNewController = App.OuController; // FIXME: evil?
// Client PCs
App.HostsController = Ember.ArrayController.extend({
  hosts: function() {
    console.log("HostsController hosts() - fetching.");
    return this.get('store').find('host');
  }.property()
});
App.OuMonitorController = Ember.ObjectController.extend({
  needs: ["hosts","ous"]
});
// AMT Optionsets
App.OptionsetController = Ember.ObjectController.extend({
  needs: ["optionsets"],
  currentOU: null,
  isEditing: false,
  ouTree: null,

  actions: {
    removeOptionset: function () {
      if (confirm("Really delete this optionset?")) {
        console.log('FINALLY Remove it');
        var device = this.get('model');
        device.deleteRecord();
        device.save().then(function() {
          humane.log('<i class="glyphicon glyphicon-saved"></i> Deleted successfully',
            { timeout: 1500, clickToClose: false });
          console.log("FIXME - transtionToRoute doesnt work here...");
          window.location.href = '#/optionsets';
        }, function(response){
          var res = jQuery.parseJSON(response.responseText);
          var msg = (typeof res.exceptionMessage=='undefined') ?
                    'Check console, please.' : res.exceptionMessage;
          humane.log('<i class="glyphicon glyphicon-fire"></i> Ooops! Fatal error:'+
                     '<p>'+msg+'</p>', { timeout: 0, clickToClose: true });
        }
      )};
    },

    becameError: function() {
      alert("This does not work... elsewhere?");
    },

    edit: function() {
      this.set('isEditing', true);
    },

    doneEditingReturn: function() {
      this.set('isEditing', false);
      console.log(this.get('model'));
      this.get('model').save().then(function() {
        humane.log('<i class="glyphicon glyphicon-saved"></i> Saved successfully',
          { timeout: 800 });
        window.location.href = '#/optionsets';
      }, function(response){
        var res = jQuery.parseJSON(response.responseText);
        var msg = (typeof res.exceptionMessage=='undefined') ? 
                   'Check console, please.' : res.exceptionMessage;
        humane.log('<i class="glyphicon glyphicon-fire"></i> Ooops! Fatal error:'+
                   '<p>'+msg+'</p>', { timeout: 0, clickToClose: true });
        device.rollback();
        }
      );
    }
  }  
});
App.OptionsetsNewController = App.OptionsetController; // FIXME: evil?
App.OptionsetsController = Ember.ArrayController.extend({
  optionsets: function() {
    return this.get('store').find('optionset');
  }.property()
});
// Controller for /#setup (Installer)
App.SetupController = Ember.ObjectController.extend({  
  // Controller used for initial installation page #setup
  selectedDB: null,
  sqlitePath: null,
  mysqlUser: null,
  mysqlHost: null,
  mysqlPassword: null,
  mysqlDB: null,
  importDemo: true,
  installHtaccess: null,

  dbs: [
    "SQLite",
    "MySQL",
    "Oracle",
    "PostgreSQL"
  ],
  
  isMySQL: function() {
    return (this.get('selectedDB')=='MySQL') ? true : false;
  }.property('selectedDB'),    
  isSQLite: function() {
    return (this.get('selectedDB')=='SQLite') ? true : false;
  }.property('selectedDB'),
  isOracle: function() {
    return (this.get('selectedDB')=='Oracle') ? true : false;
  }.property('selectedDB'),
  isPostgreSQL: function() {
    return (this.get('selectedDB')=='PostgreSQL') ? true : false;
  }.property('selectedDB'),

  pdoString: function() {
    if (this.get('selectedDB')=='MySQL') {
      return 'mysql://' + this.get('mysqlUser') + ':' + this.get('mysqlPassword') + "@" + this.get('mysqlHost') + "/" + this.get('mysqlDB');
    } else {
      return 'sqlite:' + this.get('sqlitePath');
    }
  }.property('selectedDB','sqlitePath','mysqlUser','mysqlPassword','mysqlHost','mysqlDB'),

  doneEditing: function() {
    var d = {
      selectedDB: this.get('selectedDB'),
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
      console.log(response.responseText);
      if (response.responseText=='INSTALLTOOL_LOCKED') {
        humane.log('<i class="glyphicon glyphicon-fire"></i> Setup is LOCKED!<br>'+
          'Setup is intended for initial installation only.<br>'+
          'Remove <code>data/siteconfig.php</code> to re-enable setup.',
          { timeout: 0, clickToClose: true, addnCls: 'humane-error' });  
      } else {
        humane.log('<i class="glyphicon glyphicon-fire"></i> Failed to save! Please check console.'+response.responseText,
          { timeout: 0, clickToClose: true, addnCls: 'humane-error' });
      }
    });
  }
}); 

// Models

// Organizational Unit
App.Ou = DS.Model.extend({
  name: attr('string'),
  description: attr('string'),
  parent_id: DS.belongsTo('ou', {inverse: 'children'}),
  optionset_id: DS.belongsTo('optionset'),
  ou_path: attr('string'),
  children: DS.hasMany('ou', {inverse: 'parent_id'}),
  hosts: DS.hasMany('host'),
 
  /// FIXME FIXME ... still feels hackish, but makes the dropdown+save work...
  optionsetid: function(key,value) {
    if (value) { 
       //this.set('optionset_id',value);
      return value; 
    }
    else {
      console.log('get optionset -> ' + this.get('optionset_id.id'));
      return this.get('optionset_id');
    }
  }.property('optionset_id'),
  
  // new ou-tree; 1:1 from https://github.com/joachimhs/Montric/blob/master/Montric.View/src/main/webapp/js/app/models/MainMenuModel.js
  isSelected: false,
  isExpanded: false,
  isRootLevel: function() {
    return this.get('parent_id.id')==1 ? true : false; /// OH SOOOO HACKISH
  }.property('children').cacheable(),
  hasChildren: function() {
    return this.get('children').get('length') > 0;
  }.property('children').cacheable(),
  isLeaf: function() {
    return this.get('children').get('length') == 0;
  }.property('children').cacheable(),
  isExpandedObserver: function() {
    console.log('isExpanded: ' + this.get('id'));
    if (this.get('isExpanded')) {
      var children = this.get('children.content');
      if (children) {
        //console.log('Sorting children');
        children.sort(App.Ou.compareNodes);
      }
    }
  }.observes('isExpanded')
});
App.Ou.reopenClass({
  compareNodes: function(nodeOne, nodeTwo) {
    if (nodeOne.get('id') > nodeTwo.get('id'))
        return 1;
    if (nodeOne.get('id') < nodeTwo.get('id'))
        return -1;
    return 0;
  }
});
// Clients/Hosts
App.Host = DS.Model.extend({
  ou_id: DS.belongsTo('ou'),
  hostname: attr('string')
  // add isSelected et al
});
// Markdown help / documentation pages
App.Page = DS.Model.extend({
  page_name: attr('string'),
  page_title: attr('string'),
  page_content: attr('string'),
});
// Notification center messages
App.Notification = DS.Model.extend({
  ntype: attr('string'),
  tstamp: attr('string'),
  message: attr('string'),
  cssClass: function(key,value) {
    if (!value) {
      var cc = "fa fa-"+this.get('ntype')+" fa-fw";
      return cc;
    }
  }.property()
});
// AMT Option sets
App.Optionset = DS.Model.extend({
  name: attr('string'),
  description: attr('string'),
  sw_dash: attr('boolean'),
  sw_v5: attr('boolean'),
  sw_scan22: attr('boolean'),
  sw_scan3389: attr('boolean'),
  sw_usetls: attr('boolean'),
  sw_skipcertchk: attr('boolean'),
  opt_maxthreads: attr('string'),
  opt_timeout: attr('string'),
  opt_passfile: attr('string'),
  opt_cacertfile: attr('string')
});

// Components (menu tree...)

App.TreeMenuNodeComponent = Ember.Component.extend({
  classNames: ['pointer','nav'],
  tagName: 'li',
  actions: {
    toggleExpanded: function() {
      this.toggleProperty('node.isExpanded');
    },
    toggleSelected: function() {
      this.toggleProperty('node.isSelected');
    },
    selectNode: function(node) {
      //console.log('selectedNode: ' + node);
      this.sendAction('action', node);
    }
  },
  isSelected: function() {
    //console.log("'" + this.get('selectedNode') + "' :: '" + this.get('node.id') + "'");
    return this.get('selectedNode') === this.get('node.id');
  }.property('selectedNode', 'node.id')
});

// Handlebars helpers

// markdown to html conversion
var showdown = new Showdown.converter();
Ember.Handlebars.helper('format-markdown', function(input) {
  if (input) {
    var md = showdown.makeHtml(input);
    md = md.replace("<h1 id=",'<h1 class="page-header" id=');
    var html = new Handlebars.SafeString(md);    
    return html;
  } else {
    console.log("Warning: empty input on showdown call.");
    return input;
  }
});

// print fontAwesome checkmarks for input true/false
Ember.Handlebars.helper('check-mark', function(input) {
  return input ?
    new Handlebars.SafeString(showdown.makeHtml('<i class="fa fa-check-square-o"></i> ')) :
    new Handlebars.SafeString(showdown.makeHtml('<i class="fa fa-square-o"></i> '));
});

// moment.js PRETTY timestamps
Ember.Handlebars.helper('format-from-now', function(date) {
  return moment.unix(date).fromNow();
});
