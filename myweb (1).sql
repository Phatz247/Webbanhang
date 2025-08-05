-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th8 05, 2025 lúc 08:44 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `myweb`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitietctkm`
--

CREATE TABLE `chitietctkm` (
  `MASP` varchar(10) NOT NULL,
  `MACTKM` varchar(10) NOT NULL,
  `gia_khuyenmai` int(11) DEFAULT NULL,
  `giam_phantram` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `chitietctkm`
--

INSERT INTO `chitietctkm` (`MASP`, `MACTKM`, `gia_khuyenmai`, `giam_phantram`) VALUES
('SP005A', 'CT001', NULL, 10);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitietdonhang`
--

CREATE TABLE `chitietdonhang` (
  `MADONHANG` varchar(20) NOT NULL,
  `MASP` varchar(10) NOT NULL,
  `KICHTHUOC` varchar(10) NOT NULL,
  `SOLUONG` int(11) NOT NULL,
  `GIA` decimal(10,2) NOT NULL,
  `THANHTIEN` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `chitietdonhang`
--

INSERT INTO `chitietdonhang` (`MADONHANG`, `MASP`, `KICHTHUOC`, `SOLUONG`, `GIA`, `THANHTIEN`) VALUES
('DH20250803100241180', 'SP002A31', '31', 1, 200000.00, 200000.00),
('DH20250803112034119', 'SP002A32', '32', 1, 200000.00, 200000.00),
('DH20250803113308824', 'SP001AL', 'L', 1, 150000.00, 150000.00),
('DH20250803123830912', 'SP002A31', '31', 1, 200000.00, 200000.00),
('DH20250803152248626', 'SP006AXL', 'XL', 1, 300000.00, 300000.00),
('DH20250803153750523', 'SP006AXL', 'XL', 1, 300000.00, 300000.00),
('DH20250803154900174', 'SP006AXL', 'XL', 1, 300000.00, 300000.00),
('DH20250804183243490', 'SP001AM', 'M', 1, 150000.00, 150000.00),
('DH20250804185042639', 'SP001AM', 'M', 2, 150000.00, 300000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitiethoadon`
--

CREATE TABLE `chitiethoadon` (
  `MASP` varchar(10) NOT NULL,
  `MAHD` varchar(10) NOT NULL,
  `SOLUONG` int(11) DEFAULT 1,
  `DONGIA` float DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `chitiethoadon`
--

INSERT INTO `chitiethoadon` (`MASP`, `MAHD`, `SOLUONG`, `DONGIA`) VALUES
('SP001AL', 'HD20250803', 1, 150000),
('SP002A31', 'HD20250804', 1, 200000),
('SP002A31', 'HD20250805', 2, 200000),
('SP002A32', 'HD20250805', 1, 200000);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chuongtrinhkhuyenmai`
--

CREATE TABLE `chuongtrinhkhuyenmai` (
  `ID` int(11) NOT NULL,
  `MACTKM` varchar(10) NOT NULL,
  `TENCTKM` varchar(100) DEFAULT NULL,
  `NGAYBATDAU` datetime DEFAULT NULL,
  `NGAYKETTHUC` datetime DEFAULT NULL,
  `MOTA` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `chuongtrinhkhuyenmai`
--

INSERT INTO `chuongtrinhkhuyenmai` (`ID`, `MACTKM`, `TENCTKM`, `NGAYBATDAU`, `NGAYKETTHUC`, `MOTA`) VALUES
(2, 'CT001', 'ct', '2025-08-03 20:04:00', '2025-08-03 20:08:00', ''),
(3, 'CT002', 'ct44', '0000-00-00 00:00:00', '2025-03-08 22:00:00', ''),
(4, 'CT003', 'ct44', '0000-00-00 00:00:00', '2025-03-08 22:00:00', ''),
(5, 'CT004', 'ct44', '0000-00-00 00:00:00', '2025-03-08 22:00:00', '');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `danhgia`
--

CREATE TABLE `danhgia` (
  `ID` int(11) NOT NULL,
  `MADG` varchar(10) NOT NULL,
  `MASP` varchar(10) NOT NULL,
  `MAKH` varchar(10) NOT NULL,
  `MAHD` varchar(10) NOT NULL,
  `SOSAODANHGIA` int(11) DEFAULT NULL CHECK (`SOSAODANHGIA` between 1 and 5),
  `NOIDUNG` varchar(500) DEFAULT NULL,
  `THOIGIAN` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `danhmuc`
--

CREATE TABLE `danhmuc` (
  `ID` int(11) NOT NULL,
  `MADM` varchar(10) NOT NULL,
  `TENDM` varchar(100) DEFAULT NULL,
  `MOTA` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `danhmuc`
--

INSERT INTO `danhmuc` (`ID`, `MADM`, `TENDM`, `MOTA`) VALUES
(7, 'DM001', 'Áo Nam', ''),
(8, 'DM002', 'Quần Nam', ''),
(9, 'DM003', 'Phụ Kiện', '');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `donhang`
--

CREATE TABLE `donhang` (
  `MADONHANG` varchar(20) NOT NULL,
  `MAKH` varchar(10) NOT NULL,
  `NGAYDAT` datetime NOT NULL,
  `TONGTIEN` decimal(10,2) NOT NULL,
  `TRANGTHAI` varchar(50) NOT NULL,
  `HOTEN` varchar(100) NOT NULL,
  `SODIENTHOAI` varchar(20) NOT NULL,
  `DIACHI` text NOT NULL,
  `PHUONGTHUCTHANHTOAN` varchar(50) NOT NULL,
  `GHICHU` text DEFAULT NULL,
  `is_confirmed` tinyint(1) DEFAULT 0,
  `MAVOUCHER` varchar(20) DEFAULT NULL,
  `GIATRIGIAM` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `donhang`
--

INSERT INTO `donhang` (`MADONHANG`, `MAKH`, `NGAYDAT`, `TONGTIEN`, `TRANGTHAI`, `HOTEN`, `SODIENTHOAI`, `DIACHI`, `PHUONGTHUCTHANHTOAN`, `GHICHU`, `is_confirmed`, `MAVOUCHER`, `GIATRIGIAM`) VALUES
('DH20250803100241180', 'KH003', '2025-08-03 15:02:41', 200000.00, 'Đã hoàn thành', 'Ngọc Lan', '0123456789', 'Hồ Chí Minh', 'COD', '', 1, NULL, 0.00),
('DH20250803112034119', 'KH002', '2025-08-03 16:20:34', 200000.00, 'Đã hoàn thành', 'quoc thinh', '0562761224', 'Bạc Liêu', 'COD', '', 1, NULL, 0.00),
('DH20250803113308824', 'KH002', '2025-08-03 16:33:08', 150000.00, 'Đã hoàn thành', 'quoc thinh', '0562761224', 'Bạc Liêu', 'COD', '', 1, NULL, 0.00),
('DH20250803123830912', 'KH002', '2025-08-03 17:38:30', 200000.00, 'Đã hoàn thành', 'quoc thinh', '0562761224', 'Hồ Chí Minh', 'COD', '', 1, NULL, 0.00),
('DH20250803152248626', 'KH003', '2025-08-03 20:22:48', 300000.00, 'Đã hoàn thành', 'Ngọc Lan', '0123456789', 'Hồ Chí Minh', 'COD', '', 0, NULL, 0.00),
('DH20250803153750523', 'KH003', '2025-08-03 20:37:50', 300000.00, 'Đã hoàn thành', 'Ngọc Lan', '0123456789', 'Hà Nội', 'COD', '', 0, NULL, 0.00),
('DH20250803154900174', 'KH002', '2025-08-03 20:49:00', 300000.00, 'Đã hoàn thành', 'quoc thinh', '0562761224', 'Bạc Liêu', 'COD', '', 1, NULL, 0.00),
('DH20250804183243490', 'KH004', '2025-08-04 23:32:43', 150000.00, 'Đã hoàn thành', 'Trần Hoàng', '0232454678', 'Cần Thơ', 'COD', '', 1, NULL, 0.00),
('DH20250804185042639', 'KH004', '2025-08-04 23:50:42', 300000.00, 'Đã hoàn thành', 'Trần Hoàng', '0232454789', 'Cần Thơ', 'COD', '', 1, NULL, 0.00),
('DH20250804192529571', 'KH004', '2025-08-05 00:25:29', 200000.00, 'Đã hoàn thành', 'Trần Hoàng', '0232454678', 'Cần Thơ', 'COD', '', 1, NULL, 0.00),
('DH20250805083837347', 'KH006', '2025-08-05 13:38:37', 400000.00, 'Đã hoàn thành', 'Huu Phat', '0386039665', 'Hồ Chí Minh', 'COD', '', 0, NULL, 0.00),
('DH20250805083959342', 'KH002', '2025-08-05 13:39:59', 200000.00, 'Đã hoàn thành', 'quoc thinh', '0562761224', 'Bạc Liêu', 'COD', '', 0, NULL, 0.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `giaohang`
--

CREATE TABLE `giaohang` (
  `ID` int(11) NOT NULL,
  `MAGH` varchar(10) NOT NULL,
  `MAHD` varchar(10) NOT NULL,
  `NGAYGIAO` datetime DEFAULT NULL,
  `DIACHIGIAO` varchar(255) DEFAULT NULL,
  `SDT_NHAN` varchar(15) DEFAULT NULL,
  `TEN_NGUOINHAN` varchar(100) DEFAULT NULL,
  `TRANGTHAIGH` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `giaohang`
--

INSERT INTO `giaohang` (`ID`, `MAGH`, `MAHD`, `NGAYGIAO`, `DIACHIGIAO`, `SDT_NHAN`, `TEN_NGUOINHAN`, `TRANGTHAIGH`) VALUES
(7, 'GH20250803', 'HD20250803', '2025-08-03 13:47:27', 'Bạc Liêu', '0562761224', 'quoc thinh', 'Chờ xử lý');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hoadon`
--

CREATE TABLE `hoadon` (
  `ID` int(11) NOT NULL,
  `MAHD` varchar(20) NOT NULL,
  `TONGTIEN` float DEFAULT NULL,
  `NGAYLAP` datetime DEFAULT NULL,
  `TRANGTHAI` varchar(20) DEFAULT NULL,
  `MAKH` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `hoadon`
--

INSERT INTO `hoadon` (`ID`, `MAHD`, `TONGTIEN`, `NGAYLAP`, `TRANGTHAI`, `MAKH`) VALUES
(8, 'HD20250803', 150000, '2025-08-03 13:47:27', 'Đã xác nhận', 'KH002'),
(16, 'HD20250804', 150000, '2025-08-04 23:32:43', 'Chờ xác nhận', 'KH004'),
(19, 'HD202508041850421492', 300000, '2025-08-04 23:50:42', 'Đã xác nhận', 'KH004'),
(20, 'HD202508041925293369', 200000, '2025-08-05 00:25:29', 'Đã xác nhận', 'KH004'),
(21, 'HD202508050838371569', 400000, '2025-08-05 13:38:37', 'Đã xác nhận', 'KH006'),
(22, 'HD202508050839598314', 200000, '2025-08-05 13:39:59', 'Đã xác nhận', 'KH002');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khachhang`
--

CREATE TABLE `khachhang` (
  `ID` int(11) NOT NULL,
  `MAKH` varchar(10) NOT NULL,
  `TENKH` varchar(100) DEFAULT NULL,
  `GIOITINH` varchar(10) DEFAULT NULL,
  `NGAYSINH` date DEFAULT NULL,
  `DIACHI` varchar(500) DEFAULT NULL,
  `SDT` varchar(15) DEFAULT NULL,
  `EMAIL` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `khachhang`
--

INSERT INTO `khachhang` (`ID`, `MAKH`, `TENKH`, `GIOITINH`, `NGAYSINH`, `DIACHI`, `SDT`, `EMAIL`) VALUES
(1, 'KH001', 'ADMIN', 'Khác', '1999-01-01', 'Hồ Chí Minh', '0999999999', 'admin@example.com'),
(2, 'KH002', 'quoc thinh', 'Nam', '2004-08-25', 'Bạc Liêu', '0562761224', 'thinh@gmail.com'),
(3, 'KH003', 'Ngọc Lan', 'Nữ', '2000-10-24', 'Hà Nội', '0123456789', 'Lan@gmail.com'),
(4, 'KH004', 'Trần Hoàng', 'Nam', '2002-10-14', 'Cần Thơ', '0232454678', 'hoang@gmail.com');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `loaisanpham`
--

CREATE TABLE `loaisanpham` (
  `MALOAI` varchar(10) NOT NULL,
  `TENLOAI` varchar(100) DEFAULT NULL,
  `MADM` varchar(10) DEFAULT NULL,
  `THUTU` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `loaisanpham`
--

INSERT INTO `loaisanpham` (`MALOAI`, `TENLOAI`, `MADM`, `THUTU`) VALUES
('L001', 'Áo Thun', 'DM001', 0),
('L002', 'Áo Khoác', 'DM001', 0),
('L003', 'Áo Polo', 'DM001', 0),
('L004', 'Áo Sơ Mi', 'DM001', 0),
('L005', 'Quần Tây', 'DM002', 0),
('L006', 'Quần Jean', 'DM002', 0),
('L007', 'Quần Short', 'DM002', 0),
('L008', 'Quần Kaki', 'DM002', 0),
('L009', 'Nơ', 'DM003', 0),
('L010', 'Ví', 'DM003', 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sanpham`
--

CREATE TABLE `sanpham` (
  `ID` int(11) NOT NULL,
  `MASP` varchar(10) NOT NULL,
  `TENSP` varchar(100) DEFAULT NULL,
  `MAUSAC` varchar(20) DEFAULT NULL,
  `KICHTHUOC` varchar(50) DEFAULT NULL,
  `GIA` int(11) DEFAULT NULL,
  `MOTA` varchar(500) DEFAULT NULL,
  `HINHANH` varchar(255) DEFAULT NULL,
  `MADM` varchar(10) NOT NULL,
  `hot` tinyint(1) DEFAULT 0,
  `news` tinyint(1) DEFAULT 0,
  `outsale` tinyint(1) DEFAULT 0,
  `SOLUONG` int(11) DEFAULT 0,
  `GROUPSP` varchar(10) DEFAULT NULL,
  `is_main` tinyint(1) DEFAULT 0,
  `MALOAI` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `sanpham`
--

INSERT INTO `sanpham` (`ID`, `MASP`, `TENSP`, `MAUSAC`, `KICHTHUOC`, `GIA`, `MOTA`, `HINHANH`, `MADM`, `hot`, `news`, `outsale`, `SOLUONG`, `GROUPSP`, `is_main`, `MALOAI`) VALUES
(14, 'SP001AM', 'Áo thun', 'Xanh Rêu', 'M', 150000, '', 'tshirt1.jpg', 'DM001', 1, 0, 0, 0, 'SP001', 1, 'L001'),
(15, 'SP001AL', 'Áo thun', 'Xanh Rêu', 'L', 150000, '', 'tshirt1.jpg', 'DM001', 1, 0, 0, 1, 'SP001', 1, 'L001'),
(20, 'SP002A31', 'Quần Short', 'Xám', '31', 200000, '', 'short2_thumb1.jpg', 'DM002', 1, 0, 0, 2, 'SP002', 1, 'L007'),
(21, 'SP002A32', 'Quần Short', 'Xám', '32', 200000, '', 'short2_thumb1.jpg', 'DM002', 1, 0, 0, 3, 'SP002', 1, 'L007'),
(22, 'SP003A29', 'Quần kaki', 'Nâu', '29', 240000, '', 'kaki3_thumb2.jpg', 'DM002', 0, 0, 0, 3, 'SP003', 1, 'L008'),
(23, 'SP003A30', 'Quần kaki', 'Nâu', '30', 240000, '', 'kaki3_thumb2.jpg', 'DM002', 0, 0, 0, 3, 'SP003', 1, 'L008'),
(24, 'SP004A', 'Nơ', 'Đen', 'Freesize', 80000, '', 'bowtie1.jpg', 'DM003', 0, 1, 0, 3, 'SP004', 1, 'L009'),
(25, 'SP005A', 'Ví ', 'Nâu', 'Freesize', 95000, '', 'wallet1_thumb1.jpg', 'DM003', 0, 0, 1, 3, 'SP005', 1, 'L010'),
(26, 'SP006AXL', 'áo khoác', 'Be', 'XL', 300000, '', 'coat3_thumb1.jpg', 'DM001', 0, 1, 0, 0, 'SP006', 1, 'L002');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `taikhoan`
--

CREATE TABLE `taikhoan` (
  `ID` int(11) NOT NULL,
  `MATK` varchar(10) NOT NULL,
  `TENDANGNHAP` varchar(50) DEFAULT NULL,
  `MATKHAU` varchar(255) DEFAULT NULL,
  `MAKH` varchar(10) NOT NULL,
  `VAITRO` varchar(20) DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `taikhoan`
--

INSERT INTO `taikhoan` (`ID`, `MATK`, `TENDANGNHAP`, `MATKHAU`, `MAKH`, `VAITRO`) VALUES
(1, 'TK001', 'admin', '$2y$10$u206qx8FtGXS1k5EWVkyLuNAm0SIPK21HpUWt3I7Zn2zgAd2oWRWu', 'KH001', 'user'),
(2, 'TK002', 'thinh', '$2y$10$0rbimv.aoXSd0gL5hYVAZ.vzAQf66QsnYl0jlfBlUt6a5I3lUJiOW', 'KH002', 'user'),
(3, 'TK003', 'Ngọc Lan', '$2y$10$9flx1liOf/R.M8JAAlcwJu.2XxKapsklcosYujbsDwmV4VxWP8FDK', 'KH003', 'user'),
(4, 'TK004', 'hoang', '$2y$10$ZuHlepQlbHSWtwDBsWawduIj0GGbhztqtb6m82DBKAYJ73nBHw0Bi', 'KH004', 'user');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `voucher`
--

CREATE TABLE `voucher` (
  `ID` int(11) NOT NULL,
  `MAVOUCHER` varchar(20) NOT NULL,
  `TENVOUCHER` varchar(100) NOT NULL,
  `MOTA` varchar(500) DEFAULT NULL,
  `LOAIVOUCHER` enum('percent','fixed','freeship') NOT NULL DEFAULT 'percent',
  `GIATRI` decimal(10,2) NOT NULL DEFAULT 0.00,
  `GIATRIMIN` decimal(10,2) DEFAULT 0.00,
  `GIATRIMAX` decimal(10,2) DEFAULT NULL,
  `SOLUONG` int(11) NOT NULL DEFAULT 1,
  `SOLUONGSUDUNG` int(11) NOT NULL DEFAULT 0,
  `NGAYBATDAU` datetime NOT NULL,
  `NGAYHETHAN` datetime NOT NULL,
  `TRANGTHAI` enum('active','inactive','expired') NOT NULL DEFAULT 'active',
  `NGAYTAO` datetime DEFAULT current_timestamp(),
  `NGAYCAPNHAT` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `voucher_usage`
--

CREATE TABLE `voucher_usage` (
  `ID` int(11) NOT NULL,
  `MAVOUCHER` varchar(20) NOT NULL,
  `MAKH` varchar(10) NOT NULL,
  `MADONHANG` varchar(20) NOT NULL,
  `GIATRIGIAM` decimal(10,2) NOT NULL,
  `NGAYSUDUNG` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `chitietctkm`
--
ALTER TABLE `chitietctkm`
  ADD PRIMARY KEY (`MASP`,`MACTKM`),
  ADD KEY `chitietctmk_ibfk_2` (`MACTKM`);

--
-- Chỉ mục cho bảng `chitiethoadon`
--
ALTER TABLE `chitiethoadon`
  ADD PRIMARY KEY (`MASP`,`MAHD`),
  ADD KEY `chitiethoadon_ibfk_2` (`MAHD`);

--
-- Chỉ mục cho bảng `chuongtrinhkhuyenmai`
--
ALTER TABLE `chuongtrinhkhuyenmai`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `MACTKM` (`MACTKM`);

--
-- Chỉ mục cho bảng `danhgia`
--
ALTER TABLE `danhgia`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `MADG` (`MADG`),
  ADD KEY `danhgia_ibfk_1` (`MASP`),
  ADD KEY `danhgia_ibfk_2` (`MAKH`),
  ADD KEY `danhgia_ibfk_3` (`MAHD`);

--
-- Chỉ mục cho bảng `danhmuc`
--
ALTER TABLE `danhmuc`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `MADM` (`MADM`);

--
-- Chỉ mục cho bảng `donhang`
--
ALTER TABLE `donhang`
  ADD KEY `MADONHANG` (`MADONHANG`),
  ADD KEY `MAVOUCHER` (`MAVOUCHER`);

--
-- Chỉ mục cho bảng `giaohang`
--
ALTER TABLE `giaohang`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `MAGH` (`MAGH`),
  ADD KEY `giaohang_ibfk_1` (`MAHD`);

--
-- Chỉ mục cho bảng `hoadon`
--
ALTER TABLE `hoadon`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `MAHD` (`MAHD`),
  ADD KEY `hoadon_ibfk_1` (`MAKH`);

--
-- Chỉ mục cho bảng `khachhang`
--
ALTER TABLE `khachhang`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `MAKH` (`MAKH`);

--
-- Chỉ mục cho bảng `loaisanpham`
--
ALTER TABLE `loaisanpham`
  ADD PRIMARY KEY (`MALOAI`);

--
-- Chỉ mục cho bảng `sanpham`
--
ALTER TABLE `sanpham`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `MASP` (`MASP`),
  ADD KEY `sanpham_ibfk_1` (`MADM`);

--
-- Chỉ mục cho bảng `taikhoan`
--
ALTER TABLE `taikhoan`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `MATK` (`MATK`),
  ADD KEY `taikhoan_ibfk_1` (`MAKH`);

--
-- Chỉ mục cho bảng `voucher`
--
ALTER TABLE `voucher`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `MAVOUCHER` (`MAVOUCHER`);

--
-- Chỉ mục cho bảng `voucher_usage`
--
ALTER TABLE `voucher_usage`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `voucher_usage_ibfk_1` (`MAVOUCHER`),
  ADD KEY `voucher_usage_ibfk_2` (`MAKH`),
  ADD KEY `voucher_usage_ibfk_3` (`MADONHANG`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `chuongtrinhkhuyenmai`
--
ALTER TABLE `chuongtrinhkhuyenmai`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `danhgia`
--
ALTER TABLE `danhgia`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `danhmuc`
--
ALTER TABLE `danhmuc`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `giaohang`
--
ALTER TABLE `giaohang`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT cho bảng `hoadon`
--
ALTER TABLE `hoadon`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT cho bảng `khachhang`
--
ALTER TABLE `khachhang`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `sanpham`
--
ALTER TABLE `sanpham`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT cho bảng `taikhoan`
--
ALTER TABLE `taikhoan`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `voucher`
--
ALTER TABLE `voucher`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `voucher_usage`
--
ALTER TABLE `voucher_usage`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `donhang`
--
ALTER TABLE `donhang`
  ADD CONSTRAINT `donhang_ibfk_1` FOREIGN KEY (`MAVOUCHER`) REFERENCES `voucher` (`MAVOUCHER`);

--
-- Các ràng buộc cho bảng `voucher_usage`
--
ALTER TABLE `voucher_usage`
  ADD CONSTRAINT `voucher_usage_ibfk_1` FOREIGN KEY (`MAVOUCHER`) REFERENCES `voucher` (`MAVOUCHER`),
  ADD CONSTRAINT `voucher_usage_ibfk_2` FOREIGN KEY (`MAKH`) REFERENCES `khachhang` (`MAKH`),
  ADD CONSTRAINT `voucher_usage_ibfk_3` FOREIGN KEY (`MADONHANG`) REFERENCES `donhang` (`MADONHANG`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
