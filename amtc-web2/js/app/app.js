
var App = Ember.Application.create({});

App.Router.map(function() {
  this.resource('about');
  this.resource('schedule');
  this.resource('setup');
  this.resource('ous', function() {
    this.resource('ou', { path: ':id' });
  });
  this.resource('devices', function() {
    this.resource('device', { path: ':id' });
    this.route('new');
  });
  this.resource('pages', function() {
    this.resource('page', { path: ':id' });
  });
});

App.IndexView = Ember.View.extend({
    templateName: 'index',
    didInsertElement: function() {
    	// broken, should be done by ember:
    	$('#side-menu').metisMenu();
    	// just for demo...

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


App.IndexRoute = Ember.Route.extend({
  enter: function() {
  	console.log("Entered App.IndexRoute");

	$(window).bind("load resize", function() {
	topOffset = 50;
	width = (this.window.innerWidth > 0) ? this.window.innerWidth : this.screen.width;
	if (width < 768) {
	    $('div.navbar-collapse').addClass('collapse')
	    topOffset = 100; // 2-row-menu
	} else {
	    $('div.navbar-collapse').removeClass('collapse')
	}

	height = (this.window.innerHeight > 0) ? this.window.innerHeight : this.screen.height;
	height = height - topOffset;
	if (height < 1) height = 1;
	if (height > topOffset) {
	    $("#page-wrapper").css("min-height", (height) + "px");
	}
	})
	}
 });
