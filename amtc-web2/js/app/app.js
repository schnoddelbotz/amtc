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


/*
 * Routes 
 */
App.Router.map(function() {
  this.resource('logs');
  this.resource('energy');
  this.resource('schedule');
  this.resource('ous', function() {
    this.resource('ou', { path: ':id' });
    this.route('new');
  });
  this.resource('optionsets', function() {
    this.resource('optionset', { path: ':id' });
    this.route('new');
  });
  this.resource('pages', function() {
    this.resource('page', { path: ':id' });
  });
  this.resource('monitors', function() {
    this.resource('monitor', { path: ':id' });
  });
});

// http://stackoverflow.com/questions/13120474/emberjs-scroll-to-top-when-changing-view
Ember.Route.reopen({
  render: function(controller, model) {
    this._super();
    window.scrollTo(0, 0);
  }
});

App.ApplicationRoute = Ember.Route.extend({
  model: function() {
    console.log("ApplicationRoute model() fetching ous");
    return this.store.find('ou');
  },
  setupController: function(controller,model) {
    console.log('ApplicationRoute setupController() triggering load of ou-tree');    
    this._super(controller,model);
      var p=this;
      $.ajax( { url: "rest-api.php/ou-tree", type: "GET" }).then(
        function(response) {
          var unconfigured = typeof response.exceptionMessage == 'undefined' ? false : response.exceptionMessage;
          if (window.location.hash.match('#/pages')) {
              console.log("Ok, unconfigured, but you may read the docs.");
              // FIXME in setup.php: should fetch them via rest-api directly!
          }
          else if (unconfigured == 'unconfigured') {
            // unconfigured system detected. relocate to setup.php
            humane.log('<i class="glyphicon glyphicon-fire"></i> '+
                       'Unconfigured - warping into setup...!', { timeout: 2000 });
            window.setTimeout( function(){
              window.location.href = 'setup.php';
            }, 2100);
          } else {
            // SUCCESS! OU tree received.
            controller.set('ouTree', response.ous);
            //console.log('App.ApplicationRoute received OUs successfully');
          }
        },
        function(response){
            // other error, like DB down
          var res = jQuery.parseJSON(response.responseText);
          var msg = (typeof res.exceptionMessage=='undefined') ?
                    'Check console, please.' : res.exceptionMessage;
          humane.log('<i class="glyphicon glyphicon-fire"></i> Ooops! Fatal error:'+
                     '<p>'+msg+'</p>', { timeout: 0, clickToClose: true });
        }
      );
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
    console.log("App.OuRoute model(), set currentOU -> " + params.id);
    this.set('currentOU', params.id); // hmm, unneeded? better...how?
    return this.store.find('ou', params.id);
  },
});
App.OusRoute = Ember.Route.extend({
/*
  model: function() {
    console.log("OusRoute model() fetching ous");
    return this.store.find('ou');
  },
*/ // already done by AppRoute, as OUs are required for menu
});
App.OusNewRoute = Ember.Route.extend({
  model: function() {
    console.log("OusNewRoute model() creating new OU");
    return this.store.createRecord('ou');
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
  //
});

App.MonitorRoute = Ember.Route.extend({
  model: function(params) {
    console.log("MonitorRoute model(): set currentOU -> " + params.id + " and fetch ou data");
    this.set('currentOU', params.id); // hmm, unneeded? better...how?
    return this.store.find('ou', params.id);
  }
}); 


/*
 * Views
 */

App.ApplicationView = Ember.View.extend({
  didInsertElement: function() {
    console.log("App.ApplicationView.didInsertElement() initializing metisMenu");
    $('#side-menu').metisMenu(); //<---- FIXME DO IT HERE ALWAYS ... until kicked out finally
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

/*
 * Controller
 */

App.ApplicationController = Ember.Controller.extend({
  appName: 'amtc-web', // available as {{appName}} throughout app template
  needs: ["ous"],

  ous: function() {
    return this.get('store').find('ou');
  }.property(),

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
      this.transitionToRoute('monitor', node.get('id') )
    }

  }
});
App.IndexController = Ember.ObjectController.extend({
  needs: ["Notifications"],
  notifications: function() {
    return this.get('store').find('notification');
  }.property(),
  ouTree: null,
});

App.NotificationsController = Ember.ObjectController.extend({
});
App.OuController = Ember.ObjectController.extend({
  needs: ["optionsets","ous"],
  currentOU: null,
  isEditing: false,
  ouTree: null,

  ous: function() {
    return this.get('store').find('ou');
  }.property(),

  actions: {
    removeOu: function (device) {
      if (confirm("Really delete this device?")) {
        console.log('FINALLY Remove it');
        var device = this.get('model');
        device.deleteRecord();
        device.save().then(function() {
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
      }/*, function(ou){                      //// FIXME !!!!
        humane.log('<i class="glyphicon glyphicon-fire"></i> Failed to save! Please reload page.',
            { timeout: 0, clickToClose: true, addnCls: 'humane-error' });
      } -- why broken? -- */);
    }
  } 
});
App.OusController = Ember.ObjectController.extend({
});
App.OusIndexController = Ember.ObjectController.extend({
  needs: ["ous"],
  ous: function() {
    return this.get('store').find('ou');
  }.property(),
});
App.OusNewController = App.OuController; // FIXME: evil?
App.OptionsetController = Ember.ObjectController.extend({

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
      }/*, function(device){
        humane.log('<i class="glyphicon glyphicon-fire"></i> Failed to save! Please reload page.',
            { timeout: 0, clickToClose: true, addnCls: 'humane-error' });
      }*/);
    }
  }  
});
App.OptionsetsNewController = App.OptionsetController; // FIXME: evil?
App.OptionsetsController = Ember.ObjectController.extend({
  optionsets: function() {
    return this.get('store').find('optionset');
  }.property(),
});

/*
 * DS Models
 */

// Organizational Unit
App.Ou = DS.Model.extend({
  name: attr('string'),
  description: attr('string'),
  parent_id: DS.belongsTo('ou'),
  optionset_id: DS.belongsTo('optionset'),
  ou_path: attr('string'),
  children: DS.hasMany('ou'), // NEW XXX ou-tree ...
 
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
  isExpanded: true,
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

/*
 * Components (menu tree...)
 */

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

/*
 * Handlebars helpers
 */

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
