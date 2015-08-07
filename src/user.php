<?php
class User
{
	    function __construct(string $Username, string $Password) {

	    	if (!VerifyUser($Username, $Password)) {
	    		$this->Messages[] = "Invalid Username / Password";
	    		return FALSE;
	    	}
	    	else {
				$this->Username = $Username;
	    	}


	    }
}
?>