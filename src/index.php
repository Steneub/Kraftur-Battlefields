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
    ?>

    <div id="Menu">

        <ul>
            <li><a href="?do=startgame">Start Game</a></li>
        </ul>

    </div>
    <div id="Content"><?php

        include_once('user.php');

        switch ($_GET['do'])
        {
            case "playgame":
                include_once('game.php');
                $Game = new GameState($_GET['GameID']);
                $Game->InitializeGameState();
                $Game->DisplayGame();
                break;

            case "startgame":
                include_once('game.php');
                $Game = new GameState();
                $_GET['redirect'] = 'index.php?do=playgame$AMP;GameID='.$Game->GameID;
                break;

            case "login":
                $User = new User($_POST['username'], $_POST['password']);
                $_SESSION['User'] = $User;
                break;

            default:
                ?>

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
            <?php
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