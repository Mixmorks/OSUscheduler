<?php
/****************
OSU schedule Generator
Author: Jan Ulrich Bartels
Date: 06/21/2015

Class Definitions
****************/
class section_s{
	public $DEP = "";
	public $NUM = "";
	public $CRN;
	public $CREDITS = 0;
	public $INSTRUCTOR;
	public $DAY_N_TIME = array(0,0,0,0,0); //Implemented as a 5*32 bit array. 1 bit for every half hour Mon through Fri
	public $LABS = array();
	public $LAB;
	
	/**************
	Time to bit representation
	Comment: This is probably the most hack part of the entire thing. The database will return a start and endtime i.e. "11:00:00" and "11:50:00"
	this functions job is to turn that string into an int, with every bit representing half an hour starting at 8:00am.
	A class going for example from 11:00 to 13:40 would thus look like: 00000000000000000000111111000000
	**************/
	function convert_to_time_block($TIME){
		$arr = explode(":", $TIME['START']);
		$start_hours = ((int) $arr[0])-8;
		$start_minutes = (int) $arr[1];
		
		$arr = explode(":", $TIME['END']);
		$end_hours = ((int) $arr[0])-8;
		$end_minutes = (int) $arr[1];
		if($end_minutes > 30){
			$end_hours++;
		}
		
		$val = 0;
		
		/*********
		WTF is happening here?
		Here is an example:
		Left  side: 00000000000000000000000001111111 &
		Right side: 11111111111111111111111111111000 (with inversion)
		Result    : 00000000000000000000000001111000 (block of time this class is taking up).
		*********/
		$val = (pow(2,$end_hours*2)-1) & ~(pow(2,$start_hours*2)-1);
		if($start_minutes > 30){ //For half hours we need to tack on or take off a bit on either end. If a class goes into the half hour (i.e. 12:40) I consider that half hour taken.
			$val = $val & ~(1 << $start_hours*2);
		}
		if($end_minutes < 30 and $end_minutes > 0){
			$val = $val | (1 << $end_hours*2);
		}
		
		return $val;
	}
	
	/***********
	Updates every day with the binary time representation if that class happens that day.
	Note: This only supports classes with the same time throughout the week. This should hold true for most but I will look into changing this should it become an issue.
	***********/
	function fill_day_n_time($DAYS, $time){
		if(strpos($DAYS, 'M') !== FALSE){ $this->DAY_N_TIME[0] = $time; }
		if(strpos($DAYS, 'T') !== FALSE){ $this->DAY_N_TIME[1] = $time; }
		if(strpos($DAYS, 'W') !== FALSE){ $this->DAY_N_TIME[2] = $time; }
		if(strpos($DAYS, 'R') !== FALSE){ $this->DAY_N_TIME[3] = $time; }
		if(strpos($DAYS, 'F') !== FALSE){ $this->DAY_N_TIME[4] = $time; }
	}
	
	function __construct($DEP, $NUM, $CRN, $CREDITS, $INSTRUCTOR, $DAYS, $TIMES, $LAB){
	   	$this->DEP = $DEP;
		$this->NUM = $NUM;
		$this->CRN = $CRN;
		$this->CREDITS = $CREDITS;
		$this->INSTRUCTOR = $INSTRUCTOR;
		//TODO: Some classes don't have times and instead have the string 'TBA' in them. This would break the convert_to_time_block function. Need to implement check.
		$time = $this->convert_to_time_block($TIMES);
		$this->fill_day_n_time($DAYS, $time);
		$this->LAB = $LAB;
	}
	
	function __destruct(){
		unset($DAY_N_TIME);
		unset($LABS);
	}
}

 class course{
	public $DEP;
	public $NUM;
	public $SECTIONS = array();
	
	function __construct($DEP, $NUM){
		$this->DEP = $DEP;
		$this->NUM = $NUM;
	}
	
	function add_section($DEP, $NUM, $CRN, $CREDITS, $INSTRUCTOR, $DAYS, $TIMES, $LAB){
		array_push($this->SECTIONS, new section_s($DEP, $NUM, $CRN, $CREDITS, $INSTRUCTOR, $DAYS, $TIMES, $LAB));
	}
	
	function add_lab($DEP, $NUM, $CRN, $CREDITS, $INSTRUCTOR, $DAYS, $TIMES, $LAB){
		foreach($this->SECTIONS as $SECTION) //These are to ensure labs are pushed to the correct class. This will not work if one teacher teaches multiple sections with different labs.
			if($SECTION->INSTRUCTOR == $INSTRUCTOR)
				array_push($SECTION->LABS, new section_s($DEP, $NUM, $CRN, $CREDITS, $INSTRUCTOR, $DAYS, $TIMES, $LAB));
	}
	
	function __destruct(){
		unset($SECTIONS);
	}
}

class schedule_s{
	public $CLASSES_IN_SCHEDULE = array();
	public $DAY_N_TIME = array(0,0,0,0,0); //Implemented as a 5*32 bit array. 1 bit for every half hour Mon through Fri
	public $CREDITS = 0;
	
	function __destruct(){
		unset($CLASSES_IN_SCHEDULE);
		unset($DAY_N_TIME);
	}
}

/***************
This function uses bit level operations to check if two time blocks clash
Example:
Class1: 00000000000000000000011111100000 &
Class2: 00000000000000000000000001111000
Result: 00000000000000000000000001100000 TIME CONFLICT!
***************/
function time_conflict($class, $DAY_N_TIME){
	if($class->LABS == NULL){
		for($x = 0; $x < 5; $x++){
			if($class->DAY_N_TIME[$x] & $DAY_N_TIME[$x])
				return 1;
		}
		return 0;
	}else{
		foreach($class->LABS as $LAB)
			for($x = 0; $x < 5; $x++)
				if(!($class->DAY_N_TIME[$x] & $DAY_N_TIME[$x] & $LAB->DAY_N_TIME[$x]))
					return 0;
		return 1;
	}
}

/**************
Uses bit level operations to merges a classes times with the schedules times.
Example:
Schedule: Day[0] = 00000000000000000001111000000
Class   : Day[0] = 00000000000111100000000000000
Result  : Day[0] = 00000000000111100001111000000
**************/
function merge_times($DAY_N_TIME1, $DAY_N_TIME2){
	$DAY_N_TIME_RESULT = array(0,0,0,0,0);
	for($x = 0; $x < 5; $x++)
		$DAY_N_TIME_RESULT[$x] = $DAY_N_TIME1[$x] | $DAY_N_TIME2[$x];
	
	return $DAY_N_TIME_RESULT;
}

/************
Prints the schedule as a table by going through each half hour, and checking which class in the schedule has a claim to that half hour.
If one does, then print class name. If no class has that half hour print an empty slot.
************/
function print_schedules($schedules){
	$num_schedule = 1;
	foreach($schedules as $schedule){
		echo "<br>This is schedule Nr. $num_schedule<br>";
		echo "<table style='width:100%'>";
		echo "<tr><td></td><td>Monday</td><td>Tuesday</td><td>Wednesday</td><td>Thursday</td><td>Friday</td></tr>";
		for($half_hour = 0; $half_hour < 32; $half_hour++){ //There's 32 bits in an int, each representing half an hour of the day.
			$hour = 8 + round($half_hour/2, 0, PHP_ROUND_HALF_DOWN);
			$half = ($half_hour % 2) ? '30' : '00'; 
			echo "<tr>";
			echo "<td>$hour:$half</td>"; //Will print 8:00 - 8:30 - 9:00 - 9:30 etc...
			for($day = 0; $day < 5; $day++){
				$NO_CLASS_THIS_HOUR = 1; //This is to check if any class occupies that particular spot.
				foreach($schedule->CLASSES_IN_SCHEDULE as $class){
					/*************
					Did I mention that this uses a lot of bit manipulation?
					Example:
					Class  : 000000000000000000000111110000000 &
					1 << 10: 000000000000000000000100000000000 (10 is just an example, this will happen for every shift from 0 to 31
					Result : 000000000000000000000100000000000 CLASS OCCUPIES THIS SPOT
					*************/
					if($class->DAY_N_TIME[$day] & (1 << $half_hour)){
						$NO_CLASS_THIS_HOUR = 0;
						echo "<td>$class->DEP $class->NUM</td>";
					}
				}
				if($NO_CLASS_THIS_HOUR){ //If no class uses this spot just print empty slot.
					echo "<td></td>";
				}
			}
		echo "</tr>";
		}
		echo "</table>";
		echo "The classes in this schedule are:<br>";
		foreach($schedule->CLASSES_IN_SCHEDULE as $class){ //Once the table is printed we print which classes we used to make it.
			echo "$class->DEP $class->NUM $class->CRN";
			if($class->LAB){
				echo " Lab Section";
			}else{
				echo " $class->INSTRUCTOR";
			}
			echo " Credits: $class->CREDITS";
			echo "<br>";
		}
		echo "End Schedule Nr.$num_schedule<br>";
		$num_schedule++;
	}
}

/**************
Picks a random, available class and section and checks if a class with that Department and number has already been added.
This is to avoid adding multiple sections of the same class (even if there is no time conflict).
**************/
function pick_class($schedule, $available_classes){
	srand();
	$CLASS_IN_SCHEDULE = 1;
	while($CLASS_IN_SCHEDULE){
		$CLASS_IN_SCHEDULE = 0;
		$class = $available_classes[rand(0,count($available_classes)-1)]->SECTIONS[rand(0,count($SECTIONS)-1)];

		if(count($schedule->CLASSES_IN_SCHEDULE) == 0){
			return $class;
		}
		foreach($schedule->CLASSES_IN_SCHEDULE as $sched_class){
			if($sched_class->DEP == $class->DEP)
				if($sched_class->NUM == $class->NUM){
					$CLASS_IN_SCHEDULE = 1;
				}
		}
	}
	return $class;
}
/****************
 * Schedule Generation
 * Plan:
 * Pick a random class & section.
 * See if that random class's schedule clashes with the proposed schedule.
 * If not, see if any of the class' labs don't clash with the proposed schedule + proposed class.
 * If both conditions are met, push class to schedule and set values in DAY_N_TIME, if applicable push a random working lab too. 
 ***************/
 function generate_schedules(&$possible_schedules, $available_classes, $num_credits){
    srand();
	if($available_classes != NULL){
		echo "We currently have (x) schedules generated: ";
		while(count($possible_schedules) < 4){ //Determines the number of schedules we want to generate. TODO: Let user decide.
			$schedule = new schedule_s;
			$var = count($possible_schedules);
			echo "$var ";
	       
			while($schedule->CREDITS < $num_credits){	//Determines the number of credits we want each schedule to have.
				$class = pick_class($schedule, $available_classes); //Picks a random class & section not already in the schedule
				$var = time_conflict($class, $schedule->DAY_N_TIME);
				if(time_conflict($class, $schedule->DAY_N_TIME)){
					//Deliberately left empty.
				} else {
					if($class->LABS == NULL){ //If there's no time conflict and the class doesn't have any labs we just add it.
						array_push($schedule->CLASSES_IN_SCHEDULE, $class);
						$schedule->CREDITS += $class->CREDITS;
						$schedule->DAY_N_TIME = merge_times($schedule->DAY_N_TIME, $class->DAY_N_TIME);
					}else{ //If it does have Labs we first need to see which ones don't generate time conflicts, then push a class and a valid lab.
						$temp_DAY_N_TIME = merge_times($schedule->DAY_N_TIME, $class->DAY_N_TIME);
						$available_labs = array();
						foreach($class->LABS as $LAB){ //This loop checks each lab against the schedule and keeps all labs that don't generate a time conflict.
							if(time_conflict($LAB, $temp_DAY_N_TIME)){
								//Deliberately left empty.
							} else {
								array_push($available_labs, $LAB);
							}
						}
						$random_lab = $available_labs[rand(0,count($available_labs)-1)]; //Pick a random, non-clashing lab.
						array_push($schedule->CLASSES_IN_SCHEDULE, $class);
						array_push($schedule->CLASSES_IN_SCHEDULE, $random_lab);
						$schedule->CREDITS += $class->CREDITS + $random_lab->CREDITS;
						$schedule->DAY_N_TIME = merge_times($schedule->DAY_N_TIME, merge_times($class->DAY_N_TIME, $random_lab->DAY_N_TIME));
					}
				}
			}
			array_push($possible_schedules, $schedule);
		}
	} else {
		echo "No classes matching your query were found.<br>";
		echo "i.e. ZZZ 999 is a valid query but there is no class with that moniker.<br>";
	}
 }
 
 
 /***************
 Simple adder to check how many credits we can possibly hope to add to the schedule.
 Because the scheduler will try adding classes until it reaches the desired number of credits we would get an infinite loop if we ran out of stuff to add before we met that criteria.
  **************/
 function credits_in_queue($class_queue){
	$credits_available = 0;
	foreach($class_queue as $class){
		$credits_available += $class->SECTIONS[0]->CREDITS;
	}
	return $credits_available;
 }
 
/****************
 * Database Login
 * Comment: Straightforward stuff, though using the outdated mysql functions, should migrate to MySQLi.
 ***************/
function login(&$mysql_handler){
	$dbhost = 'oniddb.cws.oregonstate.edu';
	$dbname = 'bartelja-db';
	$dbuser = 'bartelja-db';
	//Thanks for your interest. Since I don't want everyone and their dog to see just what happens with DROP TABLE I will send you the password upon request.
	//Shoot me an email. ONID is bartelja or write me on Facebook.
	$dbpass = 'THISISNOTTHEPASSWORDREADTHECOMMENT'; 
	
	$mysql_handle = mysql_connect($dbhost, $dbuser, $dbpass)
		or die("Error connecting to database server - could it be that the password is incorrect?");

	mysql_select_db($dbname, $mysql_handle)
		or die("Error selecting database: $dbname");

}

/*****************
 * main( ) - Program starts here
 ****************/
$mysql_handler; 		//Connection to our database.
login($mysql_handler);	//Logging in.


$user_input = $_GET['classes']; //"ECE 375, SUS 212, ECON 101, DROP TABLE--"

$user_credits = $_GET['credits'];
$num_credits = 14;
preg_match('/[1-9][0-9]*/', $user_credits, $match); //Will match 405 in 0405, will not match 0 or 09.
if(!empty($match[0])){ //If the user did enter a valid number we'll use that instead of the default.
	$num_credits  = $match[0];
}

$class_queue = array(); //This array will hold all the classes that we'll use to build the schedule.
if(preg_match_all('/[A-Z]{1,5} \d{3}(H|NC)?/', $user_input, $valid_classes)){ //Will match H 113NC, ECON 112, MTH 241H etc
	echo 'I found these valid classnames within your query: <br>';
	foreach($valid_classes[0] as $arr){ //"ECE 375", "SUS 212", "ECON 101"
		
		$name_and_number = explode(" ", $arr); //[0] = ECE, [1] = 375
		
		//Fetching database entries -- All entry is piped through Regex so I'm not concerned about SQL injections.
		$sqlquery = mysql_query("SELECT * FROM OSUclasses WHERE DEP = '$name_and_number[0]' AND NUM = '$name_and_number[1]' AND TERM = 'F15'");  //Regex will filter out anything evil.
		//Creating new course class
		if(mysql_num_rows($sqlquery) > 0){ //Checks if our query returned anything.
			echo "$arr<br>"; //Print class that we found a the valid entry for.
			$class_queue[] = new course($name_and_number[0], (int) $name_and_number[1]);
			while($row = mysql_fetch_assoc($sqlquery)){ //Go through each row that SQL found
				if($row['LAB'] == NULL or $row['LAB'] == 0){ //If it's not a lab section throw 
					$class_queue[count($class_queue)-1]->add_section($row['DEP'], $row['NUM'], (int)$row['CRN'], (int)$row['CREDITS'], $row['INSTRUCTOR'], $row['DAYS'], array('START' => $row['TIME1'], 'END' => $row['TIME2']), (bool)$row['LAB']);
				} else {
					$class_queue[count($class_queue)-1]->add_lab($row['DEP'], $row['NUM'], (int)$row['CRN'], (int)$row['CREDITS'], $row['INSTRUCTOR'], $row['DAYS'], array('START' => $row['TIME1'], 'END' => $row['TIME2']), (bool)$row['LAB']);
				}		
			}
		} else {
			echo "I could not find an entry for $arr<br>";
		}
	}
	echo "<br>";
	$available_credits = credits_in_queue($class_queue);
	if($available_credits < $num_credits){ //This is to avoid an infinite loop if the number of credits available is less than the number of credits desired.
		echo "I could only find $available_credits amount of credits worth of classes.<br>";
		echo "I will try building a schedule with $available_credits credits.<br>";
		$num_credits = $available_credits;
	}
	$possible_schedules = array();
	generate_schedules($possible_schedules, $class_queue, $num_credits);
	print_schedules($possible_schedules);
	
} else {
	echo "I'm sorry, your search did not contain a entry (example of a valid entry: MTH 101, ECE 201, ECON 101";
}

mysql_close($mysql_handler);

?>