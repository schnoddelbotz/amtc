/*
 *
 *
 * amtc-web EmberJS app
 *
 * http://emberjs.com/guides/concepts/naming-conventions/
 * http://ember-addons.github.io/bootstrap-for-ember/ ?
 *
 */ 


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

var attr = DS.attr;
var hasMany = DS.hasMany;

/*
 * Routes 
 */
App.Router.map(function() {
  this.resource('about');
  this.resource('logs');
  this.resource('energy');
  this.resource('schedule');
  this.resource('setup');
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

App.ApplicationRoute = Ember.Route.extend({
  setupController: function(controller,model) {
    console.log('Entered App.ApplicationRoute, triggering load of OUs');
    window.scrollTo(0, 0);
    this._super(controller,model);
      var p=this;
      $.ajax( { url: "rest-api.php/ou-tree", type: "GET" }).then(
        function(response) {
          var unconfigured = typeof response.exceptionMessage == 'undefined' ? false : response.exceptionMessage;
          if (unconfigured == 'unconfigured') {
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
App.IndexRoute = Ember.Route.extend({
  enter: function() {
    console.log("Entered App.IndexRoute");
    window.scrollTo(0, 0);
  }
});
App.PageRoute = Ember.Route.extend({
  enter: function() { window.scrollTo(0, 0); },
  model: function(params) {
    console.log("Page route");
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
  model: function() {
    console.log("OusRoute model()");
    return this.store.find('ou');
  },
});
App.OusIndexRoute = Ember.Route.extend({
  enter: function() {
    // stack.../questions/13120474/emberjs-scroll-to-top-when-changing-view
    console.log("OusIndexRoute enter()");
    window.scrollTo(0, 0);
  }
});
App.OusNewRoute = Ember.Route.extend({
  model: function() {
    console.log("New OU route");
    return this.store.createRecord('ou');
  }
});
App.OptionsetRoute = Ember.Route.extend({
  model: function(params) {
    console.log("App.OptionsetRoute -> " + params.id);
    //this.set('currentOU', params.id); // hmm, unneeded? better...how?
    return this.store.find('optionset', params.id);
  },
});
/* not needed
App.OptionsetsRoute = Ember.Route.extend({
  model: function() {
    console.log("App.OptionsetsRoute");
    return this.store.find('optionset');
  }
});
*/
App.OptionsetsIndexRoute = Ember.Route.extend({
  enter: function() {
    // stack.../questions/13120474/emberjs-scroll-to-top-when-changing-view
    window.scrollTo(0, 0);
    console.log("Optionsets INDEX route!");
  },
  model: function() {
    console.log("App.OptionsetsIndexRoute");
    return this.store.find('optionset');
  }
});
App.OptionsetsNewRoute = Ember.Route.extend({
  enter: function() {
    // stack.../questions/13120474/emberjs-scroll-to-top-when-changing-view
    console.log("OptionsetsNewRoute enter()");
  },
  /*
  FIXME - currently breaks New optionset creation.
  Y?  Uncaught Error: More context objects were passed than there are dynamic segments for the route: ous.index jslibs.js:2766 
  */
  //
  model: function() {
    console.log("OptionsetsNewRoute model()");
    return this.store.createRecord('optionset');
  }
  //
}); 

/*
 * Views
 */
App.ApplicationView = Ember.View.extend({
  didInsertElement: function() {
    // broken, should be done by ember (was done by sb-admin-2.js before):
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

  // the initial value of the `search` property
  search: '',

  actions: {
    query: function() {
      // the current value of the text field
      var query = this.get('search');
      this.transitionToRoute('search', { query: query });
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

  needs: ["optionset","ous"],

  optionsets: function() { //// ???????
    console.log("OusIndexRoute optionsets()");
    return this.get('store').find('optionset');
  }.property(),

  currentOU: null,
  isEditing: false,
  ouTree: null,

  actions: {
    removeOu: function (device) {
      if (confirm("Really delete this device?")) {
        console.log('FINALLY Remove it');
        var device = this.get('model');
        device.deleteRecord();
        device.save().then(function(device) {
          humane.log('<i class="glyphicon glyphicon-saved"></i> Deleted successfully',
            { timeout: 1500, clickToClose: false });
          console.log("FIXME - transtionToRoute doesnt work here...");
          window.location.href = '#/ous';
        }, function(device){
          humane.log('<i class="glyphicon glyphicon-fire"></i> Delete failed! Please reload page.',
            { timeout: 0, clickToClose: true, addnCls: 'humane-error' });
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
      }/*, function(ou){
        humane.log('<i class="glyphicon glyphicon-fire"></i> Failed to save! Please reload page.',
            { timeout: 0, clickToClose: true, addnCls: 'humane-error' });
      } -- why broken? -- */);
    }
  } 
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
        }, function(device){
          humane.log('<i class="glyphicon glyphicon-fire"></i> Delete failed! Please reload page.',
            { timeout: 0, clickToClose: true, addnCls: 'humane-error' });
      });
      }
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
 
  //optionsetidValue: attr('number'),
  /// FIXME FIXME ... still feels hackish, but makes the dropdown+save work...
  optionsetid: function(key,value) {
    if (value) { 
      //console.log('optionsetidValue set '+value.id); 
      //this.set('optionsetidValue',value.id); 
      //this.set('optionset_id',value); // this happens, but i don't want it :-(
      return value; 
    }
    else {
      console.log('get optionset -> ' + this.get('optionset_id.id'));
      return this.get('optionset_id');
    }
  }.property('optionset_id'),
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
 * Components (used by OU atm)
 */
App.TreeBranchComponent = Ember.Component.extend({
  tagName: 'ul',
  classNames: ['tree-branch', 'nav', /*'collapse',*/ ]
});
App.TreeNodeComponent = Ember.Component.extend({
  tagName: 'li',
  isExpanded: true,
  toggle: function() {
    this.toggleProperty('isExpanded');
  },
  didClick: function() {
    console.log('You clicked: '+this.get('node.text'));
  }
  
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
