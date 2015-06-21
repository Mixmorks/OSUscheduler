<HTML>
<HEAD>
<TITLE>OSU Schedule Generator</TITLE>
</HEAD>
<BODY>
<?php
echo phpversion();
?>

<body>
	<h3>This is the experimental/debugging Instance of the OSU scheduler. Expect things to be broken.</h3>
    <div id="classes">
      <h3>Which classes do you need scheduled?</h3>
      <form action="buildscheduleBeta.php" method="GET">
        Class: <input type="text" name="classes"><br>
		Number Credits you want: <input type="num" name="credits"> (Default is 14)<br>
	      <input type="submit" name="submit" value="Fetch Schedule"><br>
      </form>
	</div>
	
	<div>
		Hi, thank you for trying out the OSU schedule generator.<br>
		Just fill in classes you'd like to take and this script will generate a few random schedules for you.<br>
		Example: 'CS 161, MTH 251, ENGR 201, ECON 201'<br><br>
		Planned additions are:<br>
		Number of Schedules you want generated<br>
		Distinguishing between classes you want and need to take<br><br>
	</div>
	
	<h2>Important Note</h2>
	The GitHub repo has all access data to the database. I believe this is necessary so people can try out their modifications but it's a huge security vulnerability.<br>
	If the script can't find a class that you're SURE exists it might be that some jokester just dropped the table.<br>
	In that case shoot me a message on FB or mail and I will re-upload the data. Should this happen too often I will change the database password and try to figure out something better.<br>
	
	<div>
		Known issues:<br>
		If you manage to break it, let me know. ONID is bartelja. <br>
	</div>
</body>

</BODY>
</HTML>