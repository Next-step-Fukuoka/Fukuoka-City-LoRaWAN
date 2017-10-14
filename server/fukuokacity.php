<?
// エラー出力する場合(デバッグ時)
error_reporting(E_ALL);
ini_set('display_errors', '1');


// 振れ幅の上限設定
$Temperature_width_limit = 1.5;
$Humidity_width_limit    = 5.0;
$Co2_width_limit         = 100;

// jsonデータの取得とパース
$json = file_get_contents('php://input');
$object = json_decode($json, true);

// シークレットキー使っている場合の参考(今回は使っていない)
if (!empty($secret)) {
    $hash = hash_hmac('sha1', $json, $secret);
    if ($hash != $_SERVER["HTTP_X_SAKURA_SIGNATURE"]) {

        $fp = fopen("data/data.txt", "a");
        fwrite($fp, "Hase err !");
        fwrite($fp, "\n");
        fclose($fp);
        exit;
    }
}

$LoRa_DevAddr     = $object['DevEUI_uplink']['DevAddr'];
$LoRa_Time        = $object['DevEUI_uplink']['Time'];
$LoRa_payload_hex = $object['DevEUI_uplink']['payload_hex'];
// End of jsonデータの取得とパース


// log file 書き込み
ob_start(); // バッファリング開始
var_dump( $object );
$str = ob_get_contents(); // バッファリング取得
ob_end_clean(); // バッファリング終了

$fp = fopen("data/data.txt", "a");
fwrite($fp, $json);
fwrite($fp, "\n");
fwrite($fp, "DevAdde : ".$LoRa_DevAddr);
fwrite($fp, "\n");
fwrite($fp, "Time : ".$LoRa_Time);
fwrite($fp, "\n");
fwrite($fp, "payload_hex : ".$LoRa_payload_hex);
fwrite($fp, "\n");
fwrite($fp, $str);
fwrite($fp, "\n\n");
fclose($fp);
// End of log file 書き込み


// MySQL データ 書き込み


$dsn = 'mysql:host=localhost;dbname=fukuokad_fukuokacity;charset=utf8';
try {
$dbh = new PDO($dsn,'xxxxx','xxxxx',
array(PDO::ATTR_EMULATE_PREPARES => false));
} catch (PDOException $e) {
 exit('データベース接続失敗。'.$e->getMessage());
}

// 補正値取得
$stmt = $dbh->query("select * from LoRaWAN_module_config where config_module_DevAdde = '$LoRa_DevAddr' ");
$result = $stmt->fetch();

$Temperature_correction = $result['config_Temperature'];
$Humidity_correction    = $result['config_Humidity'];
$co2_correction         = $result['config_Co2'];

// 前回値取得
$stmt = $dbh->query("select * from LoRaWAN where LoRa_DevAdde = '".$LoRa_DevAddr."' order by LoRa_id desc limit 0, 1");
$result = $stmt->fetch();

$Temperature_befor      = $result['LoRa_Temperature'];
$Humidity_befor         = $result['LoRa_Humidity'];
$co2_befor              = $result['LoRa_co2'];

// 前回差分取得
$Temperature_difference = $result['LoRa_Temperature_difference'];
$Humidity_difference    = $result['LoRa_Humidity_difference'];
$co2_difference         = $result['LoRa_co2_difference'];

$temp_str = hex2bin($LoRa_payload_hex);

$LoRa_Temperature_raw = intval(substr($temp_str,0,4))/100 ;
$LoRa_Humidity_raw    = intval(substr($temp_str,4,4))/100;
$LoRa_co2_raw         = intval(substr($temp_str,8,4));

// データ補正
$LoRa_Temperature = $LoRa_Temperature_raw + $Temperature_correction ;
$LoRa_Humidity    = $LoRa_Humidity_raw    + $Humidity_correction ;
$LoRa_co2         = $LoRa_co2_raw         + $co2_correction ;

// 湿度上限ガード
if($LoRa_Humidity > 100.0){
	$LoRa_Humidity = 100;
}
// CO2下限ガード
if($LoRa_co2 < 350){
	$LoRa_co2 = 350;
}

// 振れ幅による補正
/*
if(abs($LoRa_Temperature - $Temperature_befor) > $Temperature_width_limit) {
	if($LoRa_Temperature > $Temperature_befor){
		$LoRa_Temperature = $Temperature_befor + abs($Temperature_difference) ;
	} else {
		$LoRa_Temperature = $Temperature_befor - abs($Temperature_difference) ;
	}
}
if(abs($LoRa_Humidity - $Humidity_befor) > $Humidity_width_limit) {
	if($LoRa_Humidity > $Humidity_befor){
		$LoRa_Humidity = $Humidity_befor + abs($Humidity_difference) ;
	} else {
		$LoRa_Humidity = $Humidity_befor - abs($Humidity_difference) ;
	}
}
if(abs($LoRa_co2 - $co2_befor) > $Co2_width_limit) {
	if($LoRa_co2 > $co2_befor){
		$LoRa_co2 = $co2_befor + abs($co2_difference) ;
	} else {
		$LoRa_co2 = $co2_befor - abs($co2_difference) ;
	}
}
*/
$abs_temp = abs($LoRa_Temperature - $Temperature_befor) ;
if($abs_temp > $Temperature_width_limit) {
	if($LoRa_Temperature > $Temperature_befor){
		$LoRa_Temperature = $Temperature_befor + $abs_temp / 2 ;
	} else {
		$LoRa_Temperature = $Temperature_befor - $abs_temp / 2 ;
	}
}
$abs_Humidity = abs($LoRa_Humidity - $Humidity_befor);
if( $abs_Humidity > $Humidity_width_limit) {
	if($LoRa_Humidity > $Humidity_befor){
		$LoRa_Humidity = $Humidity_befor + $abs_Humidity / 2 ;
	} else {
		$LoRa_Humidity = $Humidity_befor - $abs_Humidity / 2 ;
	}
}
$abs_co2 = abs($LoRa_co2 - $co2_befor) ;
if($abs_co2 > $Co2_width_limit) {
	if($LoRa_co2 > $co2_befor){
		$LoRa_co2 = $co2_befor + $abs_co2 / 2 ;
	} else {
		$LoRa_co2 = $co2_befor - $abs_co2 / 2 ;
	}
}



$LoRa_Temperature_difference = $LoRa_Temperature - $Temperature_befor ;
$LoRa_Humidity_difference    = $LoRa_Humidity    - $Humidity_befor ;
$LoRa_co2_difference         = $LoRa_co2         - $co2_befor ;


$str_1 = strval($LoRa_Temperature);
$str_2 = strval($LoRa_Humidity);
$str_3 = strval($LoRa_co2);


$stmt = $dbh -> prepare("INSERT INTO LoRaWAN ".
                        "(LoRa_DevAdde, LoRa_Time, LoRa_write_datetime, LoRa_payload_hex, LoRa_Temperature, LoRa_Humidity, LoRa_co2, LoRa_Temperature_raw, LoRa_Humidity_raw, LoRa_co2_raw, LoRa_Temperature_difference, LoRa_Humidity_difference, LoRa_co2_difference)".
                        " VALUES ".
                        "(:LoRa_DevAdde, :LoRa_Time, now(), :LoRa_payload_hex, :LoRa_Temperature, :LoRa_Humidity, :LoRa_co2, :LoRa_Temperature_raw, :LoRa_Humidity_raw, :LoRa_co2_raw, :LoRa_Temperature_difference, :LoRa_Humidity_difference, :LoRa_co2_difference)");
$stmt->bindParam(':LoRa_DevAdde',                $LoRa_DevAddr, PDO::PARAM_STR);
$stmt->bindParam(':LoRa_Time',                   $LoRa_Time, PDO::PARAM_STR);
$stmt->bindParam(':LoRa_payload_hex',            $LoRa_payload_hex, PDO::PARAM_STR);
$stmt->bindParam(':LoRa_Temperature',            $LoRa_Temperature, PDO::PARAM_STR);
$stmt->bindParam(':LoRa_Humidity',               $LoRa_Humidity, PDO::PARAM_STR);
$stmt->bindParam(':LoRa_co2',                    $LoRa_co2, PDO::PARAM_STR);
$stmt->bindParam(':LoRa_Temperature_raw',        $LoRa_Temperature_raw, PDO::PARAM_STR);
$stmt->bindParam(':LoRa_Humidity_raw',           $LoRa_Humidity_raw, PDO::PARAM_STR);
$stmt->bindParam(':LoRa_co2_raw',                $LoRa_co2_raw, PDO::PARAM_STR);
$stmt->bindParam(':LoRa_Temperature_difference', $LoRa_Temperature_difference, PDO::PARAM_STR);
$stmt->bindParam(':LoRa_Humidity_difference',    $LoRa_Humidity_difference, PDO::PARAM_STR);
$stmt->bindParam(':LoRa_co2_difference',         $LoRa_co2_difference, PDO::PARAM_STR);

$stmt->execute();
// End Of MySQL データ 書き込み

// Thingspark へのデータプッシュ

if(strcmp($LoRa_DevAddr, "76FFFF02") == 0){
   $base_url = 'https://api.thingspeak.com/update?api_key=xxxxxxxxxxxxxxxxxxxxxxx='.$str_1.'&field2='.$str_2.'&field3='.$str_3;
} elseif(strcmp($LoRa_DevAddr, "76FFFF01") == 0) {
   $base_url = 'https://api.thingspeak.com/update?api_key=xxxxxxxxxxxxxxxxxxxxxxx='.$str_1.'&field2='.$str_2.'&field3='.$str_3;
} elseif(strcmp($LoRa_DevAddr, "76FFFF03") == 0) {
   $base_url = 'https://api.thingspeak.com/update?api_key=xxxxxxxxxxxxxxxxxxxxxxx='.$str_1.'&field2='.$str_2.'&field3='.$str_3;
}

$tag = 'PHP';

$curl = curl_init();

curl_setopt($curl, CURLOPT_URL, $base_url);
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
//curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 証明書の検証を行わない
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);  // curl_execの結果を文字列で返す


$response = curl_exec($curl);
curl_close($curl);

// End of Thingspark へのデータプッシュ

echo	"END<br>\n";	// 正常終了時のブラウザへの表示
?>
