--
-- テーブルの構造 `LoRaWAN_module_config`
--

CREATE TABLE `LoRaWAN_module_config` (
  `config_id` int(11) NOT NULL,
  `config_module_DevAdde` varchar(10) NOT NULL,
  `config_Temperature` float NOT NULL,
  `config_Humidity` float NOT NULL,
  `config_Co2` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
