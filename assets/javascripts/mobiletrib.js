$(function() {
	// handle opening and closing the menu
	$('#game-selected').click(function() {
		$('#games').toggle();
	});
	// handle switching games
	$('#games li').click(function() {
		$('#game-selected').html(this.innerHTML);
		$('#verdict').html('Submit Verdict');
		$('#games').hide();
		
		loadGame(this.value);
	});
	// handle showing the verdict options
	$('#verdict').click(function() {
		$('#game,#submit').toggle();
		$('#loading').hide();
		
		if ($('#game').is(':visible')) {
			$('#verdict').html('Submit Verdict');
		} else {
			$('#verdict').html('Show Game');
		}
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
	$('#refresh-captcha').click(function() {
		// ajax load captcha, then in the callback:
		$('#captcha').attr('src', base64string);
	});
	// handle verdict timer
	var interval, timeLeft = 60;
	interval = window.setInterval(function() {
		if ($('#game').is(':visible')) timeLeft -= 1;
		$('#timer-message time').html(timeLeft);
		if (timeLeft < 1) {
			window.clearInterval(interval);
			$('#timer-message').detach();
		}
	}, 1000);
	// handle submitting a verdict
	$('#pardon,#punish,#skip').click(function(event) {
		event.preventDefault();
		if (timeLeft > 0) {
			alert('Please spend more time reviewing the case');
			return;
		}
		// maybe this should just do a regular form submission?
		// (nope, because we should validate the response from riot and report errors without leaving the page)
	});
	
	// load the total number of games and populate our game list
	loadCase();
	window.captchaLoaded = false;
	window.captchaIsLoading = false;
});

function reloadCaptcha() {
	if (!window.captchaIsLoading) {
		window.captchaIsLoading = true;
		$.ajax({
			type: 'POST',
			dataType: 'text',
			url: '/ajax.php',
			data: { cmd: 'getCaptcha' },
			success: function(data) {
				$('#captcha').attr('src',data);
				window.captchaIsLoading = false;
			}
		});
	}
}

function loadCase() {
	$('#game,#submit').hide();
	$('#loading').show();
	
	$.ajax({
		type: 'POST',
		dataType: 'text',
		url: '/ajax.php',
		data: { cmd: 'getCase' },
		success: function (data) {
			var num = (int)data;
			if (num < 1) return alert('Could not get case data from Riot');
			for (var i=1; i<=num; i++) {
				$('<li></li>').attr('value',i).html('Case '+i).appendTo('#games');
			}
			loadGame('1');
		}
	});
}

function loadGame(gameNumber) {
	$('#game,#submit').hide();
	$('#loading').show();
	
	$.ajax({
		type: 'POST',
		dataType: 'json',
		url: '/ajax.php',
		data: { cmd: 'getGame', game: gameNumber },
		success: applyData
	});
}

function applyData(gameData) {
	// if we are loading for the first time, grab a new captcha in the background
	if (!window.captchaLoaded) reloadCaptcha();
	
	// expand the data into the #game div
	$('#summoner-name').html('"' + gameData.summoner + '"');
	$('#portrait img').attr('src', gameData.champion);
	
	var stats = gameData.stats[0];
	$('#level').html(stats.LEVEL);
	$('#time').html(stats.TIME_PLAYED + ':00'); //fudge the time because riot only gives minutes
	$('#kills').html(stats.SCORES[0]);
	$('#deaths').html(stats.SCORES[1]);
	$('#assists').html(stats.SCORES[2]);
	$('#outcome').html(stats.WIN);
	$('#creep-score').html(stats.MINIONS_KILLED);
	$('#dps-out').html(stats.TOTAL_DAMAGE_DEALT);
	$('#dps-in').html(stats.TOTAL_DAMAGE_RECEIVED);
	
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
	for (var i=0; i<gameData.allied_report_count; i++) {
		var report = gameData.allycomments[i];
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
		var champion = gameData.chatlogchampions[i];
		var timestamp = gameData.chatloggametime[i];
		var message = gameData.chatlogtext[i];
		var classes = gameData.chatlogteams[i];
		if (classes === 'reported') classes += ' allied';
		if (gameData.chatlogsentto[i] === 'All') classes += ' all';
		$('<li></li>').addClass(classes)
			.append(
				$('<span class="author"></span>')
					.append($('<span class="summoner"></span>').html(summoner))
					.append($('<span class="character"></span>').html(champion))
			)
			.append($('<time></time>').html(timestamp))
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
