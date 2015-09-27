<?php

function GetGameList($Users = NULL) {
	
	if (!empty($Users)) {	
		if (!is_array($Users)) $Users = array($Users);	
		$Where[] = "Player1 IN (". implode(",",$Users) .") OR Player2 IN (". implode(",",$Users) .")";
	} 
	
	$Where[] = "Winner IS NULL";
		
	global $handle, $MySQL_context;
	$sql = "SELECT 
		
			g.ID `GameID`,
			g.Name `GameName`,
			g.Player1 `Player1ID`,
			g.Player2 `Player2ID`,
			u1.Name `Player1Name`,
			u2.Name `Player2Name` 
	
			FROM {$MySQL_context}Game g
	
			LEFT JOIN {$MySQL_context}Users u1 ON g.Player1 = u1.ID
			LEFT JOIN {$MySQL_context}Users u2 ON g.Player2 = u2.ID
	
		   ".(isset($Where) ? "WHERE ". implode(" AND ", $Where) : NULL);
			
	if (!$sql_result = mysql_query($sql,$handle)) die(mysql_error($handle));
    return $sql_result;
}

function MakeGameList($GameList) {
	
	if (mysql_num_rows($GameList) > 0) {
		
		echo '<table>';
		echo '<tr>';
		echo '<th>Game ID</td>';
		echo '<th>Game Name</th>';
		echo '<th>Players</th>';
		echo '</tr>';
		
		while ($row = mysql_fetch_assoc($GameList)) {
			echo '<tr>';
			echo "<td>{$row['GameID']}</td>";
			echo "<td><a href=\"?do=playgame&GameID={$row['GameID']}\">{$row['GameName']}</a></td>";
			echo "<td>{$row['Player1Name']}, {$row['Player2Name']}";
			echo '</tr>';
		}
		echo '</table>';
	}	
}

function ShowNews() {
	
	$News[] = array("Headline"=>"Welcome to War!", "Body"=>"<p>This is the first stubbed Newspost!</p><p>You should feel special!</p><p>Exclamation Point!</p>");
	
	foreach ($News as $Item) {
		echo '<h2>'.$Item['Headline'].'</h2>';
		echo '<hr/>';
		echo $Item['Body'];
	}
}

?>