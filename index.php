<!--
Purpose
- Show earning for all devices and all algos from Nicehash platform
- Data show in table
- You'll find what is profitable to mine (or not)


How to 
- Upload this single file to your PHP web server
- Open your web browser and open the page
- Put your local current rate to BTC at $btc_rate


Note
- check_array($array) is a very helpful, sample below
- Filter algo and device also available, scroll down and see the comments
- You can use power data from devices to calculate electric cost
- Nicehash's rates is realtime, it's may not same as show on Nichhash's page


Thanks Nicehash for build a great platform to sell hashpower

Created by Rawee C.
-->
<style>
body {
	font-family: 'roboto';
	color:#efefef;
	background-color:#333;
}
body, td, th {
	font-size:18px;
}
table {
	border:solid 2px #555;
	border-collapse: collapse;
}
tr:nth-child(odd) {
	background: #424242;
}
a {
	color:#efefef;
}
</style>
<?php
// your currency rate to BTC
$btc_rate = 700000; // default is my local currency "Thai Baht"


// select data to show in table
$use_device_filter = false; // filter by the list below
$use_algo_filter = false; // filter by the list below


// filter the data to show in table
$filter_devices = array(
	'nvidia-gtx-1060-6gb',
	'nvidia-gtx-1070',
	'nvidia-gtx-1080-ti',
	'nvidia-rtx-3070',
	'nvidia-rtx-3080',
	'nvidia-rtx-3090-ti'
	);


// filter the data to show in table
$filter_algos = array(
	'DAGGERHASHIMOTO',
	'BEAMV3',
	'KAWPOW',
	'GRINCUCKATOO32',
	'ZHASH',
	'GRINCUCKATOO31',
	'RANDOMXMONERO'
	);

	
// prepare nh_algos
	$url = 'https://api2.nicehash.com/main/api/v2/mining/algorithms/';
	$array = json_decode(file_get_contents($url), true);
	// remap array key with algo_id (order)
	foreach ($array['miningAlgorithms'] as $k => $v):
		$nh_algos[$v['order']] = $v;
		$nh_algos_name_to_id[$v['algorithm']] = $v['order'];
	endforeach;
	// check_array($nh_algos_name_to_id);
	// check_array($nh_algos);
	/*
	(
		[algorithm] => SCRYPT
		[title] => Scrypt
		[enabled] => 1
		[order] => 0
		[displayMiningFactor] => MH
		[miningFactor] => 1000000.00000000
		[displayMarketFactor] => TH
		[marketFactor] => 1000000000000.00000000
		[minimalOrderAmount] => 0.00100000
		[minSpeedLimit] => 0.01000000
		[maxSpeedLimit] => 10000.00000000
		[priceDownStep] => -0.00100000
		[minimalPoolDifficulty] => 500000.00000000
		[port] => 3333
		[color] => #AFAFAF
		[ordersEnabled] => 1
		[enabledMarkets] => EU, USA
		[displayPriceFactor] => TH
		[priceFactor] => 1000000000000.00000000
	)
	*/
	
	
// prepare nh_rates, return as algo_id
	$url = 'https://api2.nicehash.com/main/api/v2/public/stats/global/current/';
	$array = json_decode(file_get_contents($url), true);
	foreach ($array['algos'] as $k => $v):
		$nh_rates[$v['a']] = array(
			'algo' => $v['a'],
			'price' => $v['p'],
			'speed' => $v['s'],
			'rigs' => $v['r'],
			'orders' => $v['o'],
		);
	endforeach;
	// check_array($nh_rates);
	/*
	(
	    [algo] => 46
        [price] => 2.6089463541174E-6
        [speed] => 1258117373.0229
        [rigs] => 270
        [orders] => 4
	)
	*/


// get online nh device data
	$url = 'https://api2.nicehash.com/main/api/v2/public/profcalc/devices/';
	$json = file_get_contents($url);
	file_put_contents('tmp_nh_profit_device.json', $json);
	$data = json_decode($json, true);
	
	// remap array by key
	foreach ($data['devices'] as $k => $v):
		$nh_profit_device[$v['niceName']] = $v;
		//$group_device_by_category[$v['category']][] = $v['niceName'];
	endforeach;
	// check_array($group_device_by_category);
	// check_array($nh_profit_device);


// prepare the device info
foreach ($nh_profit_device as $k => $device):
		// extract hashrate speed json to array
		$speeds_in_array = json_decode($device['speeds'], true);
		
		foreach ($speeds_in_array as $algo => $speed):
			// only active algo and speed > 0
			if (isset($nh_algos_name_to_id[$algo]) && $speed > 0):
				// convert algo_name to algo_id
				$algo_id = $nh_algos_name_to_id[$algo];
				
				// cal the earning
				$nh_profit_device[$k]['earn'][$algo_id] = array(
					'algo' => $algo,
					'speed' => $speed,
					'cal' => calc_earning($algo_id, $speed)
				);
				
				$nh_profit_device[$k]['earn'][$algo_id]['cal']['earning_thb'] = $nh_profit_device[$k]['earn'][$algo_id]['cal']['earning'] * $btc_rate;

			endif;
		endforeach;
endforeach;

// check_array($nh_profit_device);


// select what to display
if ($use_device_filter):
	$display_devices = $filter_devices;
else:
	$display_devices = array_keys($nh_profit_device);
endif;


// select what to display
if ($use_algo_filter):
	$display_algos = $filter_algos;
else:
	$display_algos = array_keys($nh_algos_name_to_id);
endif;


echo '<table>';
echo '<tr>';
echo '<th></th>';
foreach ($display_algos as $algo_name):
	echo '<th>'.$algo_name.'</th>';
endforeach;
echo '</tr>';

foreach ($display_devices as $device_name):
	$device = $nh_profit_device[$device_name];
	echo '<tr>';
	echo '<td>'.$device_name.'</td>';
	
	foreach ($display_algos as $algo_name):
		$algo_id = $nh_algos_name_to_id[$algo_name];
		if (isset($device['earn'][$algo_id])):
			$p = $device['earn'][$algo_id]['cal']['earning_thb'];
			$d = number_format($p, 2);
		else:
			$d = '';
		endif;
		echo '<td style="border-left:solid 1px #ccc; text-align:right;">'.$d.'</td>';
	endforeach;
	
	echo '</tr>';
endforeach;
echo '</table>';


// you can uncomment this. But first, let it show you all data
check_array($nh_profit_device);


// ----- function zone ----- //

function calc_earning($algo_id, $hashrate) {
	/*
		***** cut from $nh_algos single data for easy ref
		[algorithm] => SCRYPT
		[order] => 0
		[displayMiningFactor] => MH
		[miningFactor] => 1000000.00000000
		[displayMarketFactor] => TH
		[marketFactor] => 1000000000000.00000000
		[displayPriceFactor] => TH
		[priceFactor] => 1000000000000.00000000
		
		***** cut from $nh_rates single data for easy ref
		[algo] => 46
		[price] => 2.6089463541174E-6
		[speed] => 1258117373.0229
		[rigs] => 270
		[orders] => 4
	*/

	global $nh_rates;
	global $nh_algos;
	
	$price = $nh_rates[$algo_id]['price'];
	$algo = $nh_algos[$algo_id];
	
	$data['nh_rate'] = ($price / 100000000); // convert price to satoshi
	$data['nh_rate_human'] = number_format($data['nh_rate'], 16);
	$data['nh_rate_text'] = number_format($data['nh_rate'] * $algo['marketFactor'], 8).' BTC/'.$algo['displayMarketFactor'].'/Day';
	
	// multiple with gpu hashrate
	$data['earning'] = $hashrate * $data['nh_rate'] * $algo['miningFactor'];
	$data['earning_human'] = number_format($data['earning'], 16);
	$data['earning_text'] = number_format($data['earning'], 8).' BTC/Day';
	
	return $data;
}


function check_array($array) {
	echo '<pre>';
	print_r($array);
	echo '</pre>';
}
?>
