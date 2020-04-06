<!DOCTYPE HTML>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="canonical" href="https://www.lawlessmedia.com/surf/" />
		<!-- favicon info -->
		<link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
		<link rel="icon" type="image/png" href="favicon-32x32.png" sizes="32x32">
		<link rel="icon" type="image/png" href="favicon-16x16.png" sizes="16x16">
		<link rel="manifest" href="manifest.json">
		<link rel="mask-icon" href="safari-pinned-tab.svg" color="#244b83">
		<meta name="theme-color" content="#dddddd">
		<meta name="msapplication-starturl" content="/surf/index.php">
		<meta id="viewport" name="viewport" content="width=device-width, initial-scale=1" />
		<meta name="apple-mobile-web-app-capable" content="yes" />


		<title>.: Masonboro Tide / Buoy Data for <?php echo date('M d') ?> :.</title>
		<link rel="stylesheet" type="text/css" href="css/buoydata.css">
	</head>
<body class="dark-mode">
<h2>Tides <time id="date"></time></h2>
<dl id="tides">
	<p id="tidesLoading">Loading Tides...</p>
</dl>

<?php
# Define the buoys into variables to use in building the array	
	$buoy0 = '41110';
	$name0 = 'Masonboro Inlet ' . $buoy0;
    $buoy1 = '41108';
    $name1 = 'Wilmington Harbor ' . $buoy1;
    $buoy2 = '41013';
    $name2 = 'Frying Pan Shoals ' . $buoy2;
    

# build 2 arrays to hold the variables defined above
# These are used in the foreach loop below
$arrBuoy = array($buoy0, $buoy1, $buoy2);
$arrName = array($name0, $name1, $name2);

# foreach loop to cycle through each buoy in the array
foreach ($arrBuoy as $key => $stationNumber) {

# Set the GMT (Greenich Mean Time) offset for the location of the 
# buoy you are getting reports for. Pacific Standard Time is a -8 hour 
#offset from GMT; leave this unchanged if that is your desired timezone.
$gmtOffset = -5;

# 12-hour or 24-hour clock preference
# For a 12-hour clock, set $clock = 0;
# For a 24-hour clock, set $clock = 1;
$clock = 0;

# Date format choice.
# For U.S.-style dates (MM-DD-YYYY), set $intlDateFormat = 0;
# For international-style dates(DD-MM-YYYY), set $intlDateFormat = 1;
$intlDateFormat = 0;

# Choose metric or English measurements.
# For English measurements, set $metric = 0;
# For metric measurements, set $metric = 1;
$metric = 0;

# You can customize the phpBuoy output HTML table, if you want. 
# If you are using phpBuoy as an nested include file within an existing
# table-based page layout, you can probably leave this as-is.

echo "<h2>" . $arrName[$key] . "</h2>";
echo "<dl>";

#########################
# END OF CUSTOMIZATIONS #
#########################

################################################################################################
# Really no need to tweak the code from this point down, unless you want to make it better! :) #
################################################################################################

# Location of remote backend file and local cache file
# $backend = "http://www.surfimplement.com/include/46063.txt";
$backend = "https://www.ndbc.noaa.gov/data/realtime2/".$stationNumber.".txt";
$cache_file = "tmp/buoydata.".$stationNumber.".cache";

# Set timeout duration for establishing a connection
# with NDBC's servers...if they're down, we don't want 
# the script to bomb in graceless fashion. The number
# is in seconds.
$timeout = 10;

# Cache file time-based nag stuff
$cache_time = 3600; # Buoy readings are only updated every hour so cache the current reading until the next is available
$time = explode(" ", microtime());
srand((double)microtime()*1000000);
$cache_time_rnd = 300 - rand(0, 600);

$maxReadings = 3; # Takes into account the first two lines of buoy data, which are column headings like YY, DD, HH, ATMP, etc.

$numLines = 0;    # Line pointer initialization

if ( (!(file_exists($cache_file))) || ((filectime($cache_file) + $cache_time - $time[1]) + $cache_time_rnd < 0) || (!(filesize($cache_file))) ) {

	$url = parse_url($backend);

	$fp = fsockopen($url['host'], "80", $errno, $errstr, $timeout);
	
	if(!$fp) {

		echo "Buoy data currently unavailable - server appears down<br><br><br>\n";
		return;

	} else {
		// https://stackoverflow.com/questions/32820376/fopen-accept-self-signed-certificate
		$opts = array(
		    'ssl' => array(
		        'verify_peer' => false,
		        'verify_peer_name' => false,
		    ),
		);
		
		$context = stream_context_create($opts);
		
		$fpread = fopen($backend, 'rb', false, $context);
		stream_set_timeout($fpread, 5);
	
		if(!$fpread) {
		
			echo "Buoy data currently unavailable - file could not be loaded<br><br><br>\n";
			return;
		
		} else {
	
			$fpwrite = fopen($cache_file, 'w');
	
			if(!$fpwrite) {
	
				echo "Buoy data currently unavailable - could not read cache file<br><br><br>\n\n";
				return;
	
			} else {
	
		
				$line = fgets($fpread, 1024);
				
				while((!feof($fpread)) && ($numLines < $maxReadings)) {
			
					$line = preg_replace("%MM%i",0,$line);
			
					# #YY  MM DD hh mm WDIR WSPD GST  WVHT   DPD   APD MWD   PRES  ATMP  WTMP  DEWP  VIS PTDY  TIDE

					list($YY,$MM,$DD,$HH,$MIN,$WD,$WSPD,$GST,$WVHT,$DPD,$APD,$MWD,$PRES,$ATMP,$WTMP,$DEWP,$VIS,$PTDY,$TIDE,$WOOF) = preg_split("/[\s,]+/", $line);

					# Format the date to MM-DD-YYYY or DD-MM-YYYY
					if($intlDateFormat == 0) {
	
						$formattedDate = $MM."-".$DD."-".$YY;
					
					} elseif($intlDateFormat != 0) {
	
						$formattedDate = $DD."-".$MM."-".$YY;
					
					}
	
					$HH = (int)$HH + $gmtOffset; # Compensate for GMT timezone offset | cast $HH to int to prevent PHP 7 E_WARNING
					
					if($clock == 0) {
	
						if($HH < 1) {
				
							$hour = $HH + 12; # 12hour clock fix
							$ampm = "PM";
				
						} elseif($HH > 12) {
		
							$hour = $HH - 12; # 12hour clock fix
							$ampm = "PM";

						} elseif($HH == 12) {

							$hour = $HH;
							$ampm = "PM";
		
						} else {
	
							$hour = $HH;
							$ampm = "AM";
	
						}
	
					} elseif($clock != 0) {
	
						if($HH < 0) {
				
							$hour = $HH + 24; # 24hour clock fix
				
						} 
	
					}
	
					# Metric-to-English conversion stuff	
					if($metric == 0) {
	
						$windSpeed = "kts."; # "Knots" abbrev.
						$waveHeight = "ft."; # "Feet" abbrev.
						$temp = "F"; # "Fahrenheit" abbrev.

						if($WVHT != 0) {

							$WVHT = round($WVHT * 3.28, 1); # Convert wave height from feet to meters
						
						}

						if($WSPD != 0) {

							$WSPD = round($WSPD * 1.9425, 1); # Convert wind speed from m/s to knots
						
						}

						if($GST != 0) {

							$GST = round($GST * 1.9425, 1); # Convert gust speed from m/s to knots
	
						}

						if($ATMP != 0) {

							$ATMP = round(($ATMP * 1.8) + 32, 1); # Convert air temp from C to F
	
						}
						
						if($WTMP != 0) {

							$WTMP = round(($WTMP * 1.8) + 32, 1); # Convert water temp from C to F
						
						}	

					} elseif($metric != 0) {
	
						$windSpeed = "m/s"; # "Meters/Second" abbrev.
						$waveHeight = "m"; # "Meters" abbrev.
						$temp = "C"; # "Celcius" abbrev.
	
					}
	
					# Wind direction conversion stuff

					if($WD != 0) {

						if(($WD >= 348 && $WD <= 360) || ($WD >= 0 && $WD < 11)) {
		
							$WDIR = "N";
		
						} elseif($WD >= 326 && $WD < 348) {
		
							$WDIR = "NNW";
		
						} elseif($WD >= 303 && $WD < 326) {
		
							$WDIR = "NW";
		
						} elseif($WD >= 281 && $WD < 303) {
		
							$WDIR = "WNW";
		
						} elseif($WD >= 258 && $WD < 281) {
		
							$WDIR = "W";
		
						} elseif($WD >= 236 && $WD < 258) {
		
							$WDIR = "WSW";
		
						} elseif($WD >= 213 && $WD < 236) {
		
							$WDIR = "SW";
		
						} elseif($WD >= 191 && $WD < 213) {
		
							$WDIR = "SSW";
		
						} elseif($WD >= 168 && $WD < 191) {
		
							$WDIR = "S";
		
						} elseif($WD >= 146 && $WD < 168) {
		
							$WDIR = "SSE";
		
						} elseif($WD >= 123 && $WD < 146) {
		
							$WDIR = "SE";
		
						} elseif($WD >= 101 && $WD < 123) {
		
							$WDIR = "ESE";
		
						} elseif($WD >= 78 && $WD < 101) {
		
							$WDIR = "E";
		
						} elseif($WD >= 56 && $WD < 78) {
		
							$WDIR = "ENE";
		
						} elseif($WD >= 33 && $WD < 56) {
		
							$WDIR = "NE";
		
						} elseif($WD >= 11 && $WD < 33) {
		
							$WDIR = "NNE";
		
						} else {
				
							$WDIR = "No Wind Data";
		
						}

					} else {

						$WDIR = 0;

					}
			
					# Print out results of parsing
					if($numLines >= 2) {
					
					# Check to see if there is a real value, if not don't display anything for that null value field
					($WD != 0)?$WDIR = "<dt>Wind Direction:</dt><dd>$WDIR ($WD&ordm;)</dd>" : $WDIR = "";
					($WSPD != 0)?$WSPD = "<dt>Wind Speed:</dt><dd>$WSPD $windSpeed</dd>" : $WSPD = "";
					($GST  != 0)?$GST  = "<dt>Gust Speed:</dt><dd>$GST $windSpeed</dd>" : $GST = "";
					($WVHT != 0)?$WVHT  = "<dt>Wave Height:</dt><dd>$WVHT $waveHeight</dd>" : $WVHT = "";
					($DPD != 0)?$DPD  = "<dt>Swell Period:</dt><dd>$DPD sec.</dd>" : $DPD = "";
					($ATMP != 0)?$ATMP = "<dt>Air Temp:</dt><dd>$ATMP&ordm;$temp</dd>" : $ATMP = "";
					
						fputs($fpwrite, "<dt>Report Time:</dt><dd>$hour:$MIN$ampm, $formattedDate</dd>$WDIR $WSPD $GST $WVHT $DPD $ATMP <dt>Water Temp:</dt><dd>$WTMP&ordm;$temp</dd>\n");

					}
			
					$line = fgets($fpread, 1024);
					$numLines++;
		
				}
	
			}	
		
			fclose($fpread);
	
		}
	
		fclose($fpwrite);

	}

	fclose($fp);

}

if (file_exists($cache_file)) {

	include($cache_file);

}

echo "</dl>";
}
?>

  <script>
  	// START PWA code
  	
  	// Register Service Worker
	if ('serviceWorker' in navigator) {
	  window.addEventListener('load', () => {
	    navigator.serviceWorker.register('service-worker.js')
	    .then(registration => {
	      console.log('Service Worker is registered', registration);
	    })
	    .catch(err => {
	      console.error('Registration failed:', err);
	    });
	  });
	}
	
	// END PWA code
	
	// create todays date as a formatted var for use in API call date range
	var todaysDate = new Date();
	apiDate = todaysDate.toLocaleString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' })
	//console.log(apiDate); // current date in mm/dd/yyyy format for API call params

	var tideurl = 'https://tidesandcurrents.noaa.gov/api/datagetter?product=predictions&application=NOS.COOPS.TAC.WL&begin_date=' + apiDate + '&end_date=' + apiDate + '&datum=MLLW&station=8658559&time_zone=lst_ldt&units=english&interval=hilo&format=json';
	
	//var tideurl = null; // for testing error handling
	
	// fectch API call to grab tide data
	fetch(tideurl).then(function(response) {
	  return response.json();
	}).then(function(data) {
	  //console.log(data); // log the json response data
	  
	  // remove loading placeholder once .then promise executes
	  var loading = document.getElementById("tidesLoading");
	  loading.remove();
	  
	  // loop through the json data and set the variables
	  data.predictions.forEach(function (tideData) {
	  	var t = tideData.t;
	  	var v = tideData.v;
	  	var type = tideData.type;
	  	
	  	function tideTime(){
		  	// fetch Closure for getting 12H time from API time data
		  	// Add T and GMT offset to create valid UTC time format for consistent parsing across browsers
	      	var utcTime = t.replace(' ','T');
	      	utcTime = utcTime + '-05:00';
	      	
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
	
  </script>
	</body>
</html>