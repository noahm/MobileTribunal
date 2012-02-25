/* Copyright (c) 2012 kayson (kaysond) & Noah Manneschmidt (psoplayer)
 * https://github.com/noahm/MobileTribunal
 *
 * Licensed under the MIT License.
 * https://raw.github.com/noahm/MobileTribunal/master/mit-license.txt
 */

$(function() {
	// init the login form
	$('#login form').submit(submitLogin);
	$('#realm').val($.store.get('realm'));
	$('#username').val($.store.get('username'));
	$.store.clear('password'); // just in case they used the original version
	
	// logout handler
	$('#logout').click(doLogout);
	
	// handle opening and closing the menu
	$('#game-selected').click(function() {
		$('#games').toggle();
	});
	// handle switching games
	$('#games li').live('click', function() {
		$('#games').hide();
		loadGame(this.value);
	});
	// handle showing the verdict options
	$('#verdict').click(function() {showOnly('submit');});
	$('#return').click(function() {showOnly('game');});
	
	// handle showing inventory details
	$('#inventory img').live('click', function() {
		var data = $(this).data('info');
		// TODO replace this alert with a popover div that looks nice
		alert(data.name + "\n" + data.description);
	});
	// handle chat log filters
	$('#chat-filter').change(function() {
		$('#chat').removeClass('only-all hide-enemy hide-allied only-reported').addClass(this.value);
	});
	// handle champonly checkbox
	$('#champ-only').change(function() {
		$.store.set('chat.champ-only', !!$('#champ-only').attr('checked'));
		if ($('#chat').hasClass('champ-only') != !!$('#champ-only').attr('checked'))
			$('#chat').toggleClass('champ-only');
	});
	
	// apply saved value to champonly checkbox
	$('#champ-only').attr('checked', !!$.store.get('chat.champ-only')).change();
	// handle refreshing captcha
	$('#refresh-captcha').click(reloadCaptcha);
	
	// handle submitting a verdict
	$('#pardon,#punish').click(function() {
		if (timeLeft > 0) return alert('Please spend more time reviewing the case');
		if ($(this).is('[disabled]')) return;
		showOnly('loading');
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: 'ajax.php',
			data: { cmd: 'sendVerdict', verdict: this.id, "captcha-result": $('#captcha-result').val() },
			success: processCaseSubmissionResult
		});
	});
	// handle the skip button
	$('#skip').click(function() {
		showOnly('loading');
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: 'ajax.php',
			data: { cmd: 'sendSkip' },
			success: processCaseSubmissionResult
		});
	});
	
	loadCase();
});

// shows only one component of the app and hides all the others
// nothing is hidden if called for an element that isn't a major part of the game
function showOnly(elemId) {
	var mainPageItems = ['login', 'game', 'submit', 'finished'];
	var alwaysHide = ['loading', 'games'];
	
	$('#'+elemId).show();
	$('.req-by-'+elemId).show(); // show anything required by this item
	$('.exc-by-'+elemId).hide(); // hide anything excluded by this item
	
	if ((i = mainPageItems.indexOf(elemId)) >= 0) { // was one of the main page items
		// hide other main page items
		mainPageItems.splice(i,1);
		$('#'+mainPageItems.join(',#')).hide();
		
		// regular page maintence
		$('#'+alwaysHide.join(',#')).hide();
		window.scroll(0,0);
	} else if (elemId === 'loading') window.scroll(0,0);
}

function doLogout() {
	showOnly('loading');
	$.ajax({
		type: 'POST',
		dataType: 'json',
		url: 'ajax.php',
		data: {cmd: 'logout'},
		success: function() {
			showOnly('login');
		}
	});
}

function submitLogin(event) {
	event.preventDefault();
	showOnly('loading');
	
	// perform the saving of inputs
	$.store.set('realm', $('#realm').val());
	$.store.set('username', $('#username').val());
	
	// submit the login
	$.ajax({
		type: 'POST',
		dataType: 'json',
		url: 'ajax.php',
		data: {
			cmd: 'login',
			username: $('#username').val(),
			password: $('#password').val(),
			realm: $('#realm').val()
		},
		success: processLoginResult
	});

	$('#password').val('');
}

function processLoginResult(response) {
	if (response.status === 'ok') {
		//TODO hide login form, show the case review stuff and load the case
		showOnly('game');
		loadCase();
	} else  {
		// put each elemnt of response.feedback as a paragraph in #feedback
		$('#feedback').html(response.feedback.join('<br>'));
		showOnly('login');
	}
}

function processCaseSubmissionResult(data) {
	showOnly('submit');
	if (data.status === 'failed')
		alert('Error communicating with Riot servers');
	else if (data.status === 'captchafail')
		alert('Incorrect captcha');
	else if (data.status === 'finished')
		showOnly('finished'); // TODO have a button to retry that checks if you are still expired
	else if (data.status === 'nosess')
		showOnly('login'); // TODO show login form instead of reloading
	else if (data.status === 'ok')
		loadCase();
}

function reloadCaptcha() {
	if (!window.captchaIsLoading) {
		window.captchaIsLoading = true;
		$.ajax({
			type: 'POST',
			dataType: 'text',
			url: 'ajax.php',
			data: { cmd: 'getCaptcha' },
			success: function(data) {
				$('#captcha').attr('src',data);
				$('#captcha-result').val('');
				window.captchaIsLoading = false;
				window.captchaLoaded = true;
			}
		});
	}
}

function loadCase() {
	showOnly('loading');
	window.captchaLoaded = false;
	window.captchaIsLoading = false;
	window.cachedGames = {};
	
	// handle verdict timer
	$('#timer-message').show();
	window.timeLeft = 60;
	if (!window.timerInterval)
		window.timerInterval = window.setInterval(timerTick, 1000);
	
	$('#captcha-result').val('');
	
	$.ajax({
		type: 'POST',
		dataType: 'json',
		url: 'ajax.php',
		data: { cmd: 'getCase' },
		success: function (data) {
			if (data.status === 'failed' || data.status === 'nosess') {
				return showOnly('login');
			}
			if ( data.status === 'finished' )
				showOnly('finished');
			else
			{
				var num = Number(data.numGames);
				if (num < 1) {
					showOnly('login');
					return alert('Could not get case data from Riot');
				}
				// create the list of games
				$('#games').empty();
				for (var i=1; i<=num; i++) {
					$('<li onclick="void(0)"></li>').attr('value',i).html('Game '+i).appendTo('#games');
				}
				
				$('#caseid').html(data.caseId);
				loadGame('1');
				// if we are loading for the first time, grab a new captcha in the background
				reloadCaptcha();
			}
		}
	});
}

function timerTick() {
	if ($('#game').is(':visible')) window.timeLeft -= 1;
	if (window.timeLeft >= 1) {
		$('#timer-message time').text(window.timeLeft);
		$('#verdict time').text('in '+window.timeLeft+'s');
		$('#pardon,#punish').attr('disabled', true);
	} else {
		window.clearInterval(window.timerInterval);
		delete window.timerInterval;
		$('#timer-message').hide();
		$('#verdict time').text('now');
		$('#pardon,#punish').attr('disabled', false);
	}
}

function loadGame(gameNumber) {
	showOnly('loading');
	$('#game-selected').html('Game '+gameNumber);
	
	if (!window.cachedGames[gameNumber]) {
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: 'ajax.php',
			data: { cmd: 'getGame', game: gameNumber },
			success: function(gameData) {
				if ( gameData.status === 'nosess' ) return showOnly('login');
				applyData(initData(gameData, gameNumber));
			}
		});
	} else {
		applyData(window.cachedGames[gameNumber]);
	}
}

// performs some parsing and caches the result of a fetched game
function initData(gameData, gameNumber) {
	// force secure links to images
	gameData.champion = gameData.champion.replace(/^http:/,'https:');
	for (var i=0; i<gameData.items.length; i++) {
		gameData.items[i].icon = gameData.items[i].icon.replace(/^http:/,'https:');
	}
	// try to fix missing champ names
	gameData.champsUsed = {};
	for (var i=0; i<gameData.stats.length; i++) {
		gameData.champsUsed[gameData.stats[i].NAME] = gameData.stats[i].SKIN;
	}
	// fix time string if it is over an hour
	if (Number(gameData.stats[0].TIME_PLAYED) === NaN) {
		var timechunks = /^(\d+)[\w\s]*?(\d+)$/.exec(gameData.stats[0].TIME_PLAYED);
		gameData.stats[0].TIME_PLAYED = Number(timechunks[1]) * 60 + Number(timechunks[2]);
	}
	// cache the fixed data
	window.cachedGames[gameNumber] = gameData;
	return gameData;
}

function applyData(gameData) {
	// expand the data into the #game div
	$('#summoner-name').text('"' + gameData.summoner + '"');
	$('#portrait img').attr('src', gameData.champion);
	$('#portrait img').attr('alt', gameData.champion_name || gameData.champsUsed[gameData.summoner]);
	$('#champname span').text(gameData.champion_name || gameData.champsUsed[gameData.summoner]);
	
	var stats = gameData.stats[0];
	$('#level').text(stats.LEVEL);
	$('#time').text(stats.TIME_PLAYED + ':00'); //fudge the time because riot only gives minutes
	$('#kills').text(stats.SCORES[0]);
	$('#deaths').text(stats.SCORES[1]);
	$('#assists').text(stats.SCORES[2]);
	$('#outcome').text(stats.WIN);
	$('#minion-kills').text(stats.MINIONS_KILLED);
	$('#dps-out').text(stats.TOTAL_DAMAGE_DEALT);
	$('#dps-in').text(stats.TOTAL_DAMAGE_RECEIVED);
	
	// setup inventory-container
	$('#inventory-container').empty();
	for (var i=0; i<gameData.items.length; i++) {
		var item = gameData.items[i];
		$('<img>')
			.attr('src', item.icon)
			.attr('title', item.name)
			.attr('alt', item.name)
			.data('info', item)
			.appendTo('#inventory-container');
	}
	
	// add reports
	$('#reports').empty();
	$('#allied-report-count').html(gameData.allied_report_count);
	var newDiv = $("<div/>");
	for (var i=0; i<gameData.allied_report_count; i++) {
		var report = newDiv.html(gameData.allycomments[i]).text();
		var reason = gameData.allyreportreasons[i];
		$('<li class="allied"></li>').html(report).append($('<h2></h2>').html(reason)).appendTo('#reports');
	}
	$('#enemy-report-count').html(gameData.enemy_report_count);
	for (var i=0; i<gameData.enemy_report_count; i++) {
		var report = gameData.enemycomments[i];
		var reason = gameData.enemyreportreasons[i];
		$('<li class="enemy"></li>').html(report).append($('<h2></h2>').html(reason)).appendTo('#reports');
	}
	
	// build chat log
	var $chat = $('#chat').empty();
	var chatLength = gameData.chatlogtext.length;
	for (var i=0; i<chatLength; i++) {
		var summoner = gameData.chatlogusers[i];
		var champion = gameData.chatlogchampions[i] || gameData.champsUsed[summoner];
		var timestamp = gameData.chatloggametime[i];
		var message = gameData.chatlogtext[i];
		var classes = gameData.chatlogteams[i];
		if (classes === 'reported') classes += ' allied';
		if (gameData.chatlogsentto[i] === 'All') classes += ' all';
		$('<li></li>').addClass(classes)
			.append(
				$('<span class="author"></span>')
					.append($('<span class="summoner"></span>').text(summoner))
					.append($('<span class="character"></span>').text(champion))
			)
			.append($('<time></time>').text(timestamp))
			.append(': '+message)
			.appendTo($chat);
	}
	
	// reset chat filter controls
	$('#chat-filter')[0].selectedIndex = 0;
	$('#chat-filter').change();
	
	// show our handywork
	showOnly('game');
}

function disclaim()
{
	alert("Mobile Tribunal is provided as is with no guarantees as to its functionality. While every effort is made to protect your information (all connections are encrypted with SSL), the Mobile Tribunal is not responsible for theft of your data, including, but not limited to, usernames and passwords.");
}
