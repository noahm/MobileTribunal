$(function() {
	// handle opening and closing the menu
	$('#game-selected').click(function() {
		$('#games').toggle();
	});
	// handle switching games
	$('#games li').live('click', function() {
		$('#game-selected').html(this.innerHTML);
		$('#verdict').show();
		$('#games,#return').hide();
		
		loadGame(this.value);
	});
	// handle showing the verdict options
	$('#verdict,#return').click(function() {
		$('#game,#submit,#verdict,#return').toggle();
		$('#loading').hide();
		window.scroll(0,0);
	});
	
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
		$('#chat').toggleClass('champ-only');
	});
	// handle refreshing captcha
	$('#refresh-captcha').click(reloadCaptcha);
	// handle submitting a verdict
	$('#pardon,#punish').click(function() {
		if (timeLeft > 0) return alert('Please spend more time reviewing the case');
		if ($(this).is('[disabled]')) return;
		$('#loading').show();
		$.ajax({
			type: 'POST',
			dataType: 'text',
			url: 'ajax.php',
			data: { cmd: 'sendVerdict', verdict: this.id, "captcha-result": $('#captcha-result').attr('value') },
			success: processCaseResult
		});
	});
	$('#skip').click(function() {
		$('#loading').show();
		$.ajax({
			type: 'POST',
			dataType: 'text',
			url: 'ajax.php',
			data: { cmd: 'sendSkip' },
			success: processCaseResult
		});
	});
	
	loadCase();
});

function processCaseResult(data) {
	$('#loading').hide();
	if (data === '0')
		alert('Your submission was not valid');
	else if (data === 'failed')
		alert('Incorrect captcha');
	else if (data === 'finished')
		showFinished();
	else if (data === 'nosess')
		location.reload();
	else
		loadCase();
}

function showFinished() {
	$('#game,#submit,#loading,#title').hide();
	$('#finished').show();
	return true;
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
				window.captchaIsLoading = false;
				window.captchaLoaded = true;
			}
		});
	}
}

function loadCase() {
	window.captchaLoaded = false;
	window.captchaIsLoading = false;
	window.cachedGames = {};
	
	// handle verdict timer
	$('#timer-message').show();
	window.timeLeft = 60;
	timerTick();
	window.timerInterval = window.setInterval(timerTick, 1000);
	
	$('#captcha-result').attr('value', '');
	
	// assure the right things are visible
	$('#game,#submit').hide();
	$('#loading').show();
	$('#game-selected').html('Game 1');
	$('#games').empty();
	
	$.ajax({
		type: 'POST',
		dataType: 'json',
		url: 'ajax.php',
		data: { cmd: 'getCase' },
		success: function (data) {

			if ( data.caseId === 'finished' )
				showFinished();
			else
			{
				var num = Number(data.numGames);
				if (num < 1) return alert('Could not get case data from Riot');
				for (var i=1; i<=num; i++) {
					$('<li onclick="void(0)"></li>').attr('value',i).html('Game '+i).appendTo('#games');
				}
				$('#caseid').html(data.caseId);
				loadGame('1');
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
		$('#timer-message').hide();
		$('#verdict time').text('now');
		$('#pardon,#punish').attr('disabled', false);
	}
}

function loadGame(gameNumber) {
	$('#game,#submit,#return,#games').hide();
	$('#loading,#verdict').show();
	
	if (!window.cachedGames[gameNumber]) {
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: 'ajax.php',
			data: { cmd: 'getGame', game: gameNumber },
			success: function(gameData) {
				if ( gameData === 'nosess' )
					return location.reload();
				gameData = initData(gameData, gameNumber);
				applyData(gameData);
			}
		});
	} else {
		applyData(window.cachedGames[gameNumber]);
	}
}

function initData(gameData, gameNumber) {
	window.cachedGames[gameNumber] = gameData;
	gameData.champion = gameData.champion.replace(/^http:/,'https:');
	for (var i=0; i<gameData.items.length; i++) {
		gameData.items[i].icon = gameData.items[i].icon.replace(/^http:/,'https:');
	}
	gameData.champsUsed = {};
	for (var i=0; i<gameData.stats.length; i++) {
		gameData.champsUsed[gameData.stats[i].NAME] = gameData.stats[i].SKIN;
	}
	return gameData;
}

function applyData(gameData) {
	// if we are loading for the first time, grab a new captcha in the background
	if (!window.captchaLoaded) reloadCaptcha();
	
	// expand the data into the #game div
	$('#summoner-name').text('"' + gameData.summoner + '"');
	$('#portrait img').attr('src', gameData.champion);
	$('#portrait img').attr('alt', gameData.champsUsed[gameData.summoner]);
	$('#champname span').text(gameData.champsUsed[gameData.summoner]);
	
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
	var $chat = $('#chat').removeClass().empty();
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
	$('#champ-only')[0].checked = false;
	
	// show our handywork
	$('#loading').hide();
	$('#game').show();
	window.scroll(0,0);
}

function disclaim()
{
	alert("Mobile Tribunal is provided as is with no guarantees as to its functionality. While every effort is made to protect your information (all connections are encrypted with SSL), the Mobile Tribunal is not responsible for theft of your data, including, but not limited to, usernames and passwords.");
}
