$(function () {

	var SQUARESIZE = 48;	
	var serverurl = "game.php";

    //initialize gamespace with divs
	$( '.playingfield').each (function () {		

		for (var i = 0; i < 8; i++) {
			$(this).append('<div class="file" file='+i+' style="height: '+7*SQUARESIZE+'px;"></div>');
		}
		
		$( '.file', $( this ) ).each( function () {

			var file = $(this).attr("file");
		
			for (var i = 0; i <= 6; i++) {
				var d = 'bottom';
				if ($($(this).parent()).hasClass('playingfield-bottom')) d = 'top';
				
				if (i < 6) {
					$( this ).append('<div class="cell" rank='+i+' style="'+d+':'+i*SQUARESIZE+'px;"></div>');
				}
				else {
					$( this ).append('<div class="gutter" style="'+d+':'+i*SQUARESIZE+'px;"></div>');
				}
			}

		});

	});

	var timeoutId = 0;
	var clickObject = new Object;
	var gutter = new Object;
	
	function cellDeleteMenu() {
		//spawn popup
		var element = $('#delete').detach();
		$(clickObject.object).append(element);		
		$('#delete').show();		
	}
	
	function pickupUnit() {
	
		if (!jQuery.isEmptyObject(gutter)) {
		
			//console.log('placing unit');
			
			//console.log(battlefieldData.CurrentState[clickObject.player].Board[clickObject.file].length);			
			if (battlefieldData.Boards[clickObject.player].State[clickObject.file].length >= 6) return;

			//console.log('pushing into place');
    		battlefieldData.Boards[clickObject.player].State[clickObject.file].push(gutter);
			
			//console.log('check whether to push to server');
			if (clickObject.file != gutter.fileSource) {
				
				$.ajax({
					type: "POST",
					url: serverurl,
					dataType: "json",
					data: {action: "move", player: battlefieldData.Boards[clickObject.player].PlayerID, fileSource: gutter.fileSource, fileTarget: clickObject.file, GameID: battlefieldData.GameID}
				}).done(function (data) {

					var target = $(this);

					console.log('Move Complete!');
					console.log(data);
					
					battlefieldData = data;

					updateMovesDisplay(battlefieldData.Moves.Left);
					processEvents(0);
					return;
				});

			}				
			else {
			
				console.log('source and destination are the same. clear and redraw');
				clearField();
				fieldArmy();
			}			
			
			//console.log('empty gutter');			
			$( '.gutter img' ).detach();
			gutter = new Object;

		}
		else {				
		
			//console.log('pickup');

			gutter = battlefieldData.Boards[clickObject.player].State[clickObject.file].pop();
			gutter.fileSource = clickObject.file;
			
			$( '.gutter', $( '.playingfield-bottom .file')[clickObject.file] )[0].innerHTML = '<img src="img/armies/debug/'+gutter.Color+'.gif"/>';			
						
			clearField();
			fieldArmy();
		
		}
		
		
		
	}
	
	$ ('#gameboard').on('mousedown', '.cell', function (event) {		

		var file = $(this).parent();
		var player = new String;
		if ($(file).parent().hasClass('playingfield-top')) player = 'Opponent';
		if ($(file).parent().hasClass('playingfield-bottom')) player = 'Player';
		
		for (var i in battlefieldData.Boards) {	
			
			//console.log(player, i, battlefieldData.Boards[i].IsMe);
			
			if ((player == 'Player' && battlefieldData.Boards[i].IsMe) || 
				(player == 'Opponent' && !battlefieldData.Boards[i].IsMe)) {
					var index = i; 							
			}						
		}		
		
		

		clickObject = {		
			player: index,
			file: $(file).attr('file'),
			rank: $(this).attr('rank'),
			object: $(this)[0]
			};
		
		switch (event.which) {
			case 1: //left mouse button
			if ($(this).children('img').length == 1) {				
				timeoutId = setTimeout(cellDeleteMenu, 300);
			}			
			break;
			
			case 2: //middle mouse button			
			break;
			
			case 3: //right mouse button
			if ($(this).children('img').length == 1) {				
				cellDeleteMenu();
			}
			break;
		}
		
	}).on('mouseup', function() {
	    	clearTimeout(timeoutId);

	}).on('mouseover', '.cell', function () {
		if ( $(this).find('img').length > 0) {
			$(this).addClass('del-highlight');
		}

	}).on('mouseout', '.cell', function () {
		$(this).removeClass('del-highlight');	
		
	}).on('click', '.gutter', function () {	
		
		var file = $(this).parent();
		var player = new String;
		if ($(file).parent().hasClass('playingfield-top')) player = 'Opponent';
		if ($(file).parent().hasClass('playingfield-bottom')) player = 'Player';
		
		for (var i in battlefieldData.Boards) {	
			
			//console.log(player, i, battlefieldData.Boards[i].IsMe);
			
			if ((player == 'Player' && battlefieldData.Boards[i].IsMe) || 
				(player == 'Opponent' && !battlefieldData.Boards[i].IsMe)) {
					var index = i; 							
			}						
		}	

		clickObject = {		
			player: index,
			file: $(file).attr('file'),
			rank: $(this).attr('rank'),
			object: $(this)[0]
			};
				
		pickupUnit();
		
		
	}).on('mouseover', '.file', function () {	
		if (typeof gutter !== 'undefined') {
		
			var element = $( '.gutter img' ).detach();			
			$( '.gutter', $(this) ).append(element);		
		}
	
	});

	$( '#gameboard').on('click', '#delete', function () {

		$('#delete').hide();
		var element = $('#delete').detach();
		$('body').append(element);		

		$.ajax({
			type: "POST",			
			url: serverurl,
			dataType: "json",			
			data: {action: "delete", player: battlefieldData.Boards[clickObject.player].PlayerID, file: clickObject.file, rank: clickObject.rank, GameID: battlefieldData.GameID}
		}).done(function (data) {		

			console.log('Delete Complete!');
			console.log(data);

			battlefieldData = data;

			updateMovesDisplay(battlefieldData.Moves.Left);
			processEvents(0);

		});

	});
	
	function updateMovesDisplay(numMoves) {
		$( '.portrait', '.player-bottom')[0].innerHTML = numMoves; 		
	}

	function fieldArmy() {
		
		console.log(battlefieldData);
		
        $( '.playingfield').each (function () {
            
            var player = new String();
            if ($(this).hasClass('playingfield-bottom')) player = 'Player';
            if ($(this).hasClass('playingfield-top')) player = 'Opponent';
			
			for (var i in battlefieldData.Boards) {	
				if ((player == 'Player' && battlefieldData.Boards[i].IsMe) || 
					(player == 'Opponent' && !battlefieldData.Boards[i].IsMe)) {
						var index = i; 							
				}						
			}			
		
            $( '.file', $( this ) ).each( function () {

                var file = $(this).index();

                $( '.cell', $( this )).each( function () {

                    var rank = $(this).index();
					
                    if (battlefieldData.Boards[index].State[file]) {
                        if (battlefieldData.Boards[index].State[file][rank]) {
                            switch (battlefieldData.Boards[index].State[file][rank].Type) {
                                case 'Unit':
                                    $(this)[0].innerHTML = '<img src="img/armies/debug/'+battlefieldData.Boards[index].State[file][rank]['Color']+'.gif"/>';
                                    break;
                                case 'Charge':
                                    $(this)[0].innerHTML = '<img src="img/armies/debug/'+battlefieldData.Boards[index].State[file][rank]['Color']+'_charge.gif"/>';
                                    break;
                                case 'Wall':
                                    $(this)[0].innerHTML = '<img src="img/armies/debug/wall.PNG"/>';
                                    break;
                                default:
                                    console.log(battlefieldData.Boards[index].State[file][rank].Type);
                            }
                        }
                    }
                })
            });

        });

	}
	
	function clearField()
	{
		$( '.playingfield-bottom .file' ).each(function () {
			var file = $(this).index();
			$(this).children('.cell').each(function () {
				var rank = $(this).index();

				$(this)[0].innerHTML = '';

			});
		});
	}
	
	function processEvents(offset)
	{
		console.log(offset);
		for (var i in battlefieldData.Events) {
			if (i == 0) i = offset;			
			if (i >= battlefieldData.Events.length) {
				clearField();
				fieldArmy();
				break;
			}
			
			if (battlefieldData.Events[i].Actor == "Game") {
			
				console.log(battlefieldData.Events[i]);
			
				switch (battlefieldData.Events[i].Action) {
					
					case "Formation":
					var Ranks = new Array();
					for (var j in battlefieldData.Events[i].Ranks) {
						Ranks.push(".cell[rank="+battlefieldData.Events[i].Ranks[j]+"]");
					}					
					
					$( Ranks.join(), $( ".file[file="+battlefieldData.Events[i].File+"]", ".playingfield-bottom" )).each(function () {
						$(this)[0].innerHTML = '<img src="img/armies/debug/'+battlefieldData.Events[i].Color+'_charge.gif"/>';												
					});				
					
					break;
					
					case "Defend":					
					var Files = new Array();
					for (var j in battlefieldData.Events[i].Files) {
						Files.push(".file[file="+battlefieldData.Events[i].Files[j]+"]");
					}				
										
					$( Files.join(), ".playingfield-bottom" ).each(function () {						
						$( ".cell[rank="+battlefieldData.Events[i].Rank+"]", $(this) )[0].innerHTML = '<img src="img/armies/debug/wall.PNG"/>';
					});		
					break;
					
					case "Swap":					
					var a = $( ".cell[rank="+battlefieldData.Events[i].Rank+"]", $( ".file[file="+battlefieldData.Events[i].File+"]", ".playingfield-bottom" ) );
					var b = $( ".cell[rank="+(battlefieldData.Events[i].Rank-1)+"]", $( ".file[file="+battlefieldData.Events[i].File+"]", ".playingfield-bottom" ) );
										
					var c = $(a)[0].innerHTML;					
					$(a)[0].innerHTML = $(b)[0].innerHTML;
					$(b)[0].innerHTML = c;
					break;
					
					/*
					case "Banner Event":
					$( '#announce' )[0].innerHTML = battlefieldData.Events[i].Message;
					$( '#announce' ).show("slide", {"direction": "right", "easing" : "easeOutQuart"}, 1000, callback);				
					break;
					*/
				}
									
			}			
			
			setTimeout(processEvents, 1000, ++i);
			break;
		}				
	}
	
    function callback()
	{
		setTimeout(function() {
        	$( "#announce" ).hide("slide", {"direction": "left", "easing" : "easeInQuart"}, 500);
      	}, 1000 );
    }
	
	updateMovesDisplay(battlefieldData.Moves.Left);
	processEvents(0);
    fieldArmy();

});