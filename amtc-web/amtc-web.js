/* 
 * amtc-web.js - part of amtc-web, part of amtc
 * https://github.com/schnoddelbotz/amtc
 *
 * this jQuery spaghetti renders the web GUI of amtc-web.
 */
/*
 * FIXMEs
 * - configTabs do not update hash onClick
 */

var visitorScript = 'amtc-web.php';
var adminScript = 'admin/admin.php';

var infoURL = visitorScript+"?action=getState&mode=live&roomname=";
var logURL  = visitorScript+"?action=getState&mode=log&roomname=";
var roomURL = visitorScript+"?action=getRooms";
/* these admin only pages will trigger http basic auth popups 
   ... as long as an adequate .htaccess file exists in /admin */
var ctrlURL = adminScript+"?action=submitJob";
var cfgURL = adminScript+"?action=config&mode=";


var msg_PleaseSelectRoom = 'Please select a room.';
var msg_LoadingLive  = 'Loading live state of room, please wait...';

var currentRoom;
var hostList;
var hostLog;
var showingHosts=0;
var today=new Date();
var selectedDate=new Date();
var showLive=1; /* alternative: showLog, FIXME showConfig */
var zoom=1;
var configTabs;
var amtc_rooms;
var currentViewMode;
var powerstates = {"pc":"any", "S0":"on", "S3":"sleep", "S4":"hibernate", "S5":"soft-off",
                   "S16":"no-reply", "ssh":"SSH","rdp":"RDP","none":"No-OS"};


$(document).ready(function() { 
  $("#wheel").hide();
  $("#ctrlSubmit").removeAttr('disabled');
  loadRooms();
  initViewModeButtons();
  initConfigScreens();
  initPowerController();
});


/* called when a room is selected in admin mode.
 * loads roomdata and prepares forms */
function cfgRoom(room/*name*/) {
  $(".selected-room").removeClass("selected-room");
  $.getJSON(cfgURL+'rooms&roomname='+room, function(data) {
      currentRoom = room;
      window.location.hash = '#config;rooms;'+room;
      $("#cfghosts").html("");
      $("#cfgr_"+room).addClass("selected-room");
      if (data.success) {
        if (data.data.room) {
          hostList=data.data.room.hosts;
          drawConfigHosts();
        } 
        $("#existingRoom").show(); 
        $("#roomName").val(room);
        $("#amtVersion").val(data.data.roomMeta.has_amt);
        $("#idlePower").val(data.data.roomMeta.avg_pwr_on);
        $("#roomid").val(data.data.roomMeta.id);
        $("#createPc_submit").val('Create new PC in room '+room);
        $("#modRoom_msg").html("");
      } else {
        $("#cfghosts").html('<h2 class="warning">'+data.usermsg+'</h2>');
      }
  }).error(function(e) {
        $("#cfghosts").html('<h2 class="warning">ERROR: '+e.statusText+'</h2>');
  });
}

/* called upon page load and after admin modifications.
 * renders list of amt-enabled (non-version-0) rooms as chooser */
function loadRooms() {
  $("#hosts").html("Loading room data...");
  $("#rooms").html(""); 
  $.getJSON(roomURL, function(data) {
      if (data.success) {
        $("#hosts").html(msg_PleaseSelectRoom);
        amtc_rooms = data.data;
        $.each( amtc_rooms, function( key, value ) {
          var room = '<div id="'+key+'" class="room ui-corner-top">' +  key + '</div>';
          var cfgroom = '<div id="cfgr_'+key+'" roomname="'+key+'" class="room ui-corner-top">' +  key + '</div>';
          $("#rooms").append(room);
          $("#"+key).click(function() { setRoom($(this).attr("id"),0); });
        });
        $(".roomSelector").css( 'cursor', 'pointer' );
     } else {
      if (!window.location.hash)
          $("#admin").trigger('click');
     }
     goHashUrl();
  }).error(function(e) {
      if (e.responseText.substring(0,5)==='<?php') {
        $("#hosts").html('<h2 class="warning">ERROR: Seems your webserver has no PHP support enabled?</h2>');
      } else 
        $("#admin").trigger('click');
        // fixme: where to leave the message?
        //$("#hosts").html('<h2 class="warning">ERROR: '+e.statusText+'</h2>');
  });;
}

/* called upon page admin tab switch and after modifications to rooms.
 * renders list of any user-created rooms as chooser */
function loadCfgRooms() {
  $("#existingRoom").hide(); 
  $("#roomcfg_rooms").html(""); 
  $.getJSON(cfgURL+'rooms', function(data) {
      if (data.success) {
        amtc_rooms = data.data.rooms;
        $.each( amtc_rooms, function( key, value ) {
          var cfgroom = '<div id="cfgr_'+key+'" roomname="'+key+'" class="room ui-corner-top">' +  key + '</div>';
          $("#roomcfg_rooms").append(cfgroom);
          $("#cfgr_"+key).click(function() { cfgRoom($(this).attr("roomname"),0); });
        });
        $(".roomSelector").css( 'cursor', 'pointer' );
     } else {
          $("#admin").trigger('click');
     }
  });
}

/* called upon page load.
 * tries to get back to the right(tm) state */
function goHashUrl() {
  if (window.location.hash) {
    var hash = window.location.hash.substr(1);
    var args = hash.split(';');
    if (args[0]=='config') {
      /* admin config mode */
      $("#admin").trigger('click');
      currentViewMode = 'config';
      if (args[1]) {
        var modes = {'overview':0, 'site':1, 'db':2, 'rooms':3, 'scheduler':4};
        configTabs.tabs('option', 'active', modes[args[1]]);
        if (args[1]=='rooms' && args[2])
          return cfgRoom(args[2]);
      }
    } else {
      /* user live/log mode */
      $("#hosts").html(msg_LoadingLive);
      if (args[0]=='log') {
        showLive = 0;
        currentViewMode = 'log';
      } else {
        currentViewMode = 'live';
        showLive = 1;
      }
      if (args[2]) {
        var e = args[2].split('-');
        var d = new Date(e[0], e[1]-1, e[2], 12, 0, 0, 0);
        setRoom(args[1],d);
      } else 
        setRoom(args[1],0);
    }
  }
}

/* called when group-by-powerstate-selection is done.
 * just modifies the DOM */
function modifySelection(buttonid, pclass) {
  if ($("#"+buttonid).hasClass("isActive")) {
    $("#hosts ."+pclass).removeClass("ui-selected");
    $("#"+buttonid).removeClass("isActive");
    if (buttonid=="any") {
      $("#hosts .pc").removeClass("ui-selected");
      $("#hselect span").removeClass("isActive");
    }
  } else {
    $("#hosts ."+pclass).addClass("ui-selected");
    $("#"+buttonid).addClass("isActive");
  }
  updatePowerController();
}

function drawLiveHosts() {
  $("#hselect span").removeClass("isActive");
  if (showingHosts)
    $("#hosts").selectable("destroy");

  $("#hosts").html("");
  $.each( hostList, function( key, value ) {
    var ps = value.amt;
    var host = '<div host="'+key+'" title="'+value.oport+'" class="pc livepc S' + ps + " "+value.oport+
               ' http'+value.http+' ui-corner-all"><p class="addr">' + key +
               "</p><span>" + value.msg + '</span></div>';
    $("#hosts").append(host);
  });
  $("#hosts").append('<p style="clear:both;"></p>');
  $("#hosts").selectable({
    stop: updatePowerController,
    filter: '.pc'
  });
  showingHosts=1;
}

function drawConfigHosts() {
  $("#cfghosts").html("");
  $.each( hostList, function( hostid, hostname ) {
    var del_link = '<a href="javascript:deletePc('+hostid+');">delete</a>';
    var host = '<div id="pc_'+hostid+'" class="pc cfgpc S0 ui-corner-all"><p class="addr">' + hostname +
               "</p><span>" + del_link + '</span></div>';
    $("#cfghosts").append(host);
  });
  $("#cfghosts").append('<p style="clear:both;"></p>');
}

function drawLogHosts() {
  $("#hselect span").removeClass("isActive");
  if (showingHosts)
    $("#hosts").selectable("destroy");
  showingHosts=0;

  $("#hosts").html("");
  var d = new Date();
  var now = d.getHours() * 60 + d.getMinutes();
  if (selectedDate<today) now = 1440;
  var curhost;
  var lastip;
  var curhoststates;

  // cleanup, nav buttons prev next day
  var prevDay = new Date(selectedDate.getTime());
  prevDay.setDate(prevDay.getDate()-1);
  var prevDayString = prevDay.getFullYear()+'-'+pad(prevDay.getMonth()+1,2)+'-'+pad(prevDay.getDate(),2);
  $("#pd").unbind().click(function() {
      setRoom(currentRoom,prevDay);
  });
  $("#pd").css( 'cursor', 'pointer' );

  var selectedDayString = selectedDate.getFullYear()+'-'+pad(selectedDate.getMonth()+1,2)+'-'+pad(selectedDate.getDate(),2);

  var nextDay = new Date(selectedDate.getTime());
  nextDay.setDate(nextDay.getDate()+1);
  var nextDayString = nextDay.getFullYear()+'-'+pad(nextDay.getMonth()+1,2)+'-'+pad(nextDay.getDate(),2);
  $("#nd").unbind().click(function() {
      setRoom(currentRoom,nextDay);
  });
  $("#nd").css( 'cursor', 'pointer' );
  // eof nav buttons


  var bars = '';
  var hostdiv = '';
  $.each( hostLog, function( key, value ) {
    var ip = value.ip;
    if (lastip && ip!=lastip) {
      hostdiv = '<div class="pclog" title="'+lastip+'">'+bars+'</div>';
      $("#hosts").append(hostdiv);
      hostdiv = '';
      bars = '';
    }
    var barleft = (value.state_begin / zoom) + 20 ;
    var barwidth = (now - value.state_begin) / zoom;
    bars += '<div class="bar p'+value.open_port+' a'+value.state_amt+'" style="left:'+barleft+'px; width:'+barwidth+'px;"> </div>';
    lastip=ip;
  });
}

function updatePowerController() {
        /* only show action panel as long as we have hosts selected */
        ($(".ui-selected").length>0)  ?  $("#ctrl").show() : $("#ctrl").hide();
        $("#numselected").html( $(".ui-selected").length );
}

// only for log and live mode (cfgRoom() otherwise)
function setRoom(room,showdate) {
  if (!room)
    return;
  if ($("#hosts").html()==msg_PleaseSelectRoom)
    $("#hosts").html(msg_LoadingLive);
  $("#ctrlSubmit").removeAttr('disabled');
  $("#rooms .selected-room").removeClass("selected-room");
  $("#"+room).addClass("selected-room");
  $("#ctrl").hide();
  if (!(room in amtc_rooms)) {
    $("#hosts").html('<h2 class="warning">Please select a room<h2>');
    return;
  }
  $("#wheel").show();
  $("#vm div.viewmodebutton").removeClass("selected-viewmode");
  if (showLive) {
    $("#hosts").removeClass("logged");
    $("#live").addClass("selected-viewmode");
    $.getJSON(infoURL+room, function(data) {
      $("#wheel").hide();
      currentRoom = room;
      window.location.hash = '#live;'+room;
      if (data.success) {
        hostList=data.data;
        drawLiveHosts(); 
        $("#livectrl").show();
      } else {
        $("#hosts").html('<h2 class="warning">'+data.usermsg+'</h2>');
      }
    });
  } else {
    var sdate='';
    $("#log").addClass("selected-viewmode");
    if (showdate) {
      var sd = showdate.getFullYear()+'-'+pad(showdate.getMonth()+1,2)+'-'+pad(showdate.getDate(),2);
      sdate = '&date='+sd;
    } else {
      showdate = selectedDate;
    }

    $.getJSON(logURL+room+sdate, function(data) {
      hostLog=data;
      selectedDate = showdate;
      var sd = showdate.getFullYear()+'-'+pad(showdate.getMonth()+1,2)+'-'+pad(showdate.getDate(),2);
      drawLogHosts(); 
      $("#wheel").hide();
      $("#hosts").addClass("logged");
      window.location.hash = '#log;'+room+';'+sd;
      currentRoom = room;
      $("#livectrl").hide();
    });
  }
}

function initViewModeButtons() {
  // create buttons for live and log view mode (upper right)
  $("#live").css( 'cursor', 'pointer' );
  $("#live").click(function() {
    if (currentViewMode!='live') window.location.hash = '#live';
    $("#vm div.viewmodebutton").removeClass("selected-viewmode");
    $("#live").addClass("selected-viewmode");
    $("#config").hide();
    $("#hosts").show();
    $("#rooms").show();
    showLive = 1;
    currentViewMode = 'live';
    setRoom(currentRoom,0);
  });
  //
  $("#log").css( 'cursor', 'pointer' );
  $("#log").click(function() {
    if (currentViewMode!='log') window.location.hash = '#log';
    $("#vm div.viewmodebutton").removeClass("selected-viewmode");
    $("#log").addClass("selected-viewmode");
    $("#config").hide();
    $("#hosts").show();
    $("#rooms").show(); 
    showLive = 0;
    currentViewMode = 'log';
    setRoom(currentRoom,new Date());
  });
  //
  $("#admin").css( 'cursor', 'pointer' );
  $("#admin").click(function() {
    if (currentViewMode!='config') window.location.hash = '#config';
    $("#vm div.viewmodebutton").removeClass("selected-viewmode");
    $("#admin").addClass("selected-viewmode");
    $("#livectrl").hide();
    $("#hosts").hide();
    $("#rooms").hide();
    currentViewMode = 'config';
    $("#config").show(); // 'static' config screens from index.html
  });
}

function initConfigScreens() {
  configTabs = $( "#config" ).tabs({
    beforeLoad: function( event, ui ) {
      ui.jqXHR.error(function() {
        ui.panel.html("Fatal error or insufficient permissions (bad password?).");
      });
    },
    activate: function( event, ui ) { 
      var modes  = ['overview', 'site', 'db', 'rooms', 'scheduler'];
      var tabid  = ui.newTab.context.attributes.id.value; 
      var modeid = tabid.slice(-1)-1;
      window.location.hash = '#config;'+modes[modeid];
      if (modeid==3)
        loadCfgRooms();
    }
  });
  // fixme...ff.: repetetive
  $('#editRoom_submit').click(function() {
    $.post(cfgURL+"rooms&do=submit&roomname="+currentRoom, { 
          'editRoomname': $("#roomName").val(),
          'amtVersion'  : $("#amtVersion").val(),
          'idle_power'  : $("#idlePower").val() 
    }, function(data) {
      if (data.success==true) {
        msg = 'Data submitted <span class="success">successfully</span>: '+data.usermsg;
        loadCfgRooms();
        window.location.hash = '#config;rooms;'+$("#roomName").val();
      } else
        msg = 'Data submission <span class="warning">FAILED</span>: '+data.usermsg;

      $("#modRoom_msg").html(msg);
    }).error(function(e) { 
      $("#modRoom_msg").html('Data submission <span class="warning">FAILED</span>: '+e.statusText);
    }); 
  });
  $('#addRoom_submit').click(function() {
    $.post(cfgURL+"rooms&do=submit", { 
          'newRoomname': $("#roomName").val(),
          'amtVersion' : $("#amtVersion").val(),
          'idle_power' : $("#idlePower").val() 
    }, function(data) {
      console.log(data);
      if (data.success==true) {
        msg = 'Data submitted <span class="success">successfully</span>: '+data.usermsg;
        loadCfgRooms();
        //$("#cfghost
      } else
        msg = 'Data submission <span class="warning">FAILED</span>: '+data.usermsg;

      $("#modRoom_msg").html(msg);
    }).error(function(e) { 
      $("#modRoom_msg").html('Data submission <span class="warning">FAILED</span>: '+e.statusText);
    }); 
  });
  $('#createPc_submit').click(function() {
    $.post(cfgURL+"rooms&do=submit", { 
          'roomname': currentRoom,
          'createPc' : $("#createPc").val() 
    }, function(data) {
      console.log(data);
      if (data.success==true) {
        msg = 'Host created <span class="success">successfully</span>: '+data.usermsg;
        //loadCfgRooms();
        cfgRoom(currentRoom);
      } else
        msg = 'Host creation <span class="warning">FAILED</span>: '+data.usermsg;
      $("#modRoom_msg").html(msg);
    }).error(function(e) { 
      $("#modRoom_msg").html('Data submission <span class="warning">FAILED</span>: '+e.statusText);
    }); 
  });
  $('#deleteRoom_submit').click(function() {
    deleteRoom(currentRoom);
  });
}

function initPowerController() {
  $('#ctrlSubmit').click(function() {
    if (!$("form input[name='cmd']:checked").val())
      return alert("Please select command (powerup, powerdown...) first!");
    $('#ctrlSubmit').attr('disabled', 'disabled');
    $("#wheel").show();
    var cmdhosts = [];
    $("#hosts .ui-selected").each( function(i) { cmdhosts.push( $(this).attr("host") ); } );
    $("#hosts").html("<strong>Submitting data, please wait ...</strong>");
    $.post(ctrlURL, {
          'hosts':cmdhosts.join(" "),
          'cmd':$("form input[name='cmd']:checked").val(),
          'delay':$("#delay").val(),
          'roomname':currentRoom
      },
      function(data) {
        $("#hosts").html("<strong>Job submitted. Re-select room to refresh view.<br>" +
                         "amtc command line equivalent:</strong><br>");
        $("#hosts").append("amtc -"+data.cmd+" "+data.hosts);
        $("#wheel").hide();
        $("#ctrlSubmit").removeAttr('disabled');
        $("#ctrl").hide();
        updatePowerController();
      }
    ).error(function() { 
      $("#wheel").hide();
      $("#hosts").html("<strong>Submission failed. Choose room to continue...</strong>");
    });
  });
  
  $("#hselect").html("");
  $.each(powerstates, function(key, value) {
    $("#hselect").append('<span id="'+value+'" class="'+key+' ui-corner-all">'+value+'</span>');
    $("#"+value).click(function() { modifySelection(value,$(this).attr("class").split(' ')[0]); });
  });
  $("#hselect span").css( 'cursor', 'pointer' );
}

function pad(number, length) {
 return (number+"").length >= length ?  number + "" : pad("0" + number, length);
}

function deletePc(id) {
  var enableConfirmations = $("#enableConfirmations").is(':checked');
  if (!enableConfirmations || (enableConfirmations && confirm("Really delete PC "+id+" ?"))) {
    $.getJSON(cfgURL+'rooms&do=submit&deletePc='+id, function(data) {
      if (data.success) {
        $("#pc_"+id).removeClass("S0").addClass("S5");
        $("#pc_"+id+" span").html("successfully deleted");
        window.setTimeout(function(){$("#pc_"+id).remove();}, 2500);
      } else 
        alert("Failed to remove PC: "+data.usermsg);
    }).error(function(e) {
        alert("Failed to remove PC: "+e.statusText);
    });
  }
}

function deleteRoom(r/*oomname*/) {
  var enableConfirmations = $("#enableConfirmations").is(':checked');
  if (!enableConfirmations || (enableConfirmations && confirm("Really delete room "+r+" ?"))) {
    $.getJSON(cfgURL+'rooms&do=submit&deleteRoom='+r, function(data) {
      if (data.success) { 
        $("#cfgr_"+r).remove();
        $("#cfghosts").html("");
        $("dd input").val("");
        $("#existingRoom").hide();
      } else
        alert("Failed to remove room: "+data.usermsg);
    }).error(function() {
        alert("Failed to remove room: "+e.statusText);
    });

  }
}
