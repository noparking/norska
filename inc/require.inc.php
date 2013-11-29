<?php
/* Norska -- Copyright (C) No Parking 2013 - 2013 */

date_default_timezone_set("Europe/Paris");

require dirname(__FILE__)."/utils.inc.php";

require dirname(__FILE__)."/bot.inc.php";
require dirname(__FILE__)."/project_config.inc.php";
require dirname(__FILE__)."/integration.inc.php";
require dirname(__FILE__)."/norska.inc.php";

require dirname(__FILE__)."/projects.inc.php";

require dirname(__FILE__)."/database_mysql.inc.php";

require dirname(__FILE__)."/repository_svn.inc.php";
require dirname(__FILE__)."/repository_git.inc.php";

require dirname(__FILE__)."/hooks.inc.php";

require dirname(__FILE__)."/../lang/fr_FR.lang.php";
