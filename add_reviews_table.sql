-- Create reviews table if not exists
CREATE TABLE IF NOT EXISTS `danhgia` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `MADG` varchar(10) NOT NULL,
  `MASP` varchar(10) NOT NULL,
  `MAKH` varchar(10) NOT NULL,
  `MAHD` varchar(20) NOT NULL,
  `SOSAODANHGIA` int(11) DEFAULT NULL CHECK (`SOSAODANHGIA` between 1 and 5),
  `NOIDUNG` varchar(500) DEFAULT NULL,
  `THOIGIAN` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `idx_danhgia_masp` (`MASP`),
  KEY `idx_danhgia_makh` (`MAKH`),
  KEY `idx_danhgia_mahd` (`MAHD`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
