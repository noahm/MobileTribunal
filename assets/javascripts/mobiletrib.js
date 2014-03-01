/* Copyright (c) 2012 kayson (kaysond) & Noah Manneschmidt (psoplayer)
 * https://github.com/noahm/MobileTribunal
 *
 * Licensed under the MIT License.
 * https://raw.github.com/noahm/MobileTribunal/master/mit-license.txt
 */

// Array Remove - By John Resig (MIT Licensed) http://ejohn.org/blog/javascript-array-remove/
Array.prototype.remove = function(from, to) {
	var rest = this.slice((to || from) + 1 || this.length);
	this.length = from < 0 ? this.length + from : from;
	return this.push.apply(this, rest);
};

var _gaq = _gaq || [];
_gaq.push(['_setAccount', 'UA-30304199-1']);
_gaq.push(['_trackPageview']);

$(function() {
	// in case there is an updated version
	if (window.applicationCache) {
		window.applicationCache.addEventListener('updateready', onUpdateReady);
		if (window.applicationCache.status === window.applicationCache.UPDATEREADY) {
			onUpdateReady();
		}
	}

	// init the login form
	$('#login form').submit(submitLogin);
	$('#realm').val($.store.get('realm'));
	$('#username').val($.store.get('username'));
	$.store.clear('password'); // just in case they used the original version

	// logout handler
	$('#logout').click(doLogout);

	//recaptcha refresh
	$('#recaptcha_img').click(loadRecaptcha);

	// handle opening and closing the menu
	$('#game-selected').tappable(function() {
		$('#games').toggle();
	});
	// handle switching games
	$(document).on('click', '#games li', function(event) {
		$('#games').hide();
		loadGame(this.value);
	});
	// handle showing the verdict options
	$('#verdict').tappable(function() {showOnly('submit');});
	$('#return').tappable(function() {showOnly('game');});

	// handle showing inventory details
	$(document).on('click', '#inventory img', function() {
		var data = $(this).data('info');
		// TODO replace this alert with a popover div that looks nice
		alert(data.name + "\n" + data.description);
	});
	// handle chat log filters
	$('#chat-filter').change(function() {
		$('#chat').removeClass('only-all hide-enemy hide-allied only-reported').addClass(this.value);
	});

	// handle refreshing captcha
	//$('#refresh-captcha').click(reloadCaptcha);

	// handle submitting a verdict
	$('#pardon,#punish').click(function() {
		if (timeLeft > 0) return alert('Please spend more time reviewing the case');
		if ($(this).is('[disabled]')) return;
		showOnly('loading');
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: 'ajax.php',
			data: { cmd: 'sendVerdict', verdict: this.id, "captcha-result": "" }, //$('#captcha-result').val() },
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

	//Initial ajax request
	$.ajax({
		type: 'POST',
		dataType: 'json',
		url: 'ajax.php',
		data: { cmd: 'getCase' },
		success: function (data) {
			if (data.status === 'failed' || data.status === 'nosess') {
				loadRecaptcha();
				return showOnly('login');
			}
			if ( data.status === 'finished' ) {
				return showOnly('finished');
			}
			if ( data.status === 'recess' ) {
				return showOnly('recess');
			}
			if ( data.status === 'ok' ) {
				loadCase(data);
			}
		}
	});

});

function onUpdateReady() {
	if (confirm('An update is available. Press OK to reload this page and apply the update.')) window.location.reload();
}

function urlPrefix() {
	if (!window.imgPrefix) {
		window.imgPrefix = 'http://' + $('#realm').val() + '.leagueoflegends.com';
	}
	return window.imgPrefix;
}

function formatImageUrl(url) {
	return url.replace('/tribunal/bundles/riothelper/game_data/3.03.0.1/', urlPrefix()+'/tribunal/bundles/riothelper/game_data/3.03.0.1/');
}

// shows only one component of the app and hides all the others
// nothing is hidden if called for an element that isn't a major part of the game
function showOnly(elemId) {
	var mainPageItems = ['login', 'game', 'submit', 'finished', 'recess', 'underlevel', 'unknown'];
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
	_gaq.push(['_trackEvent', 'User', 'Logout', 'Manual']);
	showOnly('loading');
	$.ajax({
		type: 'POST',
		dataType: 'json',
		url: 'ajax.php',
		data: {cmd: 'logout'},
		success: function() {
			loadRecaptcha();
			return showOnly('login');
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
			realm: $('#realm').val(),
			recaptcha_response: $('#recaptcha_response').val(),
			recaptcha_challenge: $('#recaptcha_challenge').val()
		},
		success: processLoginResult
	});

	$('#password').val('');
}

function processLoginResult(data) {
	if (data.status === 'ok') {
		_gaq.push(['_trackEvent', 'User', 'Login '+$('#realm').val(), 'Success']);
		showOnly('game');
		loadCase(data);
	} else if (data.status === 'error') {
		// put each elemnt of response.feedback as a paragraph in #feedback
		$('#feedback').html(data.feedback.join('<br>'));
		loadRecaptcha();
		showOnly('login');
	} else {
		showOnly(data.status);
	}
}

function processCaseSubmissionResult(data) {
	showOnly('submit');
	if (data.status === 'failed') {
		_gaq.push(['_trackEvent', 'Case', 'Submit', 'Comm Error']);
		alert('Error communicating with Riot servers');
	}
	else if (data.status === 'captchafail') {
		_gaq.push(['_trackEvent', 'Case', 'Submit', 'Captcha Failed']);
		alert('Incorrect captcha');
	}
	else if (data.status === 'finished') {
		_gaq.push(['_trackEvent', 'Case', 'Submit', 'Accepted']);
		_gaq.push(['_trackEvent', 'User', 'Cases Exhausted']);
		showOnly('finished'); // TODO have a button to retry that checks if you are still expired
	}
	else if (data.status === 'nosess') {
		_gaq.push(['_trackEvent', 'User', 'Logout', 'Timed out']);
		showOnly('login');
	}
	else if (data.status === 'ok') {
		_gaq.push(['_trackEvent', 'Case', 'Submit', 'Accepted']);
		loadCase(data);
	}
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

function loadRecaptcha() {
	$.ajax({
		type: 'POST',
		dataType: 'json',
		url: 'ajax.php',
		data: { cmd: 'getRecaptcha' },
		success: function(data) {
			$('#recaptcha_img').attr('src',data.image);
			$('#recaptcha_challenge').val(data.challenge);
			$('#recaptcha_response').val('');
		}
	});
}

function loadCase(data) {
	showOnly('loading');
	//window.captchaLoaded = false;
	//window.captchaIsLoading = false;
	window.cachedGames = {};

	// handle verdict timer
	$('#timer-message').show();
	window.timeLeft = 20;
	if (!window.timerInterval)
		window.timerInterval = window.setInterval(timerTick, 1000);

	$('#captcha-result').val('');
	var num = Number(data.numGames);
	if (num < 1) {
		showOnly('login');
		return alert('Could not get case data from Riot');
	}
	// create the list of games
	$('#games').empty();
	for (var i=1; i<=num; i++) {
		$('<li onclick="void(0)"></li>').attr('value',i).html('<div></div> Game '+i).appendTo('#games');
	}

	$('#caseid').html(data['case']);
	$('#votes-today').html(data['votesToday']);
	$('#votes-allowed').html(data['votesAllowed']);
	loadGame('1');
	// if we are loading for the first time, grab a new captcha in the background
	//reloadCaptcha();
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
		_gaq.push(['_trackEvent', 'Case', 'Load Game', 'Uncached', gameNumber]);
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
		_gaq.push(['_trackEvent', 'Case', 'Load Game', 'Cached', gameNumber]);
		applyData(window.cachedGames[gameNumber]);
	}
}

// performs some parsing and caches the result of a fetched game
function initData(gameData, gameNumber) {
	// massage some player data
	for( var i = 0; i < gameData.players.length; i++ ) {
		var player = gameData.players[i];
		// fill empty item slots with our empty image
		while (player.item_icons.length < 6) {
			player.item_icons.push('width:48px;height:48px;background-image:url(assets/images/itemslot.png);background-size:48px;');
		}
		if( player.association_to_offender == 'offender' ) {
			gameData.offender = player;
		}
	}
	// calculate a regular timestamp
	var minutes = gameData.offender.time_played % 60;
	if (minutes < 10) minutes = '0' + minutes;
	gameData.time_played = Math.floor(gameData.offender.time_played / 60) + ':' + minutes;
	// cache the fixed data
	window.cachedGames[gameNumber] = gameData;
	// apply champion portrait in games list - removed because riot doesn't send individual images anymore
	var img = $('#games li[value='+gameNumber+'] div').attr('style', gameData.offender.champion_icon);
	return gameData;
}

function applyData(gameData) {
	var i, item;
	// expand the data into the #game div
	$('#portrait-img').attr('style', gameData.offender.champion_icon);
	$('#champname span').text(gameData.offender.champion_name);
	$('#summoner1').attr('style', gameData.offender.summoner_spell_1_icon);
	$('#summoner2').attr('style', gameData.offender.summoner_spell_2_icon);

	$('#level').text(gameData.offender.level);
	$('#time').text(gameData.time_played);
	// we should probably display different stats if dominion
	$('#kills').text(gameData.offender.scores.kills);
	$('#deaths').text(gameData.offender.scores.deaths);
	$('#assists').text(gameData.offender.scores.assists);
	$('#outcome').text(gameData.offender.outcome);
	$('#minion-kills').text(gameData.offender.minions_killed);
	$('#gold-earned').text(gameData.offender.gold_earned);
	$('#date-played').text(gameData.game_creation_time);
	$('#dps-out').text(gameData.offender.total_damage_dealt);
	$('#dps-in').text(gameData.offender.total_damage_received);

	// setup inventory-container
	$('#inventory-container').empty();
	for (i=0; i<gameData.offender.item_icons.length; i++) {
		item = gameData.offender.item_icons[i];
		$('<div>')
			.attr('style', item)
			.appendTo('#inventory-container');
	}

	//display teammates
	$('#teammates tbody').empty();
	for (var playerid in gameData.players) {
		var player = gameData.players[playerid];
		if (player.association_to_offender === 'ally') {
			var teammate = $('#teammates thead tr.template').clone();
			teammate.removeClass('template').addClass('teammate');
			teammate.find('.port div').attr('style', player.champion_icon);
			teammate.find('.level').html(player.level);
			teammate.find('.champname').html(player.champion_name);
			teammate.find('.score').html(player.scores.kills +'/'+ player.scores.deaths +'/'+ player.scores.assists);
			teammate.find('.summ1').attr('style',player.summoner_spell_1_icon);
			teammate.find('.summ2').attr('style',player.summoner_spell_2_icon);
			teammate.find('.gold').html(player.gold_earned > 1000 ? (Math.round(player.gold_earned / 1000) + 'k') : player.gold_earned);
			teammate.find('.cs').html(player.minions_killed);
			for (i = player.item_icons.length - 1; i >= 0; i--) {
				item = teammate.find('.item'+i);
				item.attr('style', player.item_icons[i]);
			}
			teammate.appendTo('#teammates tbody');
		}
	}

	// add reports
	$('#reports').empty();
	$('#allied-report-count').html(gameData.allied_report_count);
	$('#enemy-report-count').html(gameData.enemy_report_count);
	for (i = gameData.reports.length - 1; i >= 0; i--) {
		var report = gameData.reports[i];
		item = $('<li></li>');
		item.addClass(report.association_to_offender);
		item.html(report.comment).append($('<h2></h2>').html(gameData.translated_report_reasons[report.offense]));
		if (report.association_to_offender === 'ally') {
			item.prependTo('#reports');
		} else {
			item.appendTo('#reports');
		}
	}

	// build chat log
	var $chat = $('#chat').empty();
	for (i = gameData.chat_log.length - 1; i >= 0; i--) {
		var chat_line = gameData.chat_log[i];
		var classes = chat_line.association_to_offender;
		if (classes === 'offender') classes += ' ally';
		if (chat_line.sent_to === 'All') classes += ' all';
		$('<li></li>').addClass(classes)
			.append(
				$('<span class="author"></span>')
					.append($('<span class="character"></span>').text(chat_line.champion_name))
			)
			.append($('<time></time>').text(chat_line.time))
			.append(': '+chat_line.message)
			.prependTo($chat);
	}

	// reset chat filter controls
	$('#chat-filter')[0].selectedIndex = 0;
	$('#chat-filter').change();

	// show our handywork
	showOnly('game');
}

function disclaim() {
	alert("Mobile Tribunal is provided as is with no guarantees as to its functionality. While every effort is made to protect your information (all connections are encrypted with SSL), the Mobile Tribunal is not responsible for theft of your data, including, but not limited to, usernames and passwords.");
}
