$(function() {
	var gists = { "1": "1307687", "2": "1307691", "3": "1307692" };
	// handle switching games
	$('#game-selector').change(function() {
		if (this.value === "submit") {
			$('#game,#loading').hide();
			$('#submit').show();
		} else {
			$('#game,#submit').hide();
			$('#loading').show();
			// ajax load the game number specified by this.value
			$.getJSON(
				'/gh/gist/response.json/'+gists[this.value]+'/',
				applyData
			);
		}
	});
	// handle showing inventory details
	$('#inventory img').live('click', function() {
		// look up item info from the attached data (this.dataSet)
		var data = $(this).data('info');
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
	// force initial game load
	$('#game-selector').change();
});

function applyData(gameData) {
	$('#submit,#game').hide();
	$('#loading').show();
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
