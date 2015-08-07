<!DOCTYPE html>
<html>
<head>
    <title>Clash of Clones - what</title>
    <script type="text/javascript" src="jquery-1.11.1.min.js"></script>
    <script type="text/javascript" src="jquery-ui.min.js"></script>
    <script type="text/javascript" src="game.js"></script>
    <link rel="stylesheet" type="text/css" href="game.css">
</head>
<body>



	<div id="gameboard">
		<div class="player player-top">
			<div class="bars"></div>
			<div class="portrait" style="background-image:url('img/portraits/sean.png')"></div>
		</div>

		<div class="playingfield playingfield-top"></div>
		<div id="centerline"></div>
		<div class="playingfield playingfield-bottom"></div>

		<div class="player player-bottom">
			<div class="bars"></div>
			<div class="portrait" style="background-image:url('img/portraits/underdog.png')"></div>
		</div>
	</div>

	<span id="delete">X</span>



<button id="spawnarmy-bottom">Spawn Army</button>
<button id="clear-bottom">Clear</button>

	<!--
    <button id="reinforcements-left">Reinforcements</button>
    -->

<div id="footer">
Clash of Clones Game (2014-2015)<br/>
PHP version <?php echo phpversion() ?><br/>
Path: <?php echo getcwd () ?>
</div>

</body>
</html>