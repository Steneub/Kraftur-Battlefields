<?php
class User
{
    function __construct($UserID = NULL) {		
		if (!empty($UserID)) {
			
			$this->UserID = $UserID;
			$this->UserInfo = $this->GetUserInfo($this->UserID);
			
			$this->Name = $this->UserInfo['Name']; 
		}
    }
	
	function VerifyUser($Username, $Password) {
		
		$Verify = $this->GetUser($Username, $Password);
		
		if ($Verify === FALSE) {
    		$this->Messages[] = "Invalid Username / Password";
			$this->Username = NULL;
    	}
    	else {			
			$_SESSION['UserID'] = $Verify; 
			$this->Username = $Username;			
    	}
	}
	
	function GetUser($Username, $Password) {
		
		global $handle, $MySQL_context;
    	$sql = "SELECT `ID` FROM {$MySQL_context}Users WHERE ID={$Username}";		
	
    	if (!$sql_result = mysql_query($sql,$handle)) die(mysql_error($handle));
		
		if (mysql_num_rows($sql_result) != 1) return FALSE;
		
		$row = mysql_fetch_assoc($sql_result);
		return $row['ID'];
	}
	
	public static function GetUserInfo($UserID) {
		global $handle, $MySQL_context;
    	$sql = "SELECT `ID`, `Name` FROM {$MySQL_context}Users WHERE ID={$UserID}";
		if (!$sql_result = mysql_query($sql,$handle)) die(mysql_error($handle));
		return mysql_fetch_assoc($sql_result);				
						
	}
}

function getAllUsers() {
	
	global $handle, $MySQL_context;
    $sql = "SELECT `ID`, `Name` FROM {$MySQL_context}Users";
	
    if (!$sql_result = mysql_query($sql,$handle)) die(mysql_error($handle));
	
	while ($row = mysql_fetch_assoc($sql_result)) {
		$Result[$row['ID']] = $row['Name'];
	}
	
	return $Result;	
}
?>