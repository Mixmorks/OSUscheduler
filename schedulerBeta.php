<HTML>
<HEAD>
<TITLE>OSU Schedule Generator</TITLE>
</HEAD>
<BODY>
<?php
echo phpversion();
?>

<body>
	<h1>This is the experimental/debugging Instance of the OSU scheduler. Expect things to be broken.</h1>
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
	The GitHub repo does not have the Password. Shoot me a message and I'll send it to you.<br>
	If someone drops the table shoot me a message on ONID or Facebook and I'll re-upload.<br>
	Should that happen too often I'll think of another approach.<br>
	
	<div>
		Known issues:<br>
		If you manage to break it, let me know. ONID is bartelja. <br>
	</div>
</body>

</BODY>
</HTML>