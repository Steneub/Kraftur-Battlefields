<?php session_start() ?> 

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Kraftur Battlefield</title>

    <link rel="stylesheet" href="http://code.jquery.com/ui/1.11.3/themes/smoothness/jquery-ui.css">
    <!-- <link rel="stylesheet" href="/resources/demos/style.css"> -->
    <link rel="stylesheet" href="style.css">

    <script type="text/javascript" src="jquery-1.11.1.min.js"></script>
    <script type="text/javascript" src="jquery-ui.min.js"></script>
    <script type="text/javascript" src="index.js"></script>

</head>
<body>

    <?php        
        include_once('config.php');
        include_once('user.php');        
    ?>

    <div id="Menu">

        <ul>
            <li><a href="index.php">Home</a></li>

            <?php             
                if (isset($LoggedInUser)) {
                    echo '<li><a href="?do=newgame">New Game</a></li>';
                    echo '<li><a href="?do=gamelist">My Games</a></li>';
                    echo "<li>Hi, {$LoggedInUser->UserInfo['Name']}! Your user ID is {$LoggedInUser->UserID} (<a href=\"?do=logout\">logout</a>)</li>";
                }
                else {
                    echo '<li><a href="?do=loginform">Login</a></li>';
                }               
            ?>        
            
        </ul>

    </div>
    <div id="Content"><?php

        switch ($_GET['do'])
        {
            case "newgame":
                include_once('game.php');
                GameCreateForm();     
                break;    
                
            case "gamelist":
                include_once('site.php');
                $GameList = GetGameList($LoggedInUser->UserID);
                MakeGameList($GameList);                   
                break;
            
            case "joingame":
                include_once('game.php');
                $_SESSION['GameID'] = $_GET['GameID'];
                //TODO: Is there supposed to be a break here?
            
            case "playgame":
                include_once('game.php');
                $Game = new GameState($_GET['GameID']);
                $Game->InitializeGameState();
                $Game->DisplayGame();
                break;

            case "startgame":
                include_once('game.php');
                $Game = new GameState(array('GameName'=>$_POST['GameName'], 'Players'=>array($LoggedInUser->UserID, $_POST['Opponent'])));
                $_GET['redirect'] = 'index.php?do=playgame$AMP;GameID='.$Game->GameID;
                break;

            case "login":
                include_once('user.php');
                $LoggedInUser = new User();
                $LoggedInUser->VerifyUser($_POST['Username'], $_POST['password']);
                             
                if (empty($LoggedInUser->Username)) {
                    unset($LoggedInUser, $_SESSION['UserID']);
                    echo '<pre>';
                    print_r($LoggedInUser->Messages);
                    echo '</pre>';
                }
                
                $_GET['redirect'] = "index.php";                
                break;
                
            case "logout":
                session_unset();
                $_GET['redirect'] = "index.php";
                break;
                
            case "loginform":
                ?>
                <div id="login">
                    <form name="loginform" action="index.php?do=login" method="POST">
                        <select name="Username">
                            <?php 
                                foreach (getAllUsers() as $UserID=>$UserName) {
                                    echo "<option value=\"{$UserID}\">{$UserName}</option>"; 
                                }
                            ?>
                        </select>
                        <input type="hidden" name="Password" value="qwerty"/>
                        <input type="submit" value="Login"/>    
                    </form>                    
                </div>
                
<!--                
                <div id="login">
                    <ul>
                        <li><a href="#tabs-1">Login</a></li>
                        <li><a href="#tabs-2">Register</a></li>
                    </ul>

                    <div id="tabs-1">
                        <form name="loginform" action="index.php?action=login" method="POST">
                            <table>
                                <tr>
                                    <td>Username</td>
                                    <td><input type="text" name="username"/></td>
                                </tr>
                                <tr>
                                    <td>Password</td>
                                    <td><input type="password" name="password"/></td>
                                </tr>
                                <tr>
                                    <td colspan="2"><input type="submit"/></td>
                                </tr>
                            </table>
                        </form>
                    </div>

                    <div id="tabs-2">
                        <form name="registerform" action="index.php?action=register" method="POST">
                            <table>
                                <tr>
                                    <td>Username</td>
                                    <td><input type="text" name="username"/></td>
                                </tr>
                                <tr>
                                    <td>Password</td>
                                    <td><input type="password" name="password[]"/></td>
                                </tr>
                                <tr>
                                    <td>Password Again</td>
                                    <td><input type="password" name="password[]"/></td>
                                </tr>
                                <tr>
                                    <td>Email</td>
                                    <td><input type="text" name="email"/></td>
                                </tr>
                                <tr>
                                    <td colspan="2"><input type="submit"/></td>
                                </tr>
                            </table>
                        </form>
                    </div>
                </div>
-->            

                <?php
                break;

            default:
                include_once('site.php');
                ShowNews();
                break;
        }

        if (isset($_GET['redirect'])) : ?>
            <script type="text/javascript">
                location.href = "<?php echo str_replace('$AMP;','&',$_GET['redirect']) ?>";
            </script>
        <?php endif ?>

    </div>
    <div id="Footer">Kraftur Battlefield</div>

</body>
</html>