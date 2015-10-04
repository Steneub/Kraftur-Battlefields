<?php

include_once('config.php');

class GameState
{
	public $Messages = Array();

    function __construct($GameID = NULL)
    {	
		
		if (is_array($GameID))
		{
			extract($GameID);
			
			$this->Name = $GameName; 
			$this->Players = $Players;
			$this->CreateFreshGame();
		}
				
		elseif (isset($GameID))
		{
            $this->GameID = $GameID;			
			$this->CurrentState = mysql_fetch_assoc($this->GetCurrentGameState());
			
			$this->LastUpdate = $this->CurrentState['Timestamp']; 	
			
			//echo '<pre>';
			//print_r($this->CurrentState);			
			//echo '</pre>';
			
			$this->CurrentState = json_decode($this->CurrentState['State'], true);							
						
			$this->Moves = $this->CurrentState['Moves'];
			$this->Boards = $this->CurrentState['Boards'];
			$this->Events = $this->CurrentState['Events'];			
			
			global $LoggedInUser;
				
			foreach($this->Boards as $BoardIndex => &$BoardData) {	
				
				if ($BoardData['PlayerID'] == $LoggedInUser->UserID) {
					$BoardData['IsMe'] = TRUE;					
					$this->BoardStatePlayerIndex = $BoardIndex;
				}
				else {
					$BoardData['IsMe'] = FALSE;
					$this->BoardStateOpponentIndex = $BoardIndex;
				}
			}
				
		}
	}
	
	function CreateFreshGame() {
		
		$this->Messages[] = "Building fresh game";
		global $LoggedInUser;
		
		foreach($this->Players as $PlayerID) {
			
			$this->BuildArmyDeck();
            $this->SpawnArmy();
						
			$this->Boards[] = Array(
				"PlayerID"=>$PlayerID,
				"CurrentPlayer"=> $PlayerID == 1,
				"IsMe"=> $PlayerID == $LoggedInUser->UserID,
				"State"=>$this->Field); //TODO - randomize the starting player 
		}		
		
		$this->Moves['Used'] = 0;
		$this->Moves['Left'] = 3;
		
		$this->Events[] = Array("Action"=>"Banner Event", "Actor" => "Game", "Message"=>"It is {$LoggedInUser->UserInfo['Name']}'s Turn!");
		$this->GameID = $this->RegisterGame($this->Players);
		$_SESSION['GameID'] = $this->GameID;
		$this->BuildCurrentState();
	
		$this->RecordGameState();					
		
	}

    function InitializeGameState() {
		
		$this->BuildCurrentState();

        ?>
        <script type="text/javascript">
            var battlefieldData = <?php echo $this->CurrentState; ?>;
        </script>
    	<?php
    }

    function GetCurrentGameState() {

        global $handle, $MySQL_context;
        $sql = "SELECT * FROM {$MySQL_context}Game_Events 
				WHERE Game = {$this->GameID}
				ORDER BY `TimeStamp` DESC 
				LIMIT 1";

        if (!$sql_result = mysql_query($sql,$handle)) die(mysql_error($handle));
        return $sql_result;
    }
	
	function GetUpdatesSinceTimestamp($Timestamp) {
		
		global $handle, $MySQL_context;		
 		$sql = "SELECT `ID` FROM {$MySQL_context}Game_Events 
				WHERE Game = {$this->GameID}
				AND Timestamp > '{$Timestamp}'";
		
		$this->Messages[] = $sql;
						
		if (!$sql_result = mysql_query($sql,$handle)) die(mysql_error($handle));
        return mysql_num_rows($sql_result);				
	}		
	
	function RecordGameState() {
		
		global $handle, $MySQL_context;
		$sql = "INSERT {$MySQL_context}Game_Events (`Game`, `State`) VALUES    			
    			({$this->GameID}, '".str_replace("'", "\'", $this->CurrentState)."')";
				
		if (!mysql_query($sql)) die(mysql_error($handle).$sql);
	}	

    function RegisterGame($Players) {

        global $handle, $MySQL_context;

        $sql = "INSERT INTO {$MySQL_context}Game (`Name`, `Player1`, `Player2`)
                VALUES ('".addslashes($this->Name)."', {$Players[0]}, {$Players[1]});";

        if (!mysql_query($sql)) die(mysql_error($handle));

        return mysql_insert_id($handle);

    }
	


	function BuildCurrentState()
	{
		$this->Messages[] = "Sorts: ".$this->Sorts;
		$this->CurrentState = JSON_encode(
				Array(
					"GameID"=>$this->GameID,
					"TimeStamp"=>$this->LastUpdate, 
					"Messages"=>$this->Messages, 
					"Events"=>$this->Events, 
					"Matches"=>$this->Matches,
					"Moves"=>
						Array(
							"Used"=>$this->Moves['Used'],
							"Left"=>$this->Moves['Left']
						),						
					"Boards"=>$this->Boards
				)
			);
	}
	
	function SwitchPlayers() {
		foreach($this->Boards as $BoardIndex => &$BoardData) {			
			$BoardData['CurrentPlayer'] = !$BoardData['CurrentPlayer'];
			if ($BoardData['CurrentPlayer']) {
				
				$PlayerUserInfo = User::GetUserInfo($BoardData['PlayerID']);
				$this->Events[] = Array("Action"=>"Banner Event", "Actor" => "Game", "Message"=>"It is {$PlayerUserInfo['Name']}'s Turn!");
			} 
		}
		
		$this->Moves['Used'] = 0;
		$this->Moves['Left'] = 3;
		 
	}

	function BuildArmyDeck()
	{
		$this->Deck = Array("red"=>11, "blue"=>11, "green"=>11);
	}

	function SpawnArmy()
	{

		for ($i = 0; $i < 8; $i++) $Field[$i] = Array();

		while (array_sum($this->Deck) > 0)
		{
			//pick a random file
			$File = rand(1,8) - 1;

			//if the file is full, go around again
			if (count($Field[$File]) == 6) continue;

			//pick a random color
			do {
				$Color = rand(1,3) - 1;
				switch ($Color)
				{
					case 0: $Color = "red"; break;
					case 1: $Color = "blue"; break;
					case 2: $Color = "green"; break;
				}
			} while ($this->Deck[$Color] < 1);

			$Rank = count($Field[$File]);

			//would placing this piece result in a match?

			//look for stacks
			if ($Field[$File][$Rank - 1]['Color'] == $Field[$File][$Rank - 2]['Color'] &&
				$Field[$File][$Rank - 2]['Color'] == $Color)
			{
				continue;
			}

			//look for rows
			//2 on the "left"
			if ($Field[$File-1][$Rank]['Color'] == $Color && $Field[$File-2][$Rank]['Color'] == $Color) continue;

			//2 on the "right"
			if ($Field[$File+1][$Rank]['Color'] == $Color && $Field[$File+2][$Rank]['Color'] == $Color) continue;

			//1 on each side
			if ($Field[$File-1][$Rank]['Color'] == $Color && $Field[$File+1][$Rank]['Color'] == $Color) continue;

			//place the soldier on the field and take it out of the deck
			array_push($Field[$File], array('Type'=>'Unit', 'Color'=>$Color));
			$this->Deck[$Color]--;

		}

		$this->Field = $Field;
	}

	function EligibleToBeDeleted($DeletionTest)
	{
		$this->Messages[] = 'Eligible to delete';
		return TRUE;
	}

	function DeleteItem($File, $Rank)
	{
		$this->Messages[] = 'Checking deletion of ('.$File.','.$Rank.')';

		if ($this->EligibleToBeDeleted($this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank])) {
			unset($this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]);
			
			$this->Events[] = Array(
				"Action" => "Delete",
				"Actor" => "Player",
				"Rank" => $Rank,
				"File" => $File
			);
			
			$this->Moves['Used']++;
			$this->Moves['Left']--;
			
		}
	}

	function MoveItem($Source, $Target)
	{
		print_r($this->Boards);
		
		if (!is_array($this->Boards[$this->BoardStatePlayerIndex]['State'][$Target])) {
			$this->Boards[$this->BoardStatePlayerIndex]['State'][$Target] = Array();
		}

		array_push(
			$this->Boards[$this->BoardStatePlayerIndex]['State'][$Target],
			array_pop($this->Boards[$this->BoardStatePlayerIndex]['State'][$Source]));
			
		$this->Events[] = Array(
			"Action"=>"Pickup",
			"Actor" => "Player",
			"File"=>$Source			
		);
		
		$this->Events[] = Array(
			"Action"=>"Move",
			"Actor" => "Player",
			"Source"=>$Source,
			"Target"=>$Target			
		);
		
		$this->Events[] = Array(
			"Action"=>"Drop",
			"Actor" => "Player",
			"File"=>$Target
		);
		
		$this->Moves['Used']++;
		$this->Moves['Left']--;
	}

	/**
	 * Sorts a file with the following hierarchy:
	 * 1. walls up front;
	 * 2. then charging armies;
	 * 3. followed by idlers; and lastly
	 * 4. empties
     */
	function SortField()
	{
		$this->Sorts++;

		foreach ($this->Boards[$this->BoardStatePlayerIndex]['State'] as $FileKey => &$FileArray) {

			//remove blanks
			$FileArray = array_values($FileArray);

			do {
				$SwapCount = 0;
				for ($i = count($FileArray); $i > 0; $i--) {

					$a = $this->AssignSortingScore($FileArray[$i]['Type']);
					$b = $this->AssignSortingScore($FileArray[$i-1]['Type']);

					//determine sorting
					if ($a < $b) {
						$Swap = $FileArray[$i-1];
						$FileArray[$i-1] = $FileArray[$i];
						$FileArray[$i] = $Swap;
						$SwapCount++;
						
						$this->Events[] = Array(
							"Action" => "Swap",
							"Actor" => "Game",
																			
							"File" => $FileKey,
							"Rank" => $i
						);
					}
				}
			} while ($SwapCount > 0);
		}
	}

	private function AssignSortingScore($Type)
	{
		if ($Type == 'Wall') return 0;
		if ($Type == 'Charge') return 1;
		if ($Type == 'Unit') return 2;

		return 10;
	}
	
	function SortEvents() {
		
		//$this->Messages[] = "Sorting Events";
		//$this->Messages[] = print_r($this->Events);
		
		foreach($this->Events as $EventIndex => &$Event) {
			switch ($Event['Type']) {
				case 'Charge':
					
					//find the next charge in the same file that is one higher rank than the current
					foreach($this->Events as $SearchIndex => $SearchEvent){
						if ($SearchEvent['Type'] == 'Charge' && 
							$SearchEvent['File'] == $Event['File'] && 
							$SearchEvent['Rank'] == $Event['Rank']+1) {
								
							$ErrorLevel = error_reporting();
							error_reporting(0);							
								
							$out = array_splice($this->Events, $SearchIndex, 1);
    						array_splice($this->Events, $this->Events[$EventIndex+1], 0, $out);			
							
							error_reporting($ErrorLevel);
							
							break;
						}
					}
					
										
				continue;
			}
		}
		
		ob_start();
		print_r($this->Events);
		$this->Messages[] =  ob_get_clean();
	}
	
	function DetectAndManageMatches()
	{
		$Matches = 0;

		foreach ($this->Boards[$this->BoardStatePlayerIndex]['State'] as $File=>&$FileArray)
		{
			foreach ($FileArray as $Rank=>&$RankArray)
			{
				//$this->Messages[] = 'At ('.$File.', '.$Rank.')';

				if ($this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]['Type'] == 'Unit')
				{

					//$this->Messages[] = 'File '.$File.' has '.count($FileArray).' ranks in it. If we\'re on rank '.$Rank.', then there are '.(count($FileArray) - $Rank).' ranks to check.';

					//look for attack matches
					if (count($FileArray) - $Rank >= 3)
					{

						//$this->Messages[] = 'Checking File '.$File.' at Rank '.$Rank.' for charge matches.';
						//$this->Messages[] = $this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank];
						//$this->Messages[] = $this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank+1];
						//$this->Messages[] = $this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank+2];

						if ($this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]['Color'] == $this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank+1]['Color'] &&
							$this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank+1]['Color'] == $this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank+2]['Color'] &&

							!$this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]['Attack'] &&
							!$this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank+1]['Attack'] &&
							!$this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank+2]['Attack']
							)
						{
							//attack match!
							$this->Messages[] = $this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]['Color'].' charge match at ('.$File.','.$Rank.')';
							$this->Events[] = Array(
								"Action" => "Formation",
								"Color" => $this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]['Color'],
								"Actor" => "Game",
								"File" => $File,
								"Ranks" => Array($Rank, $Rank+1, $Rank+2)
							);
							$Matches++;

							$this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]['Attack'] = TRUE;
							$this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank+1]['Attack'] = TRUE;
							$this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank+2]['Attack'] = TRUE;
						}

					}


					//look for defend matches
					$DefendMatches = 1;
					for ($f = ($File+1); $f < 8; $f++)
					{
						if ($this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]['Color'] == $this->Boards[$this->BoardStatePlayerIndex]['State'][$f][$Rank]['Color'] && isset($this->Boards[$this->BoardStatePlayerIndex]['State'][$f][$Rank]))
						{
							$DefendMatches++;
						}
						else break;
					}

					if ($DefendMatches >= 3)
					{
						$this->Messages[] = 'Wall of length '.$DefendMatches.' found at ('.$File.','.$Rank.')';

						for ($r = 0; $r < $DefendMatches; $r++)
						{
							$this->Boards[$this->BoardStatePlayerIndex]['State'][$File + $r][$Rank]['Wall'] = TRUE;
							$WallFiles[] = ($File + $r);
						}
						
						$this->Events[] = Array(
							"Action" => "Defend",
							"Actor" => "Game",
							"Rank" => $Rank,
							"Files" => $WallFiles
						);

						$Matches++;
					}

					//Identify Multimatch units
					if ($this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]['Wall'] && $this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]['Attack'])
					{
						$this->Messages[] = 'Multimatch at ('.$File.','.$Rank.')';
						$Multimatch[$File][] = $Rank;
					}

				}
			}
		}

		//separate multimatches into single matches
		if (isset($Multimatch)) foreach ($Multimatch as $File=>$MultiMatches)
		{
			$offset = 0;
			foreach ($MultiMatches as $MatchRank)
			{
				array_splice($this->Boards[$this->BoardStatePlayerIndex]['State'][$File], $MatchRank+$offset, 0, array($this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$MatchRank]));

				unset($this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$MatchRank+$offset]['Attack']);
				unset($this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$MatchRank+$offset+1]['Wall']);

				$offset++;
			}
		}


		//Change unit types for different matches
		foreach ($this->Boards[$this->BoardStatePlayerIndex]['State'] as $File=>&$FileArray)
		{
			foreach ($FileArray as $Rank=>&$RankArray)
			{

				if ($this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]['Wall'] && $this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]['Attack'])
				{
					unset($this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]['Wall']);

					//$this->Messages[] = 'Before Splice';
					//$this->Messages[] = $this->Boards[$this->BoardStatePlayerIndex]['State'][$File];

					//$this->Messages[] = 'Splicing in';
					//$this->Messages[] = $this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank];

					array_splice($this->Boards[$this->BoardStatePlayerIndex]['State'][$File], $Rank, 0, array($this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]));

					//$this->Messages[] = 'After Splice';
					//$this->Messages[] = $this->Boards[$this->BoardStatePlayerIndex]['State'][$File];

					$this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]['Type'] = 'Wall';
					unset($this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]['Color'], $this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]['Attack']);

				}
				else if ($this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]['Wall'])
				{
					unset($this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]['Wall']);
					$this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]['Type'] = 'Wall';
					unset($this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]['Color']);
				}
				else if ($this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]['Attack'])
				{
					unset($this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]['Attack']);
					$this->Boards[$this->BoardStatePlayerIndex]['State'][$File][$Rank]['Type'] = 'Charge';
				}
			}
		}

		return $Matches;

	}
	
	function GetPlayerID ($PlayerIndex) {
		return $this->Boards[$PlayerIndex]['PlayerID'];		
	}
	
	function GetCharacterPortrait($var) {
		
		switch ($var) {
			case 'player':
			$Index = $this->BoardStatePlayerIndex;
			break;
			
			case 'opponent':
			$Index = $this->BoardStateOpponentIndex;
			break;
		}
		
		switch ($this->GetPlayerID($Index)) {
			case 1:
			return 'img/portraits/sean.png';
			
			case 2:
			return 'img/portraits/underdog.png';
		}
		
	}

    function DisplayGame() {
        ?>

        <script type="text/javascript" src="game.js"></script>
        <link rel="stylesheet" href="game.css">
		
		<div id="announceContainer">
			<div id="announce">placeholder text</div>
		</div>

        <div id="gameboard">
            <div class="player player-top">
                <div class="bars"></div>
                <div class="portrait" style="background-image:url('<?php echo $this->GetCharacterPortrait('opponent') ?>')"></div>
            </div>

            <div class="playingfield playingfield-top"></div>
            <div id="centerline"></div>
            <div class="playingfield playingfield-bottom"></div>

            <div class="player player-bottom">
                <div class="bars"></div>
                <div class="portrait" style="background-image:url('<?php echo $this->GetCharacterPortrait('player') ?>')"></div>
            </div>
        </div>

        <span id="delete">X</span>
        <?php
    }
}

function GameCreateForm() {
	
	?>
	
	<div id="newgame">
		<form name="newgameform" action="index.php?do=startgame" method="POST">			
			<table>
				<tr>
					<th>Game Name</th>
					<td><input name="GameName"/></td>
				</tr>
				<tr>
					<th>Opponent</th>
					<td><select name="Opponent">
                        <?php 												
							global $LoggedInUser;
                            foreach (getAllUsers() as $UserID=>$UserName) {
                                if ($LoggedInUser->UserID != $UserID)
								echo "<option value=\"{$UserID}\">{$UserName}</option>"; 
                            }
                        ?>					
                    </select></td>
				</tr>
				<tr>
					<td colspan="2"><input type="submit" value="Start!"/>
				</tr>				
			</table>			
		</form>				
	</div>
	
	<?php
	
}

session_start();
include_once('config.php');
include_once('user.php');  

switch ($_POST['action']) {

    case "spawnarmy":

        //debug
        $DebugField = array(
            0 => array(array("Type" => "Unit", "Color" => "blue"), array("Type" => "Unit", "Color" => "blue"), array("Type" => "Unit", "Color" => "green")),
            1 => array(array("Type" => "Unit", "Color" => "blue"), array("Type" => "Unit", "Color" => "blue"), array("Type" => "Unit", "Color" => "green")),
            2 => array(array("Type" => "Unit", "Color" => "green"), array("Type" => "Unit", "Color" => "green"), array("Type" => "Unit", "Color" => "red"), array("Type" => "Unit", "Color" => "green")),
            3 => array(array("Type" => "Unit", "Color" => "blue")),
            4 => array(array("Type" => "Unit", "Color" => "red")),
            5 => array(array("Type" => "Unit", "Color" => "red")),
            6 => array(array("Type" => "Unit", "Color" => "green")),
            7 => array(array("Type" => "Unit", "Color" => "blue"))
        );

        $Game = new GameState();
        //$Game = new GameState(Array('Army'=>$DebugField));
		echo $Game->BuildCurrentState();
		$Game->RecordGameState();
        echo $Game->CurrentState;
        break;


    //TODO: move and delete are stupid-similar. Move this into a single something where they can be handled
    case "move":
	case "delete":	
        $Game = new GameState($_SESSION['GameID']);
		unset($Game->Events);
		
		if ($_POST['action'] == "move")		$Game->MoveItem($_POST['fileSource'], $_POST['fileTarget']);
		if ($_POST['action'] == "delete")	$Game->DeleteItem($_POST['file'], $_POST['rank']);
		
        do {
            $Game->SortField();
            $NumMatches = $Game->DetectAndManageMatches();
            $Game->Matches += $NumMatches;			
        } while ($NumMatches > 0);

		if ($Game->Moves['Left'] <= 0) {
			$Game->SwitchPlayers();
		}
		
		$Game->BuildCurrentState();
		$Game->RecordGameState();
        echo $Game->CurrentState;

        break; 
	
}

switch ($_GET['action']) {
	case "peek":
		
		$Game = new GameState($_SESSION['GameID']);
		
		$Updates = $Game->GetUpdatesSinceTimestamp($_GET['timestamp']);
		$Game->Messages[] = $Updates;
		
		echo JSON_encode(
				Array(
					"Response"=>$Updates > 0,					 
					"Messages"=>$Game->Messages
				)
			);			
		
			
		//print_r(get_defined_vars());			
			
		break;
		
	case "fetch":
		$Game = new GameState($_SESSION['GameID']);
		$Game->BuildCurrentState();
        echo $Game->CurrentState;
		break;
}

?>