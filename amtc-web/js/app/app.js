/*
 * js/app.js - part of amtc-web, part of amtc
 * https://github.com/schnoddelbotz/amtc
 *
 * Use emberjs and ember-data to create our ambitious website.
 *
 * Bookmarks...
 *  http://emberjs.com/guides/concepts/naming-conventions/
 *  http://ember-addons.github.io/bootstrap-for-ember/
 *  http://emberjs.com/guides/routing/generated-objects/
 */

'use strict';

var attr = DS.attr;
var hasMany = DS.hasMany;

var App = Ember.Application.create({
  //LOG_TRANSITIONS: true, // basic logging of successful transitions
  //LOG_TRANSITIONS_INTERNAL: true, // detailed logging of all routing steps
  //LOG_RESOLVER: true,
  // http://discuss.emberjs.com/t/equivalent-to-document-ready-for-ember/2766
  ready: function() {
    // turn off splash screen
    if (App.readCookie('isLoggedIn')) {
      $('#splash').hide();
      $('#backdrop').hide();
    } else {
      window.setTimeout( function(){
        $('#splash').fadeOut(1200);
        $('#backdrop').fadeOut(1000);
      }, 750);
    }
    $(window).bind("load resize", function(){
      App.windowResizeHandler();
    });

    // AMTCWEB_IS_CONFIGURED gets defined via included script rest-api.php/rest-config.js
    if (typeof AMTCWEB_IS_CONFIGURED != 'undefined' && AMTCWEB_IS_CONFIGURED===false && !window.location.hash.match('#/page')) {
      // unconfigured system detected. inform user and relocate to setup.php
      humane.log('<i class="fa fa-meh-o"></i> '+
                 'No configuration file found!<br>warping into setup ...', { timeout: 3000 });
      window.location.href = '#/setup'; // how to use transitionToRoute here?
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
  },
  // for zero-padding numbers
  pad: function(number, length) {
    return (number+"").length >= length ?  number + "" : this.pad("0" + number, length);
  },
  successMessage: function(messageText,faClass,caller,redirTo) {
    humane.log('<i class="fa fa-'+faClass+'"></i> '+messageText, { timeout: 1500, clickToClose: false });
    window.location.href = '#/'+redirTo;
  },
  // SB-Admin 2 responsiveness helper
  windowResizeHandler: function() {
    var topOffset = 50;
    var width = (window.innerWidth > 0) ? window.innerWidth : screen.width;
    if (width < 768) {
      $('div.navbar-collapse').addClass('collapse');
      topOffset = 100; // 2-row-menu
    } else {
      $('div.navbar-collapse').removeClass('collapse');
    }
    var height = (window.innerHeight > 0) ? window.innerHeight : screen.height;
    height = height - topOffset;
    if (height < 1) height = 1;
    if (height > topOffset) {
      $("#page-wrapper").css("min-height", (height) + "px");
    }
  },
  ensureLoginForTarget: function(that,transition) {
    if (!App.readCookie('isLoggedIn')) {
      that.controllerFor('login').set('isLoggedIn',0);
      that.controllerFor('login').set('previousTransition', transition);
      that.transitionTo('login');
    } else {
      that.controllerFor('login').set('isLoggedIn',1);
    }
  },
  // 1:1 copy, THANKS! https://github.com/joachimhs/Montric/blob/master/Montric.View/src/main/webapp/js/app.js
  createCookie: function(name, value, days) {
    if (days) {
      var date = new Date();
      date.setTime(date.getTime()+(days*24*60*60*1000));
      var expires = "; expires="+date.toGMTString();
    }
    else var expires = "";
    document.cookie = name+"="+value+expires+"; path=/";
  },
  readCookie:function (name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
      var c = ca[i];
      while (c.charAt(0) == ' ') c = c.substring(1, c.length);
      if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
  },
  eraseCookie:function (name) {
    this.createCookie(name, "", -1);
  }
});

Ember.onerror = function(err) {
  var msg = 'Unkown error occured: '+ err;
  if (typeof err.errors !== 'undefined') {
    msg = err + '<p class="errDetails">' + err.errors[0].detail + '</p>';
  }
  humane.log('<i class="fa fa-frown-o"></i> '+msg, { timeout: 0, clickToClose: true });
}

// default Adapter for emberjs 2.0 is JSONAdapter - override it (until migrated?)
App.ApplicationAdapter = DS.RESTAdapter.extend({});

// Routes

App.Router.map(function() {
  this.route('login');
  this.route('setup');
  this.route('logs');
  this.route('energy');
  this.route('systemhealth');
  this.route('page', { path: '/page/:id' });

  this.route('ous', function() {
    this.route('new');
  });
  this.route('ou', { path: '/ou/:id' }, function() {
    this.route('edit');
    this.route('hosts');
    this.route('monitor');
    this.route('statelog');
  });

  this.route('users', function() {
    this.route('new');
  });
  this.route('user', { path: '/user/:id' }, function() {
    this.route('edit');
  });

  this.route('optionsets', function() {
    this.route('new');
  });
  this.route('optionset', { path: '/optionset/:id' }, function() {
    this.route('edit');
  });

  this.route('schedules', function() {
    this.route('new');
  });
  this.route('schedule', { path: '/schedule/:id' }, function() {
    this.route('edit');
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
    // fetches static markdown files from server
    // recycled from markdownBrauser
    var store = this.store;
    var url = 'page/'+ params.id + '.md';
    var existantRecord = null;
    var test = store.peekAll('page');
    test.forEach(function(item) {
      if (item.id === params.id) {
        existantRecord = item;
      }
    });
    if (existantRecord) {
      return existantRecord;
    } else {
      return Ember.$.get(url).then(function(data) {
        var page = {
          'id': params.id,
          'page_content': data,
          'file_name': params.id+'.md'
        };
        // create a real e-d record to enjoy computed propoerties
        var record = store.createRecord('page', page);
        return record;
      });
    }
  }
});
App.OuRoute = Ember.Route.extend({
  model: function(params) {
    this.set('currentOU', params.id); // hmm, unneeded? better...how?
    return this.store.find('ou', params.id);
  },
  beforeModel: function(t) {App.ensureLoginForTarget(this,t);}
});
App.IndexRoute = Ember.Route.extend({
  setupController: function(controller, model) {
    var self = this;
    self._super(controller, model);
    if (Ember.isNone(self.get('pollster'))) {
      self.set('pollster', App.Pollster.create({
        onPoll: function() {
          self.send('refresh');
        }
      }));
    }
    self.get('pollster').start();
  },
  // This is called upon exiting the Route
  deactivate: function() {
    this.get('pollster').stop();
  },
  actions: {
    refresh: function() {
        // would be model.refresh(), but we need it for the laststates model...
        this.store.find('laststate');
        this.store.find('notification'); // me too!
    }
  },
  beforeModel: function(t) {App.ensureLoginForTarget(this,t);}
});
App.OuMonitorRoute = Ember.Route.extend({
  setupController: function(controller, model) {
    var self = this;
    self._super(controller, model);
    if (Ember.isNone(self.get('pollster'))) {
      self.set('pollster', App.Pollster.create({
        onPoll: function() {
          self.send('refresh');
        }
      }));
    }
    self.get('pollster').start();
  },
  afterModel: function() {
    this.controllerFor("ouMonitor").send('updateSelectedHosts');
    this.controllerFor("ouMonitor").set('selectedHostsCount',0);
    $("#hselect div").removeClass("isActive");
  },
  // This is called upon exiting the Route
  deactivate: function() {
    this.get('pollster').stop();
  },
  actions: {
    refresh: function() {
        // would be model.refresh(), but we need it for the laststates model...
        this.store.find('laststate');
        // this.store.find('notification'); // me too!
    }
  },
  beforeModel: function(t) {App.ensureLoginForTarget(this,t);}
});
App.OusRoute = Ember.Route.extend({
  model: function(params) {
    return this.store.find('ou');
  },
  beforeModel: function(t) {App.ensureLoginForTarget(this,t);}
});
App.OusNewRoute = Ember.Route.extend({
  model: function() {
    return this.store.createRecord('ou');
  },
  beforeModel: function(t) {App.ensureLoginForTarget(this,t);}
});
App.LaststatesRoute = Ember.Route.extend({
  // RETURN last states via db view laststates (->table statelogs)
  model: function(params) {
    return this.store.find('laststate');
  },
  beforeModel: function(t) {App.ensureLoginForTarget(this,t);}
});
App.UserRoute = Ember.Route.extend({
  model: function(params) {
    return this.store.find('user', params.id);
  },
  beforeModel: function(t) {App.ensureLoginForTarget(this,t);}
});
App.UsersRoute = Ember.Route.extend({
  model: function() {
    return this.store.find('user');
  },
  beforeModel: function(t) {App.ensureLoginForTarget(this,t);}
});
App.UsersNewRoute = Ember.Route.extend({
  model: function() {
    return this.store.createRecord('user');
  },
  beforeModel: function(t) {App.ensureLoginForTarget(this,t);}
});
App.OptionsetRoute = Ember.Route.extend({
  model: function(params) {
    //this.set('currentOU', params.id); // hmm, unneeded? better...how?
    return this.store.find('optionset', params.id);
  },
  beforeModel: function(t) {App.ensureLoginForTarget(this,t);}
});
App.OptionsetsRoute = Ember.Route.extend({
  model: function() {
    return this.store.find('optionset');
  },
  beforeModel: function(t) {App.ensureLoginForTarget(this,t);}
});
App.OptionsetsNewRoute = Ember.Route.extend({
  model: function() {
    return this.store.createRecord('optionset');
  },
  beforeModel: function(t) {App.ensureLoginForTarget(this,t);}
});
App.NotificationsRoute = Ember.Route.extend({
  model: function() {
    return this.store.find('notification');
  },
  beforeModel: function(t) {App.ensureLoginForTarget(this,t);}
});
App.SetupRoute = Ember.Route.extend({
  setupController: function(controller,model) {
    this._super(controller,model);
      var p=this;
      $.ajax( { url: "rest-api.php/phptests", type: "GET" }).then(
        function(response) {
          var index;
          var supported = [];
          var a = response.phptests;
          var config_writable = false;
          var data_writable = false;
          var curl_supported = false;
          for (index = 0; index < a.length; ++index) {
              var e = a[index];
              (e.id=='pdo_sqlite') && (e.result==true) && supported.push('SQLite');
              (e.id=='pdo_mysql')  && (e.result==true) && supported.push('MySQL');
              (e.id=='pdo_pgsql')  && (e.result==true) && supported.push('PostgreSQL');
              (e.id=='pdo_oci')    && (e.result==true) && supported.push('Oracle');
              (e.id=='freshsetup') && (e.result==true) && controller.set('freshsetup', true);
              (e.id=='data')       && (e.result==true) && (data_writable = true);
              (e.id=='config')     && (e.result==true) && (config_writable = true);
              (e.id=='curl')       && (e.result==true) && (curl_supported = true);
          }
          controller.set('phptests', response.phptests);
          controller.set('authurl', response.authurl);
          controller.set('dbs', supported);
          controller.set('pdoSupported', supported.length>0 ? true : false);
          controller.set('preconditionsMet',
            (controller.get('pdoSupported') && controller.get('freshsetup') &&
               config_writable && data_writable && curl_supported) ? true : false);
        },
        function(response){
          humane.log('<i class="fa fa-meh-o"></i> Fatal error:'+
                     '<p>webserver seems to lack PHP support!</p>', { timeout: 0, clickToClose: true });
        }
      );
  }
});
App.SchedulesRoute = Ember.Route.extend({
  model: function() {
    return this.store.find('job');
  },
  beforeModel: function(t) {App.ensureLoginForTarget(this,t);}
});
App.SchedulesNewRoute = Ember.Route.extend({
  model: function() {
    return this.store.createRecord('job');
  },
  beforeModel: function(t) {App.ensureLoginForTarget(this,t);}
});
App.ScheduleRoute = Ember.Route.extend({
  model: function(params) {
    return this.store.find('job', params.id);
  },
  beforeModel: function(t) {App.ensureLoginForTarget(this,t);}
});
App.SystemhealthRoute = Ember.Route.extend({
  model: function() {
    return Ember.$.getJSON('rest-api.php/systemhealth').then(function(data) {
      return data;
    });
  },
  actions: {
    refreshData: function() {
      this.refresh();
    }
  },
  beforeModel: function(t) {App.ensureLoginForTarget(this,t);}
});

// Views

App.ApplicationView = Ember.View.extend({
  didInsertElement: function() {
    $('#side-menu').metisMenu(); // initialize metisMenu
    App.windowResizeHandler(); // ensure full height white body bg
  }
});
App.OuMonitorView = Ember.View.extend({
  tagName: '',
  classNames: ['row'],
  didInsertElement: function() {
    $("#livectrl").show();
    $("#hosts").show();
    $("#hosts").selectable({
      stop: function(){
        // trigger controller -- selection was modified
        var controller = App.__container__.lookup("controller:ouMonitor");
        controller.send('updateSelectedHosts');
      },
      filter: '.pc'
    });
  },
  willClearRender: function() {
    $("#hosts").selectable("destroy");
    $(".pc").removeClass("ui-selected");
  }
});
App.OuStatelogView = Ember.View.extend({
  keyDown: function(e) {
    if(e) {
      // tbd ...
      //this.get('controller').send('changeDay', { foo: bar });
    }
  }
});
App.OuHostsView = Ember.View.extend({
  // add/remove hosts view
  didInsertElement: function() {
    $("#cfghosts").selectable({
      stop: function(){
        // trigger controller -- selection was modified
        var controller = App.__container__.lookup("controller:ouHosts");
        controller.send('updateHostSelection');
      },
      filter: '.pc'
    });
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
App.NavigationView = Em.View.extend({
  templateName: 'navigation',
  selectedBinding: 'controller.selected',
  NavItemView: Ember.View.extend({
    tagName: 'li',
    classNameBindings: 'isActive:active'.w(),
    isActive: function() {
        return this.get('item') === this.get('parentView.selected');
    }.property('item', 'parentView.selected').cacheable()
  })
});
App.LoginView = Ember.View.extend({
  // that element has auto-focus, but it only works when entering route 1st time
  didInsertElement: function() {
    $('#username').focus();
  }
});

// polling helper for refreshing ember-data models ... from:
// http://yoranbrondsema.com/live-polling-system-ember-js/
App.Pollster = Ember.Object.extend({
  interval: function() {
    // tbd: make adjustable / reseatable
    // after submitting control functions, it should be increased.
    return 10000; // Time between polls (in ms)
  }.property().readOnly(),
  // Schedules the function `f` to be executed every `interval` time.
  schedule: function(f) {
    return Ember.run.later(this, function() {
      f.apply(this);
      this.set('timer', this.schedule(f));
    }, this.get('interval'));
  },
  // Stops the pollster
  stop: function() {
    Ember.run.cancel(this.get('timer'));
  },
  // Starts the pollster, i.e. executes the `onPoll` function every interval.
  start: function() {
    // ensures we don't create more traffic than desired...
    Ember.run.cancelTimers();
    this.set('timer', this.schedule(this.get('onPoll')));
  }
});

// Controllers

App.ApplicationController = Ember.Controller.extend({
  appName: 'amtc-web', // available as {{appName}} throughout app template
  needs: ["ou","ous","notifications","login"],

  // the initial value of the `search` property
  search: '',
  actions: {
    query: function() {
      // the current value of the text field
      var query = this.get('search');
      this.transitionToRoute('search', { query: query });
    },
    selectNode: function(node) {
      this.set('selectedNode', node.get('id'));
      this.transitionToRoute('ou.monitor', node.get('id') )
    },
    goLogout: function() {
      this.get('controllers.login').send('doLogout');
    }
  },
});
App.LoginController = Ember.Controller.extend({
  isLoggedIn: 0,
  authFailed: null,
  username: null,
  password: null,

  actions: {
    doLogin: function(assertion) {
      this.set('isLoggingIn', true);
      var u = this.get('username');
      var p = this.get('password');
      var self = this;

      $.ajax({
        type: 'POST',
        url: 'rest-api.php/authenticate',
        data: {username: u, password: p},

        success: function(res, status, xhr) {
          if (res.result=="success") {
            self.set('isLoggingIn', false);
            self.set('password',''); // no-longer-required
            self.set('isLoggedIn', true); // will load/unhide real menu
            self.set('authFailed', false);
            App.createCookie('isLoggedIn',1);
            //INTENSION: redir to initially requested URL upon successful auth
            var previousTransition = self.get('previousTransition');
            if (previousTransition) {
              self.set('previousTransition', null);
              previousTransition.retry();
            } else {
              // Default back to homepage
              self.transitionToRoute('index');
            }
          } else {
            self.set('authFailed', true);
            $("#password").effect( "shake" );
          }
        },
        error: function(xhr, status, err) {
          console.log("error: " + status + " error: " + err);
        }
      });
    },
    doLogout: function() {
      var self = this;
      $.ajax({
        dataType: "json",
        type: 'GET',
        url: 'rest-api.php/logout',
        success: function(xhr, status, err) {
          if (xhr.error) {
            self.set('isLoggedIn', false);
            App.eraseCookie('isLoggedIn');
            self.transitionToRoute('login');
          } else if (xhr.message && xhr.message=="success") {
            humane.log('<i class="fa fa-smile-o"></i> Signed out successfully',
               { timeout: 1000, clickToClose: false });
            self.set('isLoggedIn', false);
            App.eraseCookie('isLoggedIn');
            self.transitionToRoute('login');
          } else {
            humane.log('<i class="fa fa-meh-o"></i> Weird error!',
               { timeout: 0, clickToClose: true });
          }
        },
        error: function(xhr, status, err) {
          humane.log('<i class="fa fa-meh-o"></i> <b>Fatal error:</b><br>'+err,
               { timeout: 0, clickToClose: true });
        }
        });
    }
  },
});
App.IndexController = Ember.Controller.extend({
  needs: ["notifications","laststates"],
});
App.NotificationsController = Ember.ArrayController.extend({
  notifications: function() {
    return this.get('store').find('notification');
  }.property()
});
App.UserEditController = Ember.Controller.extend({
  needs: ["ous"],
  actions: {
    removeUser: function () {
      if (confirm("Really delete this user?")) {
        var device = this.get('model');
        device.deleteRecord();
        device.save().then(function(){
          App.successMessage('Deleted successfully','trash',this,'users');
        });
      }
    },
    doneEditingReturn: function() {
      this.get('model').save().then(function(){
        App.successMessage('Saved successfully','save',this,'users');
      });
    }
  }
});
App.UsersNewController = App.UserEditController;
// Organizational Units
App.OuController = Ember.Controller.extend({
  needs: ["optionsets","ous"],
  currentOU: null,
  isEditing: false
});
App.OuEditController = Ember.Controller.extend({
  needs: ["optionsets","ous"],
  actions: {
    removeOu: function () {
      if (confirm("Really delete this OU and associated Jobs?")) {
        var device = this.get('model');
        device.deleteRecord();
        device.save().then(function(){
          App.successMessage('Deleted successfully','trash',this,'ous');
        }, function(err) {
          device.rollbackAttributes(); // restore 'menu entry' for room deleted above
          var msg = "Unknown error occured!";
          if (typeof err.errors !== 'undefined') {
            msg = err + '<p class="errDetails">' + err.errors[0].detail + '</p>';
          }
          humane.log('<i class="fa fa-warning"></i> '+msg, { clickToClose: true });
        });
      }
    },
    doneEditingReturn: function() {
      this.get('model').save().then(function(){
        App.successMessage('Saved successfully','save',this,'ous');
      });
    }
  }
});
App.OusController = Ember.ArrayController.extend({
  ous: function() {
    return this.get('store').find('ou');
  }.property()
});
App.OusIndexController = Ember.Controller.extend({
  needs: ["ous","optionsets"]
});
App.OusNewController = App.OuEditController;
// Client PCs
App.HostsController = Ember.ArrayController.extend({
  hosts: function() {
    return this.get('store').find('host');
  }.property()
});
App.OuHostsController = Ember.Controller.extend({
  needs: ["hosts"],
  addMultiple: false,
  numHosts: 5,
  startNum: 20,
  padNum:3,
  hostname: null,
  domainName: null,
  selectedHosts: [],
  selectedHostsCount: 0,

  hostsToAdd: function() {
    if (!this.get('addMultiple')) {
      return [this.get('hostname')];
    } else {
      var hosts = [];
      if (this.get('numHosts') < 1)
       return hosts;

      var start = this.get('startNum');
      var stop  = parseInt(this.get('startNum')) + parseInt(this.get('numHosts')) - 1;
      for (var x=start; x<=stop; x++) {
        var hostname = this.get('hostname') +
                       App.pad( x, this.get('padNum')) +
                       (this.get('domainName') ? ('.' + this.get('domainName')) : '');
        hosts.push(hostname);
      }
      return hosts;
    }
  }.property('hostname','numHosts','domainName','padNum','startNum','addMultiple'),

  actions: {
    updateHostSelection: function() {
      var selection = [];
      $("#cfghosts .ui-selected").each( function(i) {
        selection.push( $(this).attr("hostDbId") );
      });
      this.set('selectedHosts', selection);
      this.set('selectedHostsCount', $(".ui-selected").length);
    },

    deleteSelectedHosts: function() {
      var selection = this.get('selectedHosts');
      var idx;
      if (confirm("Permanently DELETE selected host(s) and associated logs?")) {
        for (idx=0; idx<selection.length; idx++) {
          var host = this.store.getById('host', selection[idx]);
          host.deleteRecord();
          host.save();
        }
        $("#cfghosts .pc").removeClass("ui-selected");
        this.set('selectedHostsCount', 0);
      }
    },

    saveNewHosts: function() {
      var ouid = this.get('model.id');
      //var ou = this.store.find('ou', ouid); // async
      var ou = this.store.getById('ou', ouid); // https://github.com/emberjs/data/issues/2150
      var add = this.get('hostsToAdd');
      var idx;
      for (idx=0; idx<add.length; idx++) {
        var host = add[idx];
        var record = this.store.createRecord('host');
        record.set('hostname', host);
        record.set('ou_id', ou);
        record.save(); // .then()
        this.set('numHosts', (this.get('numHosts')-1));
      }
    }
  }
});
App.OuMonitorController = Ember.Controller.extend({
  needs: ["hosts","ous","laststates"],
  commandActions: ["powerdown","powerup","powercycle","reset","shutdown","reboot","bootpxe","boothdd"],
  shortActions: {powerdown:"D", powerup:"U", powercycle:"C", reset:"R", shutdown:"S", reboot:"B", bootpxe:"X", boothdd:"H"},

  selectedCmd: null,
  selectedHosts: [], // EMBER.MUTABLEARRAY?
  selectedHostsCount: 0,
  selectedDelay: 5,

  laststates: Ember.computed.alias("controllers.laststates"),

  /* called when group-by-powerstate-selection is done. */
  modifySelection: function(buttonid, pclass) {
    if ($("#"+buttonid).hasClass("isActive")) {
      $("#hosts ."+pclass).removeClass("ui-selected");
      $("#"+buttonid).removeClass("isActive");
      if (buttonid=="S_pc") {
        $("#hosts .pc").removeClass("ui-selected");
        $("#hselect div").removeClass("isActive");
      }
    } else {
      $("#hosts ."+pclass).addClass("ui-selected");
      $("#"+buttonid).addClass("isActive");
    }
    this.send('updateSelectedHosts');
  },

  actions: {
    selectByState: function(stateClass) {
      this.modifySelection('S_'+stateClass,stateClass);
    },
    updateSelectedHosts: function() {
      var selection = [];
      $("#hosts .ui-selected").each( function(i) {
        selection.push( $(this).attr("hostDbId") );
      });
      this.set('selectedHosts', selection);
      this.set('selectedHostsCount', $(".ui-selected").length);
    },
    submitJob: function() {
      var record = this.store.createRecord('job');
      record.set('amtc_cmd', this.get('shortActions')[this.get('selectedCmd')]);
      record.set('amtc_delay', this.get('selectedDelay'));
      record.set('hosts', this.get('selectedHosts'));
      record.set('ou_id', this.get('model'));
      record.set('description', "Interactive");
      record.set('job_type', 1 /*interactive*/);
      record.save().then(function() {
        humane.log('<i class="fa fa-save"></i> Submitted', { timeout: 1000 });
        $("#hosts .pc").removeClass("ui-selected");
        var controller = App.__container__.lookup("controller:ouMonitor");
        controller.send('updateSelectedHosts');
      });
    }
  }
});
App.OuStatelogController = Ember.Controller.extend({
  needs: ["hosts","ous","logdays"],
  selectedDay: null, // selectedDay should take last element of logdays ...
  logdata: [],
  actions: {
    selectLogday: function(args) {
      this.set('selectedDay', args.get('dayUnixStart'));
    }
  },
  dayHours: function(){
    var hours = [];
    for (var i=0; i<24; i++) {
      hours.push({hour:i, posX:i*60, textX:i*60+2});
    }
    return hours;
  }.property(),
  watchDayAndRoom: function(){
    var controller = this;
    var url = 'rest-api.php/statelogs/'+this.get('model.id')+'/'+this.get('selectedDay');
    Ember.$.getJSON(url).then(function(data) {
      controller.set('logdata', data);
    });
  }.observes('selectedDay','model.id')
});
App.LogdaysController = Ember.ArrayController.extend({
  logdays: function() {
    return this.get('store').find('logday');
  }.property(),
});
App.LaststatesController = Ember.ArrayController.extend({
  laststates: function() {
    return this.get('store').find('laststate');
  }.property(),

  stateSSH: function() {
    var laststates = this.get('laststates');
    return laststates.filterBy('open_port', 22).get('length');
  }.property('laststates.@each.open_port'),

  stateRDP: function() {
    var laststates = this.get('laststates');
    return laststates.filterBy('open_port', 3389).get('length');
  }.property('laststates.@each.open_port'),

  stateOff: function() {
    var laststates = this.get('laststates');
    return laststates.filterBy('state_amt', 5).get('length');
  }.property('laststates.@each.state_amt'),

  stateUnreachable: function() {
    var laststates = this.get('laststates');
    return laststates.filterBy('state_http', 0).get('length');
  }.property('laststates.@each.state_http'),

  stateOffList: function() {
    var list = [];
    var laststates = this.get('laststates');
    var hosts = laststates.filterBy('state_amt', 5);
    for (var i=0; i<hosts.get('length'); i++) {
      list.push(hosts[i].get('hostname'));
    }
    return list.join();
  }.property('laststates.@each.state_amt'),

  stateUnreachableList: function() {
    var list = [];
    var laststates = this.get('laststates');
    var hosts = laststates.filterBy('state_http', 0);
    for (var i=0; i<hosts.get('length'); i++) {
      list.push(hosts[i].get('hostname'));
    }
    return list.join();
  }.property('laststates.@each.state_http'),

  stateRDPList: function() {
    var list = [];
    var laststates = this.get('laststates');
    var hosts = laststates.filterBy('open_port', 3389);
    for (var i=0; i<hosts.get('length'); i++) {
      list.push(hosts[i].get('hostname'));
    }
    return list.join();
  }.property('laststates.@each.open_port'),

  stateSSHList: function() {
    var list = [];
    var laststates = this.get('laststates');
    var hosts = laststates.filterBy('open_port', 22);
    for (var i=0; i<hosts.get('length'); i++) {
      list.push(hosts[i].get('hostname'));
    }
    return list.join();
  }.property('laststates.@each.open_port'),
});
// AMT Optionsets
App.OptionsetController = Ember.Controller.extend({
  needs: ["optionsets"],
  currentOU: null,
  ouTree: null,

  actions: {
    removeOptionset: function () {
      if (confirm("Really delete this optionset?")) {
        var device = this.get('model');
        device.deleteRecord();
        device.save().then(function(){
          App.successMessage('Deleted successfully','trash',this,'optionsets');
        });
      }
    },
    doneEditingReturn: function() {
      this.get('model').save().then(function(){
        App.successMessage('Saved successfully','save',this,'optionsets');
      });
    }
  }
});
App.OptionsetsNewController = App.OptionsetController;
App.OptionsetsController = Ember.ArrayController.extend({
  optionsets: function() {
    return this.get('store').find('optionset');
  }.property()
});
// Scheduled Tasks
App.ScheduleController = Ember.Controller.extend({
  needs: ["ous"],
  currentOU: null,
  ouTree: null,
  commandActions: ["powerdown","powerup","powercycle","reset","shutdown","reboot", "bootpxe", "boothdd"],
  shortActions: {powerdown:"D", powerup:"U", powercycle:"C", reset:"R", shutdown:"S", reboot:"B", bootpxe:"X", boothdd:"H"},
  validCommands: [
    {cmd: "powerdown", cchar: "D"},
    {cmd: "powerup",   cchar: "U"},
    {cmd: "reset",     cchar: "R"},
    {cmd: "cycle",     cchar: "C"},
    {cmd: "shutdown",  cchar: "S"},
    {cmd: "reboot",    cchar: "B"},
    {cmd: "bootpxe",   cchar: "X"},
    {cmd: "boothdd",   cchar: "H"},
  ],

  actions: {
    removeSchedule: function () {
      if (confirm("Really delete this job?")) {
        var device = this.get('model');
        device.deleteRecord();
        device.save().then(function(){
          App.successMessage('Deleted successfully','trash',this,'schedules');
        });
      }
    },

    doneEditingReturn: function() {
      this.set('model.job_type', 2 /* scheduled task */ );

      // select dropdown will set newCommand when selection is altered
      if (this.get('newCommand')) {
        var newCommand = this.get('newCommand');
        this.set('model.amtc_cmd', newCommand.cchar);
      }

      this.get('model').save().then(function(){
        App.successMessage('Saved successfully','save',this,'schedules');
      });
    }
  }
});
App.SchedulesNewController = App.ScheduleController;
// System Status
App.SystemhealthController = Ember.Controller.extend({
  // action killJobs, killProcesses
  actions: {
    flushStatelog: function () {
      if (confirm('Really drop all host state log / history information?')) {
        Ember.$.getJSON('rest-api.php/flushStatelog').then(function(data) {
          humane.log('<i class="fa fa-trash"></i> Flush successful',
            { timeout: 1500, clickToClose: false });
        });
      }
    },
    resetMonitoringJob: function () {
      if (confirm('Really reset monitoring job status?')) {
        Ember.$.getJSON('rest-api.php/resetMonitoringJob').then(function(data) {
          humane.log('<i class="fa fa-trash"></i> Reset successful',
            { timeout: 1500, clickToClose: false });
        });
      }
    },
    refresh: function () {
      this.send("refreshData"); // ... on the router
    }
  }
});
// Controller for /#setup (Installer)
App.SetupController = Ember.Controller.extend({
  // Controller used for initial installation page #setup
  selectedDB: null,
  sqlitePath: 'data/amtc-web.db',
  timezone: 'Europe/Berlin',
  mysqlUser: 'amtcweb',
  mysqlHost: 'localhost',
  mysqlPassword: null,
  mysqlDB: 'amtc',
  importDemo: true,
  installHtaccess: null,
  phptests: null,
  freshsetup: false,
  datadir: 'data',
  preconditionsMet: false,
  amtcbin: '/usr/bin/amtc',

  dbs: null, // Array of supported DBs; gets set in SetupRoute
  pdoSupported: false,
  authurl: null,

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

  actions: {
    doneEditing: function() {
      var d = {
        datadir: this.get('datadir'),
        timezone: this.get('timezone'),
        selectedDB: this.get('selectedDB'),
        sqlitePath: this.get('sqlitePath'),
        mysqlUser: this.get('mysqlUser'),
        mysqlHost: this.get('mysqlHost'),
        mysqlPassword: this.get('mysqlPassword'),
        mysqlDB: this.get('mysqlDB'),
        amtcbin: this.get('amtcbin'),
        authurl: this.get('authurl'),
        importDemo: this.get('importDemo'),
        installHtaccess: this.get('installHtaccess'),
      }
      $.ajax({type:"POST", url:"rest-api.php/submit-configuration",
              data:jQuery.param(d), dataType:"json"}).then(function(response) {
        if (typeof response.errorMsg != "undefined")
          humane.log('<i class="fa fa-meh-o"></i> Save failed: <br>'+response.errorMsg, { timeout: 0, clickToClose: true, addnCls: 'humane-error'});
        else {
          humane.log('<i class="fa fa-save"></i> Saved successfully! Warping into amtc-web!', { timeout: 1500 });
          window.setTimeout( function(){
            window.location.href = 'index.html';
          }, 2000);
        }
      }, function(response){
        if (response.responseText=='INSTALLTOOL_LOCKED') {
          humane.log('<i class="fa fa-meh-o"></i> Setup is LOCKED!<br>'+
            'Setup is intended for initial installation only.<br>'+
            'Remove <code>config/siteconfig.php</code> to re-enable setup.',
            { timeout: 0, clickToClose: true, addnCls: 'humane-error' });
        } else {
          humane.log('<i class="fa fa-meh-o"></i> Failed to save! Please check console.'+response.responseText,
            { timeout: 0, clickToClose: true, addnCls: 'humane-error' });
        }
      });
    }
  }
});

// Models

// Organizational Unit
App.Ou = DS.Model.extend({
  name: attr('string'),
  description: attr('string'),
  parent_id: DS.belongsTo('ou', {inverse: 'children'}),
  optionset_id: DS.belongsTo('optionset'),
  idle_power: attr('number'),
  logging: attr('boolean'),
  children: DS.hasMany('ou', {inverse: 'parent_id'}),
  hosts: DS.hasMany('host'),//, {inverse: null}),

  // return ou/room path-style string of 'parent directories'
  ou_path: function() {
    var height = 0;
    var pathParts = [];
    var p = this.get('parent_id');
    while (p && height++<10) {
      pathParts.unshift(p.get('name'));
      p = p.get('parent_id');
    }
    return pathParts.join(' / ');
  }.property('parent_id').cacheable(),

  // new ou-tree; 1:1 from https://github.com/joachimhs/Montric/blob/master/Montric.View/src/main/webapp/js/app/models/MainMenuModel.js
  isSelected: false,
  isExpanded: true, // make this user/cookie/whatever optional
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
    if (this.get('isExpanded')) {
      var children = this.get('children.content');
      if (children) {
        //children.sort(App.Ou.compareNodes);
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
  ou_id: DS.belongsTo('ou', {async:false}),//, {inverse: null}),
  hostname: attr('string'),
  enabled: attr('boolean'),
  laststate: DS.belongsTo('laststate'),
  // add isSelected et al
});
// Markdown help / documentation pages
App.Page = DS.Model.extend({
  file_name: attr('string'),
  page_title: attr('string'),
  page_content: attr('string'),
});
// Notification center messages
App.Notification = DS.Model.extend({
  ntype: attr('string'),
  tstamp: attr('string'),
  user_id: DS.belongsTo('user'),
  message: attr('string'),
  cssClass: function(key,value) {
    if (!value) {
      var cc = "fa fa-"+this.get('ntype')+" fa-fw";
      return cc;
    }
  }.property('ntype')
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
  opt_timeout: attr('string'),
  opt_passfile: attr('string'),
  opt_cacertfile: attr('string')
});
// Users
App.User = DS.Model.extend({
  ou_id: DS.belongsTo('ou'),
  name: attr('string'),
  fullname: attr('string'),
  is_enabled: attr('boolean'),
  is_admin: attr('boolean'),
  can_control: attr('boolean')
});
// Last power states
App.Laststate = DS.Model.extend({
  host_id: DS.belongsTo('host'),
  state_begin: attr('number'),
  open_port: attr('number'),
  state_amt: attr('number'),
  state_http: attr('number'),
  hostname: attr('string'),

  lastScan: function() {
    return moment.unix(this.get('state_begin')).fromNow();
  }.property('state_begin'),
  openPortIcon: function() {
    var cc='fa fa-ban fa-fw';
    this.get('state_http')== 200  && this.get('state_amt')==0 && (cc='fa fa-ban fa-fw red'); // AMT ok, powered up, no OS
    this.get('state_http')== 200  && this.get('state_amt')==5 && (cc='fa fa-power-off fa-fw'); // AMT ok, powered down
    this.get('open_port') == 22   && (cc = "fa fa-linux fa-fw");
    this.get('open_port') == 3389 && (cc = "fa fa-windows fa-fw");
    this.get('state_amt') == 3 && (cc = "fa fa-bed fa-fw"); // sleeping
    this.get('state_amt') == 4 && (cc = "fa fa-rocket fa-fw"); // hibernating
    return new Ember.Handlebars.SafeString('<i class="'+cc+'"></i> ');
  }.property('open_port','state_http','state_amt'),
  openPortCssClass: function() {
    var result = ''; // unreachable
    switch(this.get('open_port')) {
    case 22:
        result = 'ssh';
        break;
    case 3389:
        result = 'rdp';
        break;
    default:
        this.get('state_http')==200 && this.get('state_amt')==0 && (result = 'none'); // AMT reachable, ON, but no OS
    }
    return result;
  }.property('open_port','state_http','state_amt'),
  amtStateCssClass: function() {
    return 'S' + this.get('state_amt');
  }.property('state_amt'),
});
// Days with logdata available
App.Logday = DS.Model.extend({
  // id = date
  dayString: function() {
    return moment(this.get('id')).format("YYYY MMMM Do (dddd)");
  }.property('id'),
  dayUnixStart: function() {
    var tstr = this.get('id')+' 00:00:00 +0200';
    return moment(tstr,"YYYY-MM-DD HH:mm:ss Z").unix();
  }.property('id'),
});
// Jobs / scheduled tasks
App.Job = DS.Model.extend({
  ou_id: DS.belongsTo('ou', {async:false}),//, {inverse: null}),
  job_type: attr('number'), // 1=interactive, 2=scheduled, 3=monitor
  description: attr('string'),
  hosts: attr(),
  start_time: attr('number'),
  amtc_cmd: attr('string'),
  amtc_delay: attr('number'),
  repeat_interval: attr('number'),
  repeat_days: attr('number'),
  last_started: attr('number'),
  last_done: attr('number'),

  // start_time is a int value representing minute-of-day.
  // allow getting / setting it in a human readable form.
  human_start_time: function(k,v) {
    if (arguments.length > 1) {
      var parts =  v.split(':');
      var h = parseInt(parts[0]);
      var m = parseInt(parts[1]);
      var hm = h*60 + m;
      this.set('start_time', hm);
    }
    var hrs = App.pad( Math.floor(this.get('start_time')/60),  2);
    var min = App.pad( this.get('start_time') - ( hrs * 60),  2);
    return hrs + ':' + min;
  }.property('start_time'),

  // repeat_days is a bitmask, starting with sunday=1.
  // setters on computable properties below provide easy access...
  on_sunday: function(k,v) {
    if (arguments.length > 1)
      this.set('repeat_days', this.get('repeat_days') ^ 1);
    return this.get('repeat_days') & 1 ? true : false;
  }.property('repeat_days'),
  on_monday: function(k,v) {
    if (arguments.length > 1)
      this.set('repeat_days', this.get('repeat_days') ^ 2);
    return this.get('repeat_days') & 2 ? true : false;
  }.property('repeat_days'),
  on_tuesday: function(k,v) {
    if (arguments.length > 1)
      this.set('repeat_days', this.get('repeat_days') ^ 4);
    return this.get('repeat_days') & 4 ? true : false;
  }.property('repeat_days'),
  on_wednesday: function(k,v) {
    if (arguments.length > 1)
      this.set('repeat_days', this.get('repeat_days') ^ 8);
    return this.get('repeat_days') & 8 ? true : false;
  }.property('repeat_days'),
  on_thursday: function(k,v) {
    if (arguments.length > 1)
      this.set('repeat_days', this.get('repeat_days') ^ 16);
    return this.get('repeat_days') & 16 ? true : false;
  }.property('repeat_days'),
  on_friday: function(k,v) {
    if (arguments.length > 1)
      this.set('repeat_days', this.get('repeat_days') ^ 32);
    return this.get('repeat_days') & 32 ? true : false;
  }.property('repeat_days'),
  on_saturday: function(k,v) {
    if (arguments.length > 1)
      this.set('repeat_days', this.get('repeat_days') ^ 64);
    return this.get('repeat_days') & 64 ? true : false;
  }.property('repeat_days'),

  isInteractiveTask: function() { return this.get('job_type')==1; }.property('job_type'),
  isScheduledTask:   function() { return this.get('job_type')==2; }.property('job_type'),
  isMonitoringTask:  function() { return this.get('job_type')==3; }.property('job_type')
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
      this.sendAction('action', node);
    }
  },
  isSelected: function() {
    return this.get('selectedNode') === this.get('node.id');
  }.property('selectedNode', 'node.id')
});
App.StateLogComponent = Ember.Component.extend({
  logItems: function() {
    var host = this.get('controller.host');
    var logs = this.get('controller.logdata');
    var hostid = host.get('id');
    var output = [];
    var day0h = this.get('controller.selectedDay');
    var now = moment().unix();
    var today = moment(moment().format("YYYY-MM-DDT00:00:00.000Z")).unix();
    // SVG ... tbd
    // SVG width: 1440px = 60minutes*24
    // http://madhatted.com/2014/11/24/scalable-vector-ember
    logs.forEach(function(log) {
      if (hostid==log.host_id && day0h != null) {
        var dayMinute = (log.state_begin - day0h)/60;
        log.hostname = host.get('hostname');
        log.posX = dayMinute > 0 ? dayMinute : 0;
        log.sizeX = 1440-dayMinute;
        log.timeBegin = moment.unix(log.state_begin).format("MMM DD HH:mm:ss");
        // unsuck ...: use css classes as monitor
        if (log.open_port==22 && log.state_http==200) {
          log.fillColor = '#63aae7;'; // linux
        } else if (log.open_port==3389 && log.state_http==200) {
          log.fillColor = '#5cb85c'; // windows
        } else if (log.state_amt==5 && log.state_http==200) {
          log.fillColor = '#aaa'; // off + AMT reachable
        } else if (log.state_amt==16 || log.state_http!=200) {
          log.fillColor = '#e9635f'; // AMT unreachable
        } else if (log.state_amt==3) {
          log.fillColor = 'orange'; // sleep
        } else if (log.state_amt==4) {
          log.fillColor = '#aae'; // hibernate
        } else if (log.state_amt==0 && log.state_http==200 && log.open_port==0) {
          log.fillColor = 'yellow'; // reachable, but no OS detected/running
        } else {
          log.fillColor = 'black'; // what? f_ix-me
        }
        output.push(log);
      }
    });
    // if looking at today's log, indicate current time
    if (day0h == today) {
      var nowMinute = Math.round((now-today)/60);
      var nowentry = {posX: nowMinute, sizeX: 1440-nowMinute, fillColor:'white;fill-opacity:0.5'};
      output.push(nowentry)
    }
    return output;
  }.property('controller.logdata')
});
App.MySelectComponent = Ember.Component.extend({
  // http://emberjs.com/deprecations/v1.x/#toc_ember-select
  // possible passed-in values with their defaults:
  content: [],
  prompt: null,
  optionValuePath: 'id',
  optionLabelPath: null,//'title',
  comparisonAttr: null,

  action: Ember.K, // action to fire on change

  // shadow the passed-in `selection` to avoid
  // leaking changes to it via a 2-way binding
  _selection: Ember.computed.reads('selection'),

  actions: {
    // change() { ... } syntax breaks nodejs build. fix
    change: function() {
      const selectEl = this.$('select')[0];
      const selectedIndex = selectEl.selectedIndex;
      const content = this.get('content');

      // decrement index by 1 if we have a prompt
      const hasPrompt = !!this.get('prompt');
      const contentIndex = hasPrompt ? selectedIndex - 1 : selectedIndex;

      var ary = content.toArray();
      const selection = ary[contentIndex];

      // set the local, shadowed selection to avoid leaking
      // changes to `selection` out via 2-way binding
      this.set('_selection', selection);

      const changeCallback = this.get('action');
      changeCallback(selection);
    }
  }
});
App.IsEqualHelper = Ember.Helper.helper(function(params) {
  // http://emberjs.com/deprecations/v1.x/#toc_ember-select
  // ^ uses ES6/Babel function([params]) that will transpile
  // into better params validation as done here
  var leftSide = params[0];
  var rightSide = params[1];

  // kludge to allow <select> comparison for non-ember-data records.
  // used by scheduleEdit.hbs -- improve/fix...
  var comparisonAttr = params[2]; // null by component's default
  if (comparisonAttr) {
    return leftSide[comparisonAttr] === rightSide;
  }

  return leftSide === rightSide;
});
App.IsNotHelper = Ember.Helper.helper(function(value) {
  return !value[0];
});
App.ReadPathHelper = Ember.Helper.helper(function(params){
  var object = params[0];
  var path = params[1];
  return Ember.get(object, path);
});

// https://gist.github.com/pwfisher/b4d27d984ad5868baab6
// {{ radio-button name='dish' value='spam' groupValue=selectedDish }} Spam
// {{ radio-button name='dish' value='eggs' groupValue=selectedDish }} Eggs
//
App.RadioButtonComponent = Ember.Component.extend({
  tagName: 'input',
  type: 'radio',
  attributeBindings: [ 'checked', 'name', 'type', 'value' ],

  checked: function () {
    return this.get('value') === this.get('groupValue');
  }.property('value', 'groupValue'),

  change: function () {
    this.set('groupValue', this.get('value'));
  }
});

// Handlebars helpers

// markdown to html conversion
Ember.Handlebars.helper('format-markdown', function(input) {
  var showdown = new window.showdown.Converter();
  if (input) {
    var md = showdown.makeHtml(input);
    md = md.replace("<h1 id=",'<h1 class="page-header" id=');
    var html = new Ember.Handlebars.SafeString(md);
    return html;
  } else {
    console.log("Warning: empty input on showdown call.");
    return input;
  }
});

// print fontAwesome checkmarks for input true/false
Ember.Handlebars.helper('check-mark', function(input) {
  return input ?
    new Ember.Handlebars.SafeString('<i class="fa grey fa-fw fa-check-square-o"></i> ') :
    new Ember.Handlebars.SafeString('<i class="fa grey fa-fw fa-square-o"></i> ');
});

// moment.js PRETTY timestamps
Ember.Handlebars.helper('format-from-now', function(date) {
  if (!date) {
    return 'n/a';
  }
  return moment.unix(date).fromNow();
});
