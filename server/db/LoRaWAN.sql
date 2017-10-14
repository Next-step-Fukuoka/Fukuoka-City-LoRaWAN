--
-- テーブルの構造 `LoRaWAN`
--

CREATE TABLE `LoRaWAN` (
  `LoRa_id` bigint(20) UNSIGNED NOT NULL,
  `LoRa_DevAdde` varchar(10) NOT NULL,
  `LoRa_Time` varchar(30) NOT NULL,
  `LoRa_write_datetime` datetime NOT NULL,
  `LoRa_payload_hex` varchar(100) NOT NULL,
  `LoRa_Temperature` float NOT NULL,
  `LoRa_Humidity` float NOT NULL,
  `LoRa_co2` int(11) NOT NULL,
  `LoRa_Temperature_raw` float NOT NULL,
  `LoRa_Humidity_raw` float NOT NULL,
  `LoRa_co2_raw` int(11) NOT NULL,
  `LoRa_Temperature_difference` float NOT NULL,
  `LoRa_Humidity_difference` float NOT NULL,
  `LoRa_co2_difference` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
