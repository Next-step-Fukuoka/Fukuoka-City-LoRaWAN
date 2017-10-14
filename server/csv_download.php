<?php
 //DB接続情報
 $dsn = 'mysql:host=localhost;dbname=fukuokad_fukuokacity;charset=utf8';
 $id = 'xxxxx';
 $pw = 'xxxxx';
 
 //画面からパラメータ取得
 $LoRa_DevAdde  = filter_input(INPUT_POST, "LoRa_DevAdde");
 $start_daytime = filter_input(INPUT_POST, "start_daytime");
 $start_daytime = $start_daytime." 00:00:00";
 $end_daytime   = filter_input(INPUT_POST, "end_daytime");
 $end_daytime   = $end_daytime." 23:59:59";

 $dev_id = $_REQUEST["dev_id"] ;
 if (isset($_POST["dlbtn"])) {
   try {
     //DB検索処理
     $pdo = new PDO($dsn, $id, $pw,
              array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
     $sql = "SELECT * FROM LoRaWAN WHERE LoRa_DevAdde  = :LoRa_DevAdde and 	LoRa_write_datetime >= :start_daytime and 	LoRa_write_datetime <= :end_daytime order by LoRa_id ";
     $stmt = $pdo->prepare($sql);
     $stmt->bindParam(':LoRa_DevAdde', $LoRa_DevAdde, PDO::PARAM_STR);
     $stmt->bindParam(':start_daytime', $start_daytime, PDO::PARAM_STR);
     $stmt->bindParam(':end_daytime' , $end_daytime, PDO::PARAM_STR);
     $stmt->execute();
 
     //CSV文字列生成・出力
     $fileNm = filter_input(INPUT_POST, "LoRa_DevAdde")."_".filter_input(INPUT_POST, "start_daytime")."_".filter_input(INPUT_POST, "end_daytime").".csv";
     header('Content-Type: text/csv');
     header('Content-Disposition: attachment; filename='.$fileNm);
     $csvstr ="No,Temperature,Humidity,CO2,Timestamp\r\n";
     echo $csvstr;
     $cnt = 0 ;
     while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
       $cnt++;
       $csvstr = "";
       $csvstr .= $cnt . ",";
       $csvstr .= $row['LoRa_Temperature'] . ",";
       $csvstr .= $row['LoRa_Humidity'] . ",";
       $csvstr .= $row['LoRa_co2'] . ",";
       $csvstr .= $row['LoRa_Time'] . "\r\n";
       echo $csvstr;
     }
     exit();
 
   }catch(ErrorException $ex){
     print('ErrorException:' . $ex->getMessage());
   }catch(PDOException $ex){
     print('PDOException:' . $ex->getMessage());
   }
 }
?>
<html lang="ja">
  <head>
    <meta charset="utf-8">
    <meta name="ROBOTS" content="NOINDEX,NOFOLLOW">
    <title>CSVダウンロード</title>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <link rel="stylesheet" href="/resources/demos/style.css">
  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  
	<script>
	$( function() {
			from = $( "#from" )
				.datepicker({
					defaultDate: "+1w",
					changeMonth: true,
					numberOfMonths: 1
				})
				.on( "change", function() {
					to.datepicker( "option", "minDate", getDate( this ) );
				}),
			to = $( "#to" ).datepicker({
				defaultDate: "+1w",
				changeMonth: true,
				numberOfMonths: 1
			})
			.on( "change", function() {
				from.datepicker( "option", "maxDate", getDate( this ) );
			});

		function getDate( element ) {
			var date;
			try {
				date = $.datepicker.parseDate( dateFormat, element.value );
			} catch( error ) {
				date = null;
			}

			return date;
		}
	  $("#from").datepicker("option", "dateFormat", "yy-mm-dd" );
      $("#to").datepicker("option", "dateFormat", "yy-mm-dd" );

	} );
	</script>
  
    <script>
    $( function() {
    $("#datepicker").datepicker();
    $("#datepicker").datepicker("option", "dateFormat", "yy-mm-dd" );

    } );
    </script>
  </head>
  <body>
    <div align="center">
    <H2>CSVデータダウンロード</h2>
    <form action="./csv_download.php" method="post">
                <input type ="hidden" name="LoRa_DevAdde" value="<? echo "$dev_id" ; ?>"  /><br>
      開始日時：<input type ="text" name="start_daytime" id="from" /><br>
      終了日時：<input type ="text" name="end_daytime" id="to" /><br>
      
      <input type="submit" name="dlbtn" value="ダウンロード" />
    </form>
    <br><br><br>
    <H2>データ補正</h2>
    <form action="">
                <input type ="hidden" name="LoRa_DevAdde" value="<? echo "$dev_id" ; ?>"  /><br>
      温度：<input type ="text" name="temp" /><br>
      湿度：<input type ="text" name="Humi" /><br>
      二酸化炭素：<input type ="text" name="co2" /><br>
      <input type="submit" name="dlbtn" value="設定" />
    </form>
	<font color="#ff0000" size="-1">※この機能は管理権限でのみ機能します</font>
    </div>
  </body>
</html>
