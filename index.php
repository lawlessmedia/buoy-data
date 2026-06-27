<!DOCTYPE HTML>
<html lang="en" dir="ltr">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="description" content="Local Tide And Buoy Data for Wilmington, NC area">
		<link rel="dns-prefetch" href="https://tidesandcurrents.noaa.gov/" >
		<link rel="canonical" href="https://www.lawlessmedia.com/surf/" />
		<link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
		<link rel="icon" type="image/png" href="favicon-32x32.png" sizes="32x32">
		<link rel="icon" type="image/png" href="favicon-16x16.png" sizes="16x16">
		<link rel="manifest" href="manifest.json">
		<link rel="mask-icon" href="safari-pinned-tab.svg" color="#244b83">
		<meta name="theme-color" content="#dddddd">
		<meta name="msapplication-starturl" content="/surf/index.php">
		<meta id="viewport" name="viewport" content="width=device-width, initial-scale=1" />
		<meta name="mobile-web-app-capable" content="yes" />
		<title>.: Masonboro Tide / Buoy Data for <?php echo date('M d') ?> :.</title>
		<link rel="stylesheet" type="text/css" href="css/buoydata.css">
	</head>
<body class="dark-mode">
<h2>Tides <time id="date"></time></h2>
<dl id="tides">
	<p id="tidesLoading">Loading Tides...</p>
</dl>

<?php
# Define the buoys and their names in a single associative array
$buoys = [
    '41110' => 'Masonboro Inlet 41110',
    '41108' => 'Wilmington Harbor 41108',
    '41013' => 'Frying Pan Shoals 41013'
];

# Preferences
$gmtOffset 			= -5;		# Timezone offset
$clock 					= 0;		# 0 = 12-hour, 1 = 24-hour
$intlDateFormat = 0;		# 0 = MM-DD-YYYY, 1 = DD-MM-YYYY
$metric 				= 0;		# 0 = English, 1 = Metric
$cache_time 		= 3600;	# Cache expiration in seconds (3600 = 1 hour)
$timeout 				= 10;		# Timeout duration for fetching data

# Ensure the cache directory exists
$cache_dir = "tmp";
if (!is_dir($cache_dir)) {
    mkdir($cache_dir, 0755, true);
}

foreach ($buoys as $stationNumber => $stationName) {

    echo "<h2>{$stationName}</h2>\n<dl>\n";

    $buoyurl = "https://www.ndbc.noaa.gov/data/realtime2/{$stationNumber}.txt";
    $cache_file = "{$cache_dir}/buoydata.{$stationNumber}.cache";

    # Determine if we need to fetch new data
    $cache_is_valid = file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time);

    # If cache is missing or expired, try to update it
    if (!$cache_is_valid) {
        
        $context = stream_context_create([
            'http' => ['timeout' => $timeout],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false]
        ]);

        # Fetch data suppressing warnings if server is down
        $raw_data = @file_get_contents($buoyurl, false, $context);

        if ($raw_data) {
            $lines = explode("\n", trim($raw_data));
            
            # Ensure we have enough lines (Line 0: headers, Line 1: units, Line 2: latest reading)
            if (isset($lines[2])) {
                
                # NDBC uses "MM" for missing data; replace with "0"
                $line = str_ireplace("MM", "0", $lines[2]);
                
                # Split the line by spaces into variables
                list($YY, $MM, $DD, $HH, $MIN, $WD, $WSPD, $GST, $WVHT, $DPD, $APD, $MWD, $PRES, $ATMP, $WTMP, $DEWP, $VIS, $PTDY, $TIDE) = preg_split("/[\s]+/", $line);

                # Date Formatting
                $formattedDate = ($intlDateFormat == 0) ? "{$MM}-{$DD}-{$YY}" : "{$DD}-{$MM}-{$YY}";

                # Time Formatting
                $HH = (int)$HH + $gmtOffset;
                
                if ($clock == 0) { // 12-hour clock
                    if ($HH < 1) { $hour = $HH + 12; $ampm = "PM"; }
                    elseif ($HH > 12) { $hour = $HH - 12; $ampm = "PM"; }
                    elseif ($HH == 12) { $hour = $HH; $ampm = "PM"; }
                    else { $hour = $HH; $ampm = "AM"; }
                } else { // 24-hour clock
                    $hour = ($HH < 0) ? $HH + 24 : $HH;
                    $ampm = "";
                }

                # Unit Conversions
                if ($metric == 0) {
                    $windSpeedUnit = "kts.";
                    $waveHeightUnit = "ft.";
                    $tempUnit = "F";

                    if ($WVHT != 0) $WVHT = round($WVHT * 3.28084, 1); # Convert wave height from feet to meters
                    if ($WSPD != 0) $WSPD = round($WSPD * 1.94384, 1); # Convert wind speed from m/s to knots
                    if ($GST != 0)  $GST = round($GST * 1.94384, 1); # Convert gust speed from m/s to knots
                    if ($ATMP != 0) $ATMP = round(($ATMP * 1.8) + 32, 1); # Convert air temp from C to F
                    if ($WTMP != 0) $WTMP = round(($WTMP * 1.8) + 32, 1); # Convert water temp from C to F
                } else {
                    $windSpeedUnit = "m/s";
                    $waveHeightUnit = "m";
                    $tempUnit = "C";
                }

                # Wind Direction Calculation
                # Convert wind direction in degrees to cardinal wind direction
                if ($WD != 0) {
                    $compass = ["N","NNE","NE","ENE","E","ESE","SE","SSE","S","SSW","SW","WSW","W","WNW","NW","NNW","N"];
                    $WDIR_text = $compass[round($WD / 22.5) % 16];
                } else {
                    $WDIR_text = "No Wind Data";
                }

                # Build Output HTML
                $html = "<dt>Report Time:</dt><dd>{$hour}:{$MIN}{$ampm}, {$formattedDate}</dd>\n";
                
                if ($WD != 0)   $html .= "<dt>Wind Direction:</dt><dd>{$WDIR_text} ({$WD}&ordm;)</dd>\n";
                if ($WSPD != 0) $html .= "<dt>Wind Speed:</dt><dd>{$WSPD} {$windSpeedUnit}</dd>\n";
                if ($GST != 0)  $html .= "<dt>Gust Speed:</dt><dd>{$GST} {$windSpeedUnit}</dd>\n";
                if ($WVHT != 0) $html .= "<dt>Wave Height:</dt><dd>{$WVHT} {$waveHeightUnit}</dd>\n";
                if ($DPD != 0)  $html .= "<dt>Swell Period:</dt><dd>{$DPD} sec.</dd>\n";
                if ($ATMP != 0) $html .= "<dt>Air Temp:</dt><dd>{$ATMP}&ordm;{$tempUnit}</dd>\n";
                
                $html .= "<dt>Water Temp:</dt><dd>{$WTMP}&ordm;{$tempUnit}</dd>\n";

                # Save atomically with an exclusive lock to prevent partial reads
                file_put_contents($cache_file, $html, LOCK_EX);
            }
        }
    }

    # Display the cache file if it exists (whether it's freshly downloaded or stale)
    if (file_exists($cache_file)) {
        echo file_get_contents($cache_file);
    } else {
        echo "<dt>Status:</dt><dd>Buoy data currently unavailable.</dd>\n";
    }

    echo "</dl>\n";
}
?>

  <script>
  // START PWA code
  	
  	// Register Service Worker
		const registerServiceWorker = async () => {
		  if ('serviceWorker' in navigator) {
		    try {
		      const registration = await navigator.serviceWorker.register('service-worker.js', {
		        scope: '/surf/',
		      });
		      if (registration.installing) {
		        console.log('Service worker installing');
		      } else if (registration.waiting) {
		        console.log('Service worker installed');
		      } else if (registration.active) {
		        console.log('Service worker active');
		      }
		    } catch (error) {
		      console.error('Registration failed:', error);
		    }
		  }
		};

		registerServiceWorker();
	
	// END PWA code
	
	// create todays date as a formatted const for use in API call date range
	//const todaysDate = new Date();
	//apiDate = todaysDate.toLocaleString('en-CA', { year: 'numeric', month: '2-digit', day: '2-digit' });
	//console.log(apiDate); // current date in yyyy-mm-dd format for API call params

	//const tideurl = 'https://api.tidesandcurrents.noaa.gov/api/prod/datagetter?product=predictions&application=NOS.COOPS.TAC.WL&begin_date=' + apiDate + '&end_date=' + apiDate + '&datum=MLLW&station=8658559&time_zone=lst_ldt&units=english&interval=hilo&format=json';
	
	// Simplified date attribute by using date=today
	const tideurl = 'https://api.tidesandcurrents.noaa.gov/api/prod/datagetter?product=predictions&application=NOS.COOPS.TAC.WL&date=today&datum=MLLW&station=8658559&time_zone=lst_ldt&units=english&interval=hilo&format=json';
	
	//const tideurl = null; // for testing error handling
	
	getTideData(tideurl);
	
	function getTideData(tideurl) {
	
		// fectch API call to grab tide data
		fetch(tideurl).then(function(response) {
		  return response.json();
		}).then(function(data) {
		  //console.log(data); // log the json response data
		  console.log('Network tide data received.')
		  
		  // remove loading placeholder once .then promise executes
		  var loading = document.getElementById("tidesLoading");
		  loading.remove();

		  // Get Local Timezone Offset - returns UTC offset in seconds
	      const currentDate = new Date();
	      utcTimeOffset = currentDate.getTimezoneOffset();
	      var utcHourOffset = utcTimeOffset/60;
	      var usableOffset = '-0' + utcHourOffset + ':00';
	      // End Local Timezone Offset calculation... now to add it to the utcTime
		  
		  // loop through the json data and set the variables
		  data.predictions.forEach(function (tideData) {
		  	var t = tideData.t;
		  	var v = tideData.v;
		  	var type = tideData.type;
		  	
		  	function tideTime(){
			  	// fetch Closure for getting 12H time from API time data
			  	// Add T and GMT offset to create valid UTC time format for consistent parsing across browsers
		      	var utcTime = t.replace(' ','T');

		      	//utcTime = utcTime + '-04:00'; // Hardcoded Time offset which caused it to be off by 1 hour during DST
		      	utcTime = utcTime + usableOffset;
		      	
		      	// convert the ISO datetime into just 12 hour time display
		      	// this is a bit convoluted to normalize API response to valid Date format by converting it back to unix timestamp and then use toLocaleString to expand it back into only 12 hour formatted time
		      	var unixTimeZero = +Date.parse(utcTime);
		      	var tideTime = new Date(unixTimeZero);
		
		      	var time = tideTime.toLocaleString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
		      	return time;
		  	}
		  	
		  	var time = tideTime();
	
	      	// round the tide height to 1 decimal place, needs to be converted into a number to apply toFixed
	      	var tideHeight = parseFloat(v);
	      	var tideHeightRounded = tideHeight.toFixed(1); // converts it to a string just FYI
	      	
	      	// Figure out if it's High or Low tide and apply formatting and readable text
	        type=="L" ? tide = '<dt>Low:</dt>' : tide = '<dt class=\"hightide\">High:</dt>';
			var tide = tide + '<dd>' + time + ' <span class="tideheight">(' + tideHeightRounded + ')</span></dd>';
			
			// append the formatted tide data to the tides list
			var tidesList = document.getElementById("tides");
			tidesList.innerHTML += tide;
		  });
		  
		}).catch(function(err) {
		  console.log('Fetch problem: ' + err.message);
		  // todo: add some human readable error messaging
		  
		  var tidesLoading = document.getElementById("tidesLoading");
		  var errorContent = document.createTextNode("Error Loading Tide Data");
		  tidesLoading.innerHTML = '';
		  tidesLoading.appendChild(errorContent);
		});
		
		// date formatting to show current date info
		function getDate() {
		  
		  var date = new Date();
		  
		  var weekday = new Array(7);
		  weekday[0] = "Sunday";
		  weekday[1] = "Monday";
		  weekday[2] = "Tuesday";
		  weekday[3] = "Wednesday";
		  weekday[4] = "Thursday";
		  weekday[5] = "Friday";
		  weekday[6] = "Saturday";
		
		  function nth(d) {
	      if(d>3 && d<21) return 'th'; // thanks kennebec
		      switch (d % 10) {
			  	case 1:  return "st";
				case 2:  return "nd";
		        case 3:  return "rd";
		        default: return "th";
		      }
			}
		  
		  var day = weekday[date.getDay()];
		  var date = date.getDate();
		  var ordinalDate = date + nth(date);
		  document.getElementById("date").innerHTML = '-' + ' ' + day + ' ' + ordinalDate;
		}
		
		getDate();
	
	}
	
  </script>
	</body>
</html>