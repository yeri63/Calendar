<?php
    $urlPath = "http://192.168.3.250/cisco/calendar.php";

    session_start();
    header("Content-type: text/xml");

    # Used to properly align digits in calendar based on current phone model.
    function getCiscoPhoneModel() {
        return $_SERVER['HTTP_X_CISCOIPPHONEMODELNAME'] ?? 'unknown';
    }

    # Recall prior variable state and SoftKey selection.;
    $dateTime = new DateTime($_SESSION['dateTime']);
    $myMonth = $_SESSION['myMonth'];
    $myDay = $_SESSION['myDay'];
    $myYear = $_SESSION['myYear'];
    $lastCmd = $_SESSION['cmd']; # User command.

    # Get SoftKey selection and data collected.
    $cmd = $_GET['key'] + 0;
    $num = $_GET['num'] + 0;

    # Initialize the current and selected dates.
    $currTime = new DateTime(date('Y-m-d')); 

    if (!isset($lastCmd)) 
        $dateTime = new DateTime(date('Y-m-d'));

    # Set the number alignment based on phone model.
	# $model = getCiscoPhoneModel();
	$model = "CP-7911G";

    # Print a single date of the month.
    function printDay($day)
    {
        global $dateTime, $currTime; 
        global $myMonth, $myDay, $myYear;
        global $model;

        if ($model == "CP-7911G")
            $adj = "   ";
        else
            $adj = "  ";

        $month = $dateTime->format('m');
        $year = $dateTime->format('Y');

        if ($day < 10)
            printf(" %s%d\t", $adj, $day);
        else
            printf(" %d\t", $day);

        return;
    }

    # Print the days of the month.
    function printCal()
    {
        global $dateTime, $currTime, $model;

        # Get the information needed to display the current month.
        $month = $dateTime->format('m');
        $year = $dateTime->format('Y');
        $firstWeekday = date('w', strtotime("$year-$month"));
        $lastDay = $dateTime->format('t');

        # Adjust indent to match phone display model to center text.
		if ($model == "CP-7911G")
            $indent = "\t ";
        else
            $indent = "\t \t \t ";

        $day = 0;

        printf("%sSu\t Mo\t Tu\t We\t Th\t Fr\t Sa\n%s", $indent, $indent);
    
        while (TRUE) {
            for ($weekday = 0; $weekday < 7; $weekday++) {
          
                # Print nothing until we reach the 1st weekday.
                if (($day == 0) && ($weekday < $firstWeekday))
                    print "  \t\t";
                else
                    printDay(++$day);

                # Exit once the last day was reached.
                if ($day == $lastDay)
                    break 2;
            }
            printf("\n%s", $indent);
        }
    }

    # Process user input and update the selected calendar.
    switch($lastCmd) {
    case 3 : # date (month)
        $myMonth = $num;
        $cmd = 4;
        break;

    case 4 : # date (year)
        $myYear = $num;
        $dateTime = new DateTime("$myYear-$myMonth-1");
        $cmd = 0;
        break;
    }

    # Prompt the user for information based on the key pressed.	
    switch($cmd) {
    case 1 : # previous
        $dateTime = $dateTime->modify('previous month');
        break;
        
    case 2 : # next
        $dateTime = $dateTime->modify('next month');
        break;

    case 3 : # date (month)
        $units = "Month";
        $mesg = "Enter the calendar month.";
        break;

    case 4 : # date (year)
        $units = "Year";
        $mesg = "Enter the calendar year.";
        break;
    }
    
    # Update IP Phone display.
    switch($cmd) {
    case 0 :
    case 1 :
    case 2 :
        echo "<CiscoIPPhoneText>";
        echo "  <Title>Calendar - ".$dateTime->format('F Y')."</Title>";
        echo "  <Text>";
        printCal();
        echo "  </Text>";

        echo "  <SoftKeyItem>";
        echo "	<Name>Prev</Name>";
        echo "	<URL>$urlPath?key=1</URL>";
        echo "	<Position>1</Position>";
        echo "  </SoftKeyItem>";
            
        echo "  <SoftKeyItem>";
        echo "    <Name>Next</Name>";
        echo "	<URL>$urlPath?key=2</URL>";
        echo "    <Position>2</Position>";
        echo "  </SoftKeyItem>";
            
        echo "  <SoftKeyItem>";
        echo "    <Name>Date</Name>";
        echo "	<URL>$urlPath?key=3</URL>";
        echo "    <Position>3</Position>";
        echo "  </SoftKeyItem>";

        echo "  <SoftKeyItem>";
        echo "    <Name>Exit</Name>";
        echo "    <URL>SoftKey:Exit</URL>\n";
        echo "    <Position>4</Position>";
        echo "  </SoftKeyItem>";

        echo "  <Prompt>".$mesg."</Prompt>";
        echo "</CiscoIPPhoneText>"; 

        break;

    case 3 : 
    case 4 :
        echo "<CiscoIPPhoneInput>";
        echo "  <Title>Calendar - ".$dateTime->format('F Y')."</Title>";
        echo "  <URL method=\"post\">$urlPath</URL>";

        echo "  <InputItem>";
        echo "    <DisplayName>$units</DisplayName>";
        echo "    <QueryStringParam>num</QueryStringParam>";
        echo "    <DefaultValue> </DefaultValue>";
        echo "    <InputFlags>N</InputFlags>";
        echo "  </InputItem>";

        echo "  <SoftKeyItem>";
        echo "	<Name>Update</Name>";
        echo "	<URL>SoftKey:Submit</URL>";
        echo "	<Position>2</Position>";
        echo "  </SoftKeyItem>";

        echo "  <SoftKeyItem>";
        echo "    <Name>Cancel</Name>";
        echo "    <URL>$urlPath?key=0</URL>";
        echo "    <Position>4</Position>";
        echo "  </SoftKeyItem>";
 
        echo "  <Prompt>$mesg</Prompt>";
        echo "</CiscoIPPhoneInput>";

        break;
    }

    # Save current variable state.
    $_SESSION['dateTime'] = $dateTime->format('Y-m-d');
    $_SESSION['myMonth'] = $myMonth;
    $_SESSION['myYear'] = $myYear;
    $_SESSION['num'] = $num;
    $_SESSION['cmd'] = $cmd;
?>

