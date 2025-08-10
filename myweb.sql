-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th8 06, 2025 lúc 09:48 AM
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
('SP005A', 'CT001', NULL, 10),
('SP024AXL', 'CT001', NULL, 10);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitietdonhang`
--

CREATE TABLE `chitietdonhang` (
  `ID` int(11) NOT NULL,
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

INSERT INTO `chitietdonhang` (`ID`, `MADONHANG`, `MASP`, `KICHTHUOC`, `SOLUONG`, `GIA`, `THANHTIEN`) VALUES
(1, 'DH20250806141840135', 'SP006AS', 'S', 1, 350000.00, 350000.00),
(2, 'DH20250806142338891', 'SP005AS', 'S', 2, 320000.00, 640000.00),
(3, 'DH20250806142830339', 'SP009A28', '28', 1, 450000.00, 450000.00),
(4, 'DH20250806142917409', 'SP003AM', 'M', 2, 180000.00, 360000.00),
(5, 'DH20250806143105567', 'SP006AL', 'L', 1, 350000.00, 350000.00),
(6, 'DH20250806143311645', 'SP016A29', '29', 1, 350000.00, 350000.00),
(7, 'DH20250806143556363', 'SP020A', 'Freesize', 1, 35000.00, 35000.00),
(8, 'DH20250806143734196', 'SP011A32', '32', 1, 200000.00, 200000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitiethoadon`
--

CREATE TABLE `chitiethoadon` (
  `MASP` varchar(10) NOT NULL,
  `MAHD` varchar(20) NOT NULL,
  `SOLUONG` int(11) DEFAULT 1,
  `DONGIA` float DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `chitiethoadon`
--

INSERT INTO `chitiethoadon` (`MASP`, `MAHD`, `SOLUONG`, `DONGIA`) VALUES
('SP003AM', 'HD20250806142917570', 2, 180000),
('SP005AS', 'HD20250806142338870', 2, 320000),
('SP006AL', 'HD20250806143105311', 1, 350000),
('SP006AS', 'HD20250806141840207', 1, 350000),
('SP009A28', 'HD20250806142830952', 1, 450000),
('SP011A32', 'HD20250806143734568', 1, 200000),
('SP016A29', 'HD20250806143311648', 1, 350000),
('SP020A', 'HD20250806143556244', 1, 35000);

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
(1, 'CT001', 'best saler', '2025-08-06 14:38:00', '2025-08-07 14:38:00', 'Sale sập sàn');

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
('DH20250806141840135', 'KH003', '2025-08-06 14:18:40', 350000.00, 'Đã hoàn thành', 'Huu Phat', '0386039665', 'Hồ Chí Minh', 'COD', '', 1, NULL, 0.00),
('DH20250806142338891', 'KH003', '2025-08-06 14:23:38', 620000.00, 'Đã hoàn thành', 'Huu Phat', '0386039665', 'Hồ Chí Minh', 'COD', '', 1, 'GIAM20K', 20000.00),
('DH20250806142830339', 'KH002', '2025-08-06 14:28:30', 430000.00, 'Đã hoàn thành', 'quoc thinh', '0562761224', 'Hồ Chí Minh', 'COD', '', 0, 'GIAM20K', 20000.00),
('DH20250806142917409', 'KH002', '2025-08-06 14:29:17', 360000.00, 'Đã hoàn thành', 'quoc thinh', '0562761224', 'Hồ Chí Minh', 'COD', '', 0, NULL, 0.00),
('DH20250806143105567', 'KH002', '2025-08-06 14:31:05', 350000.00, 'Đã hoàn thành', 'quoc thinh', '0562761224', 'Hồ Chí Minh', 'COD', '', 0, NULL, 0.00),
('DH20250806143311645', 'KH004', '2025-08-06 14:33:11', 350000.00, 'Đã hoàn thành', 'Ngọc Lan', '0228374565', 'Hà Nội', 'COD', '', 0, NULL, 0.00),
('DH20250806143556363', 'KH004', '2025-08-06 14:35:56', 35000.00, 'Đã hoàn thành', 'Ngọc Lan', '0228374565', 'Hà Nội', 'COD', '', 0, NULL, 0.00),
('DH20250806143734196', 'KH003', '2025-08-06 14:37:34', 200000.00, 'Đã hoàn thành', 'Huu Phat', '0386039665', 'Hồ Chí Minh', 'Bank', '', 0, NULL, 0.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `giaohang`
--

CREATE TABLE `giaohang` (
  `ID` int(11) NOT NULL,
  `MAGH` varchar(20) NOT NULL,
  `MAHD` varchar(10) NOT NULL,
  `MADONHANG` varchar(20) DEFAULT NULL,
  `NGAYGIAO` datetime DEFAULT NULL,
  `DIACHIGIAO` varchar(255) DEFAULT NULL,
  `SDT_NHAN` varchar(15) DEFAULT NULL,
  `TEN_NGUOINHAN` varchar(100) DEFAULT NULL,
  `TRANGTHAIGH` varchar(50) DEFAULT NULL,
  `PHIVANCHUYEN` decimal(10,2) DEFAULT 30000.00,
  `NGAYTAO` datetime DEFAULT current_timestamp(),
  `NGAYCAPNHAT` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `GHICHU_GH` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `giaohang`
--

INSERT INTO `giaohang` (`ID`, `MAGH`, `MAHD`, `MADONHANG`, `NGAYGIAO`, `DIACHIGIAO`, `SDT_NHAN`, `TEN_NGUOINHAN`, `TRANGTHAIGH`, `PHIVANCHUYEN`, `NGAYTAO`, `NGAYCAPNHAT`, `GHICHU_GH`) VALUES
(1, 'GH20250806141840107', 'HD20250806', 'DH20250806141840135', NULL, 'Hồ Chí Minh', '0386039665', 'Huu Phat', 'Chờ xử lý', 30000.00, '2025-08-06 14:18:40', '2025-08-06 14:18:40', 'Đơn hàng mới được tạo'),
(2, 'GH20250806142338403', 'HD20250806', 'DH20250806142338891', NULL, 'Hồ Chí Minh', '0386039665', 'Huu Phat', 'Chờ xử lý', 30000.00, '2025-08-06 14:23:38', '2025-08-06 14:23:38', 'Đơn hàng mới được tạo'),
(3, 'GH20250806142830506', 'HD20250806', 'DH20250806142830339', NULL, 'Hồ Chí Minh', '0562761224', 'quoc thinh', 'Chờ xử lý', 30000.00, '2025-08-06 14:28:30', '2025-08-06 14:28:30', 'Đơn hàng mới được tạo'),
(4, 'GH20250806142917294', 'HD20250806', 'DH20250806142917409', NULL, 'Hồ Chí Minh', '0562761224', 'quoc thinh', 'Chờ xử lý', 30000.00, '2025-08-06 14:29:17', '2025-08-06 14:29:17', 'Đơn hàng mới được tạo'),
(5, 'GH20250806143105734', 'HD20250806', 'DH20250806143105567', NULL, 'Hồ Chí Minh', '0562761224', 'quoc thinh', 'Chờ xử lý', 30000.00, '2025-08-06 14:31:05', '2025-08-06 14:31:05', 'Đơn hàng mới được tạo'),
(6, 'GH20250806143311873', 'HD20250806', 'DH20250806143311645', NULL, 'Hà Nội', '0228374565', 'Ngọc Lan', 'Chờ xử lý', 30000.00, '2025-08-06 14:33:11', '2025-08-06 14:33:11', 'Đơn hàng mới được tạo'),
(7, 'GH20250806143556592', 'HD20250806', 'DH20250806143556363', NULL, 'Hà Nội', '0228374565', 'Ngọc Lan', 'Chờ xử lý', 30000.00, '2025-08-06 14:35:56', '2025-08-06 14:35:56', 'Đơn hàng mới được tạo'),
(8, 'GH20250806143734530', 'HD20250806', 'DH20250806143734196', NULL, 'Hồ Chí Minh', '0386039665', 'Huu Phat', 'Chờ xử lý', 30000.00, '2025-08-06 14:37:34', '2025-08-06 14:37:34', 'Đơn hàng mới được tạo');

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
(1, 'HD20250806141840207', 350000, '2025-08-06 14:18:40', 'Đã xác nhận', 'KH003'),
(2, 'HD20250806142338870', 620000, '2025-08-06 14:23:38', 'Đã xác nhận', 'KH003'),
(3, 'HD20250806142830952', 430000, '2025-08-06 14:28:30', 'Đã xác nhận', 'KH002'),
(4, 'HD20250806142917570', 360000, '2025-08-06 14:29:17', 'Đã xác nhận', 'KH002'),
(5, 'HD20250806143105311', 350000, '2025-08-06 14:31:05', 'Đã xác nhận', 'KH002'),
(6, 'HD20250806143311648', 350000, '2025-08-06 14:33:11', 'Đã xác nhận', 'KH004'),
(7, 'HD20250806143556244', 35000, '2025-08-06 14:35:56', 'Đã xác nhận', 'KH004'),
(8, 'HD20250806143734568', 200000, '2025-08-06 14:37:34', 'Đã xác nhận', 'KH003');

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
(1, 'KH001', 'Admin', 'Khác', '1999-01-01', 'Hồ Chí Minh', '0999999999', 'admin@gmail.com'),
(2, 'KH002', 'quoc thinh', 'Nam', '2000-10-10', 'Hồ Chí Minh', '0562761224', 'quocthinh0562@gmail.com'),
(3, 'KH003', 'Huu Phat', 'Nam', '2004-07-24', 'Hồ Chí Minh', '0386039665', 'phat8348@gmail.com'),
(4, 'KH004', 'Ngọc Lan', 'Nữ', '2002-05-12', 'Hà Nội', '0228374565', 'lan@gmail.com');

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
('L001', 'Áo thun', 'DM001', 0),
('L002', 'Áo Sơ Mi', 'DM001', 0),
('L003', 'Áo Polo', 'DM001', 0),
('L004', 'Áo Khoác', 'DM001', 0),
('L005', 'Quần Tây', 'DM002', 0),
('L006', 'Quần Short', 'DM002', 0),
('L007', 'Quần Kaki', 'DM002', 0),
('L008', 'Quần Jean', 'DM002', 0),
('L009', 'Ví', 'DM003', 0),
('L010', 'Nón', 'DM003', 0),
('L011', 'Cà Vạt & Nơ', 'DM003', 0),
('L012', 'Thắt Lưng', 'DM003', 0);

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
  `MALOAI` varchar(10) DEFAULT NULL,
  `IS_DELETED` tinyint(1) DEFAULT 0 COMMENT 'Trạng thái xóa mềm: 0=Hiển thị, 1=Đã ẩn'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `sanpham`
--

INSERT INTO `sanpham` (`ID`, `MASP`, `TENSP`, `MAUSAC`, `KICHTHUOC`, `GIA`, `MOTA`, `HINHANH`, `MADM`, `hot`, `news`, `outsale`, `SOLUONG`, `GROUPSP`, `is_main`, `MALOAI`, `IS_DELETED`) VALUES
(1, 'SP001AM', 'Áo thun basic', 'Trắng', 'M', 120000, 'Áo thun là lựa chọn kinh điển phù hợp với mọi lứa tuổi và giới tính. Thiết kế đơn giản, ôm nhẹ cơ thể, mang lại cảm giác thoải mái khi vận động. Chất liệu cotton cao cấp giúp áo thấm hút mồ hôi tốt, dễ phối cùng quần jean, quần short hoặc chân váy cho nhiều phong cách khác nhau.', 'tshirt2_thumb1.jpg', 'DM001', 1, 0, 0, 10, 'SP001', 1, 'L001', 0),
(2, 'SP001AL', 'Áo thun basic', 'Trắng', 'L', 120000, 'Áo thun là lựa chọn kinh điển phù hợp với mọi lứa tuổi và giới tính. Thiết kế đơn giản, ôm nhẹ cơ thể, mang lại cảm giác thoải mái khi vận động. Chất liệu cotton cao cấp giúp áo thấm hút mồ hôi tốt, dễ phối cùng quần jean, quần short hoặc chân váy cho nhiều phong cách khác nhau.', 'tshirt2_thumb1.jpg', 'DM001', 1, 0, 0, 10, 'SP001', 1, 'L001', 0),
(3, 'SP001AXL', 'Áo thun basic', 'Trắng', 'XL', 120000, 'Áo thun là lựa chọn kinh điển phù hợp với mọi lứa tuổi và giới tính. Thiết kế đơn giản, ôm nhẹ cơ thể, mang lại cảm giác thoải mái khi vận động. Chất liệu cotton cao cấp giúp áo thấm hút mồ hôi tốt, dễ phối cùng quần jean, quần short hoặc chân váy cho nhiều phong cách khác nhau.', 'tshirt2_thumb1.jpg', 'DM001', 1, 0, 0, 10, 'SP001', 1, 'L001', 0),
(4, 'SP002AS', 'Áo Thun', 'Xanh Rêu', 'S', 125000, 'Áo thun cổ tròn là lựa chọn kinh điển phù hợp với mọi lứa tuổi và giới tính. Thiết kế đơn giản, ôm nhẹ cơ thể, mang lại cảm giác thoải mái khi vận động. Chất liệu cotton cao cấp giúp áo thấm hút mồ hôi tốt, dễ phối cùng quần jean, quần short.', 'tshirt3.jpg', 'DM001', 0, 1, 0, 10, 'SP002', 1, 'L001', 0),
(5, 'SP002AM', 'Áo Thun', 'Xanh Rêu', 'M', 125000, 'Áo thun cổ tròn là lựa chọn kinh điển phù hợp với mọi lứa tuổi và giới tính. Thiết kế đơn giản, ôm nhẹ cơ thể, mang lại cảm giác thoải mái khi vận động. Chất liệu cotton cao cấp giúp áo thấm hút mồ hôi tốt, dễ phối cùng quần jean, quần short.', 'tshirt3.jpg', 'DM001', 0, 1, 0, 10, 'SP002', 1, 'L001', 0),
(6, 'SP002AL', 'Áo Thun', 'Xanh Rêu', 'L', 125000, 'Áo thun cổ tròn là lựa chọn kinh điển phù hợp với mọi lứa tuổi và giới tính. Thiết kế đơn giản, ôm nhẹ cơ thể, mang lại cảm giác thoải mái khi vận động. Chất liệu cotton cao cấp giúp áo thấm hút mồ hôi tốt, dễ phối cùng quần jean, quần short.', 'tshirt3.jpg', 'DM001', 0, 1, 0, 10, 'SP002', 1, 'L001', 0),
(7, 'SP003AM', 'Áo Polo', 'Nâu', 'M', 180000, 'Áo thun Polo với đặc trưng cổ bẻ và hàng nút trước ngực mang đến sự lịch lãm, khỏe khoắn. Chất liệu thun co giãn, bền màu và giữ form tốt, dễ dàng phối cùng quần tây, quần kaki hoặc jean. Lựa chọn lý tưởng cho môi trường công sở hoặc các hoạt động ngoài trời.', 'polo1_thumb1.jpg', 'DM001', 0, 0, 0, 8, 'SP003', 1, 'L003', 0),
(8, 'SP003AL', 'Áo Polo', 'Nâu', 'L', 180000, 'Áo thun Polo với đặc trưng cổ bẻ và hàng nút trước ngực mang đến sự lịch lãm, khỏe khoắn. Chất liệu thun co giãn, bền màu và giữ form tốt, dễ dàng phối cùng quần tây, quần kaki hoặc jean. Lựa chọn lý tưởng cho môi trường công sở hoặc các hoạt động ngoài trời.', 'polo1_thumb1.jpg', 'DM001', 0, 0, 0, 10, 'SP003', 1, 'L003', 0),
(9, 'SP004AS', 'Áo Polo 2', 'Trắng', 'S', 200000, 'Áo thun Polo với đặc trưng cổ bẻ và hàng nút trước ngực mang đến sự lịch lãm, khỏe khoắn. Chất liệu thun co giãn, bền màu và giữ form tốt, dễ dàng phối cùng quần tây, quần kaki hoặc jean. Lựa chọn lý tưởng cho môi trường công sở hoặc các hoạt động ngoài trời.', 'polo4.jpg', 'DM001', 0, 1, 0, 10, 'SP004', 1, 'L003', 0),
(10, 'SP004AM', 'Áo Polo 2', 'Trắng', 'M', 200000, 'Áo thun Polo với đặc trưng cổ bẻ và hàng nút trước ngực mang đến sự lịch lãm, khỏe khoắn. Chất liệu thun co giãn, bền màu và giữ form tốt, dễ dàng phối cùng quần tây, quần kaki hoặc jean. Lựa chọn lý tưởng cho môi trường công sở hoặc các hoạt động ngoài trời.', 'polo4.jpg', 'DM001', 0, 1, 0, 10, 'SP004', 1, 'L003', 0),
(11, 'SP004AL', 'Áo Polo 2', 'Trắng', 'L', 200000, 'Áo thun Polo với đặc trưng cổ bẻ và hàng nút trước ngực mang đến sự lịch lãm, khỏe khoắn. Chất liệu thun co giãn, bền màu và giữ form tốt, dễ dàng phối cùng quần tây, quần kaki hoặc jean. Lựa chọn lý tưởng cho môi trường công sở hoặc các hoạt động ngoài trời.', 'polo4.jpg', 'DM001', 0, 1, 0, 10, 'SP004', 1, 'L003', 0),
(12, 'SP004AXL', 'Áo Polo 2', 'Trắng', 'XL', 200000, 'Áo thun Polo với đặc trưng cổ bẻ và hàng nút trước ngực mang đến sự lịch lãm, khỏe khoắn. Chất liệu thun co giãn, bền màu và giữ form tốt, dễ dàng phối cùng quần tây, quần kaki hoặc jean. Lựa chọn lý tưởng cho môi trường công sở hoặc các hoạt động ngoài trời.', 'polo4.jpg', 'DM001', 0, 1, 0, 10, 'SP004', 1, 'L003', 0),
(13, 'SP005AS', 'Áo Khoác', 'Đen', 'S', 320000, 'Áo khoác là item không thể thiếu trong tủ đồ, giúp bảo vệ cơ thể trước gió lạnh, nắng bụi và tạo điểm nhấn cho phong cách cá nhân. Được may từ chất liệu cao cấp như kaki, dù, jean hoặc nỉ, áo khoác đảm bảo độ bền và khả năng giữ ấm hiệu quả. Thiết kế đa dạng: từ dáng bomber năng động, áo khoác jeans trẻ trung, đến hoodie cá tính, dễ dàng phối cùng nhiều loại trang phục khác nhau. Phù hợp cho mọi hoạt động: đi làm, đi chơi, dạo phố hay thể thao ngoài trời.', 'coat1.jpg', 'DM001', 0, 0, 0, 8, 'SP005', 1, 'L004', 0),
(14, 'SP005AM', 'Áo Khoác', 'Đen', 'M', 320000, 'Áo khoác là item không thể thiếu trong tủ đồ, giúp bảo vệ cơ thể trước gió lạnh, nắng bụi và tạo điểm nhấn cho phong cách cá nhân. Được may từ chất liệu cao cấp như kaki, dù, jean hoặc nỉ, áo khoác đảm bảo độ bền và khả năng giữ ấm hiệu quả. Thiết kế đa dạng: từ dáng bomber năng động, áo khoác jeans trẻ trung, đến hoodie cá tính, dễ dàng phối cùng nhiều loại trang phục khác nhau. Phù hợp cho mọi hoạt động: đi làm, đi chơi, dạo phố hay thể thao ngoài trời.', 'coat1.jpg', 'DM001', 0, 0, 0, 10, 'SP005', 1, 'L004', 0),
(15, 'SP005AL', 'Áo Khoác', 'Đen', 'L', 320000, 'Áo khoác là item không thể thiếu trong tủ đồ, giúp bảo vệ cơ thể trước gió lạnh, nắng bụi và tạo điểm nhấn cho phong cách cá nhân. Được may từ chất liệu cao cấp như kaki, dù, jean hoặc nỉ, áo khoác đảm bảo độ bền và khả năng giữ ấm hiệu quả. Thiết kế đa dạng: từ dáng bomber năng động, áo khoác jeans trẻ trung, đến hoodie cá tính, dễ dàng phối cùng nhiều loại trang phục khác nhau. Phù hợp cho mọi hoạt động: đi làm, đi chơi, dạo phố hay thể thao ngoài trời.', 'coat1.jpg', 'DM001', 0, 0, 0, 10, 'SP005', 1, 'L004', 0),
(16, 'SP005AXL', 'Áo Khoác', 'Đen', 'XL', 320000, 'Áo khoác là item không thể thiếu trong tủ đồ, giúp bảo vệ cơ thể trước gió lạnh, nắng bụi và tạo điểm nhấn cho phong cách cá nhân. Được may từ chất liệu cao cấp như kaki, dù, jean hoặc nỉ, áo khoác đảm bảo độ bền và khả năng giữ ấm hiệu quả. Thiết kế đa dạng: từ dáng bomber năng động, áo khoác jeans trẻ trung, đến hoodie cá tính, dễ dàng phối cùng nhiều loại trang phục khác nhau. Phù hợp cho mọi hoạt động: đi làm, đi chơi, dạo phố hay thể thao ngoài trời.', 'coat1.jpg', 'DM001', 0, 0, 0, 10, 'SP005', 1, 'L004', 0),
(17, 'SP006AS', 'Áo Khoác 2', 'Xám', 'S', 350000, 'Áo khoác là item không thể thiếu trong tủ đồ, giúp bảo vệ cơ thể trước gió lạnh, nắng bụi và tạo điểm nhấn cho phong cách cá nhân. Được may từ chất liệu cao cấp như kaki, dù, jean hoặc nỉ, áo khoác đảm bảo độ bền và khả năng giữ ấm hiệu quả. Thiết kế đa dạng: từ dáng bomber năng động, áo khoác jeans trẻ trung, đến hoodie cá tính, dễ dàng phối cùng nhiều loại trang phục khác nhau. Phù hợp cho mọi hoạt động: đi làm, đi chơi, dạo phố hay thể thao ngoài trời.', 'coat4.jpg', 'DM001', 1, 0, 0, 9, 'SP006', 1, 'L004', 0),
(18, 'SP006AM', 'Áo Khoác 2', 'Xám', 'M', 350000, 'Áo khoác là item không thể thiếu trong tủ đồ, giúp bảo vệ cơ thể trước gió lạnh, nắng bụi và tạo điểm nhấn cho phong cách cá nhân. Được may từ chất liệu cao cấp như kaki, dù, jean hoặc nỉ, áo khoác đảm bảo độ bền và khả năng giữ ấm hiệu quả. Thiết kế đa dạng: từ dáng bomber năng động, áo khoác jeans trẻ trung, đến hoodie cá tính, dễ dàng phối cùng nhiều loại trang phục khác nhau. Phù hợp cho mọi hoạt động: đi làm, đi chơi, dạo phố hay thể thao ngoài trời.', 'coat4.jpg', 'DM001', 1, 0, 0, 10, 'SP006', 1, 'L004', 0),
(19, 'SP006AL', 'Áo Khoác 2', 'Xám', 'L', 350000, 'Áo khoác là item không thể thiếu trong tủ đồ, giúp bảo vệ cơ thể trước gió lạnh, nắng bụi và tạo điểm nhấn cho phong cách cá nhân. Được may từ chất liệu cao cấp như kaki, dù, jean hoặc nỉ, áo khoác đảm bảo độ bền và khả năng giữ ấm hiệu quả. Thiết kế đa dạng: từ dáng bomber năng động, áo khoác jeans trẻ trung, đến hoodie cá tính, dễ dàng phối cùng nhiều loại trang phục khác nhau. Phù hợp cho mọi hoạt động: đi làm, đi chơi, dạo phố hay thể thao ngoài trời.', 'coat4.jpg', 'DM001', 1, 0, 0, 9, 'SP006', 1, 'L004', 0),
(20, 'SP006AXL', 'Áo Khoác 2', 'Xám', 'XL', 350000, 'Áo khoác là item không thể thiếu trong tủ đồ, giúp bảo vệ cơ thể trước gió lạnh, nắng bụi và tạo điểm nhấn cho phong cách cá nhân. Được may từ chất liệu cao cấp như kaki, dù, jean hoặc nỉ, áo khoác đảm bảo độ bền và khả năng giữ ấm hiệu quả. Thiết kế đa dạng: từ dáng bomber năng động, áo khoác jeans trẻ trung, đến hoodie cá tính, dễ dàng phối cùng nhiều loại trang phục khác nhau. Phù hợp cho mọi hoạt động: đi làm, đi chơi, dạo phố hay thể thao ngoài trời.', 'coat4.jpg', 'DM001', 1, 0, 0, 10, 'SP006', 1, 'L004', 0),
(29, 'SP009A28', 'Quần Jean', 'Đen', '28', 450000, 'Quần jean là item “quốc dân”, không thể thiếu trong tủ đồ của mọi người. Với chất liệu denim bền chắc, khả năng giữ form tốt, quần jean phù hợp với nhiều phong cách từ cá tính, năng động đến lịch sự. Dễ dàng phối với áo thun, sơ mi, hoodie hay áo khoác, thích hợp cho cả đi làm, đi học lẫn đi chơi.', 'jean2_thumb1.jpg', 'DM002', 1, 0, 0, 9, 'SP009', 1, 'L008', 0),
(30, 'SP009A29', 'Quần Jean', 'Đen', '29', 450000, 'Quần jean là item “quốc dân”, không thể thiếu trong tủ đồ của mọi người. Với chất liệu denim bền chắc, khả năng giữ form tốt, quần jean phù hợp với nhiều phong cách từ cá tính, năng động đến lịch sự. Dễ dàng phối với áo thun, sơ mi, hoodie hay áo khoác, thích hợp cho cả đi làm, đi học lẫn đi chơi.', 'jean2_thumb1.jpg', 'DM002', 1, 0, 0, 10, 'SP009', 1, 'L008', 0),
(31, 'SP009A30', 'Quần Jean', 'Đen', '30', 450000, 'Quần jean là item “quốc dân”, không thể thiếu trong tủ đồ của mọi người. Với chất liệu denim bền chắc, khả năng giữ form tốt, quần jean phù hợp với nhiều phong cách từ cá tính, năng động đến lịch sự. Dễ dàng phối với áo thun, sơ mi, hoodie hay áo khoác, thích hợp cho cả đi làm, đi học lẫn đi chơi.', 'jean2_thumb1.jpg', 'DM002', 1, 0, 0, 10, 'SP009', 1, 'L008', 0),
(32, 'SP009A31', 'Quần Jean', 'Đen', '31', 450000, 'Quần jean là item “quốc dân”, không thể thiếu trong tủ đồ của mọi người. Với chất liệu denim bền chắc, khả năng giữ form tốt, quần jean phù hợp với nhiều phong cách từ cá tính, năng động đến lịch sự. Dễ dàng phối với áo thun, sơ mi, hoodie hay áo khoác, thích hợp cho cả đi làm, đi học lẫn đi chơi.', 'jean2_thumb1.jpg', 'DM002', 1, 0, 0, 10, 'SP009', 1, 'L008', 0),
(33, 'SP009A32', 'Quần Jean', 'Đen', '32', 450000, 'Quần jean là item “quốc dân”, không thể thiếu trong tủ đồ của mọi người. Với chất liệu denim bền chắc, khả năng giữ form tốt, quần jean phù hợp với nhiều phong cách từ cá tính, năng động đến lịch sự. Dễ dàng phối với áo thun, sơ mi, hoodie hay áo khoác, thích hợp cho cả đi làm, đi học lẫn đi chơi.', 'jean2_thumb1.jpg', 'DM002', 1, 0, 0, 10, 'SP009', 1, 'L008', 0),
(34, 'SP009A33', 'Quần Jean', 'Đen', '33', 450000, 'Quần jean là item “quốc dân”, không thể thiếu trong tủ đồ của mọi người. Với chất liệu denim bền chắc, khả năng giữ form tốt, quần jean phù hợp với nhiều phong cách từ cá tính, năng động đến lịch sự. Dễ dàng phối với áo thun, sơ mi, hoodie hay áo khoác, thích hợp cho cả đi làm, đi học lẫn đi chơi.', 'jean2_thumb1.jpg', 'DM002', 1, 0, 0, 10, 'SP009', 1, 'L008', 0),
(35, 'SP009A34', 'Quần Jean', 'Đen', '34', 450000, 'Quần jean là item “quốc dân”, không thể thiếu trong tủ đồ của mọi người. Với chất liệu denim bền chắc, khả năng giữ form tốt, quần jean phù hợp với nhiều phong cách từ cá tính, năng động đến lịch sự. Dễ dàng phối với áo thun, sơ mi, hoodie hay áo khoác, thích hợp cho cả đi làm, đi học lẫn đi chơi.', 'jean2_thumb1.jpg', 'DM002', 1, 0, 0, 10, 'SP009', 1, 'L008', 0),
(36, 'SP010A28', 'Quần Jean 2', 'Xanh', '28', 420000, 'Quần jean là item “quốc dân”, không thể thiếu trong tủ đồ của mọi người. Với chất liệu denim bền chắc, khả năng giữ form tốt, quần jean phù hợp với nhiều phong cách từ cá tính, năng động đến lịch sự. Dễ dàng phối với áo thun, sơ mi, hoodie hay áo khoác, thích hợp cho cả đi làm, đi học lẫn đi chơi.', 'jean2.jpg', 'DM002', 0, 1, 0, 10, 'SP010', 1, 'L008', 0),
(37, 'SP010A29', 'Quần Jean 2', 'Xanh', '29', 420000, 'Quần jean là item “quốc dân”, không thể thiếu trong tủ đồ của mọi người. Với chất liệu denim bền chắc, khả năng giữ form tốt, quần jean phù hợp với nhiều phong cách từ cá tính, năng động đến lịch sự. Dễ dàng phối với áo thun, sơ mi, hoodie hay áo khoác, thích hợp cho cả đi làm, đi học lẫn đi chơi.', 'jean2.jpg', 'DM002', 0, 1, 0, 10, 'SP010', 1, 'L008', 0),
(38, 'SP010A30', 'Quần Jean 2', 'Xanh', '30', 420000, 'Quần jean là item “quốc dân”, không thể thiếu trong tủ đồ của mọi người. Với chất liệu denim bền chắc, khả năng giữ form tốt, quần jean phù hợp với nhiều phong cách từ cá tính, năng động đến lịch sự. Dễ dàng phối với áo thun, sơ mi, hoodie hay áo khoác, thích hợp cho cả đi làm, đi học lẫn đi chơi.', 'jean2.jpg', 'DM002', 0, 1, 0, 10, 'SP010', 1, 'L008', 0),
(39, 'SP010A31', 'Quần Jean 2', 'Xanh', '31', 420000, 'Quần jean là item “quốc dân”, không thể thiếu trong tủ đồ của mọi người. Với chất liệu denim bền chắc, khả năng giữ form tốt, quần jean phù hợp với nhiều phong cách từ cá tính, năng động đến lịch sự. Dễ dàng phối với áo thun, sơ mi, hoodie hay áo khoác, thích hợp cho cả đi làm, đi học lẫn đi chơi.', 'jean2.jpg', 'DM002', 0, 1, 0, 10, 'SP010', 1, 'L008', 0),
(40, 'SP010A32', 'Quần Jean 2', 'Xanh', '32', 420000, 'Quần jean là item “quốc dân”, không thể thiếu trong tủ đồ của mọi người. Với chất liệu denim bền chắc, khả năng giữ form tốt, quần jean phù hợp với nhiều phong cách từ cá tính, năng động đến lịch sự. Dễ dàng phối với áo thun, sơ mi, hoodie hay áo khoác, thích hợp cho cả đi làm, đi học lẫn đi chơi.', 'jean2.jpg', 'DM002', 0, 1, 0, 10, 'SP010', 1, 'L008', 0),
(41, 'SP010A33', 'Quần Jean 2', 'Xanh', '33', 420000, 'Quần jean là item “quốc dân”, không thể thiếu trong tủ đồ của mọi người. Với chất liệu denim bền chắc, khả năng giữ form tốt, quần jean phù hợp với nhiều phong cách từ cá tính, năng động đến lịch sự. Dễ dàng phối với áo thun, sơ mi, hoodie hay áo khoác, thích hợp cho cả đi làm, đi học lẫn đi chơi.', 'jean2.jpg', 'DM002', 0, 1, 0, 10, 'SP010', 1, 'L008', 0),
(42, 'SP010A34', 'Quần Jean 2', 'Xanh', '34', 420000, 'Quần jean là item “quốc dân”, không thể thiếu trong tủ đồ của mọi người. Với chất liệu denim bền chắc, khả năng giữ form tốt, quần jean phù hợp với nhiều phong cách từ cá tính, năng động đến lịch sự. Dễ dàng phối với áo thun, sơ mi, hoodie hay áo khoác, thích hợp cho cả đi làm, đi học lẫn đi chơi.', 'jean2.jpg', 'DM002', 0, 1, 0, 10, 'SP010', 1, 'L008', 0),
(43, 'SP011A28', 'Quần Short', 'Be', '28', 200000, 'Quần short là lựa chọn lý tưởng cho những ngày hè năng động, giúp bạn tự do vận động và cảm giác thoải mái tối đa. Được may từ chất liệu cotton, thun hoặc kaki mỏng nhẹ, quần short thích hợp mặc đi chơi, dạo phố, đi biển hoặc các hoạt động thể thao. Thiết kế đa dạng từ basic đến cá tính, dễ phối cùng áo thun, áo sơ mi hoặc áo polo.', 'short4_thumb1.jpg', 'DM002', 0, 1, 0, 10, 'SP011', 1, 'L006', 0),
(44, 'SP011A29', 'Quần Short', 'Be', '29', 200000, 'Quần short là lựa chọn lý tưởng cho những ngày hè năng động, giúp bạn tự do vận động và cảm giác thoải mái tối đa. Được may từ chất liệu cotton, thun hoặc kaki mỏng nhẹ, quần short thích hợp mặc đi chơi, dạo phố, đi biển hoặc các hoạt động thể thao. Thiết kế đa dạng từ basic đến cá tính, dễ phối cùng áo thun, áo sơ mi hoặc áo polo.', 'short4_thumb1.jpg', 'DM002', 0, 1, 0, 10, 'SP011', 1, 'L006', 0),
(45, 'SP011A30', 'Quần Short', 'Be', '30', 200000, 'Quần short là lựa chọn lý tưởng cho những ngày hè năng động, giúp bạn tự do vận động và cảm giác thoải mái tối đa. Được may từ chất liệu cotton, thun hoặc kaki mỏng nhẹ, quần short thích hợp mặc đi chơi, dạo phố, đi biển hoặc các hoạt động thể thao. Thiết kế đa dạng từ basic đến cá tính, dễ phối cùng áo thun, áo sơ mi hoặc áo polo.', 'short4_thumb1.jpg', 'DM002', 0, 1, 0, 10, 'SP011', 1, 'L006', 0),
(46, 'SP011A31', 'Quần Short', 'Be', '31', 200000, 'Quần short là lựa chọn lý tưởng cho những ngày hè năng động, giúp bạn tự do vận động và cảm giác thoải mái tối đa. Được may từ chất liệu cotton, thun hoặc kaki mỏng nhẹ, quần short thích hợp mặc đi chơi, dạo phố, đi biển hoặc các hoạt động thể thao. Thiết kế đa dạng từ basic đến cá tính, dễ phối cùng áo thun, áo sơ mi hoặc áo polo.', 'short4_thumb1.jpg', 'DM002', 0, 1, 0, 10, 'SP011', 1, 'L006', 0),
(47, 'SP011A32', 'Quần Short', 'Be', '32', 200000, 'Quần short là lựa chọn lý tưởng cho những ngày hè năng động, giúp bạn tự do vận động và cảm giác thoải mái tối đa. Được may từ chất liệu cotton, thun hoặc kaki mỏng nhẹ, quần short thích hợp mặc đi chơi, dạo phố, đi biển hoặc các hoạt động thể thao. Thiết kế đa dạng từ basic đến cá tính, dễ phối cùng áo thun, áo sơ mi hoặc áo polo.', 'short4_thumb1.jpg', 'DM002', 0, 1, 0, 9, 'SP011', 1, 'L006', 0),
(48, 'SP011A33', 'Quần Short', 'Be', '33', 200000, 'Quần short là lựa chọn lý tưởng cho những ngày hè năng động, giúp bạn tự do vận động và cảm giác thoải mái tối đa. Được may từ chất liệu cotton, thun hoặc kaki mỏng nhẹ, quần short thích hợp mặc đi chơi, dạo phố, đi biển hoặc các hoạt động thể thao. Thiết kế đa dạng từ basic đến cá tính, dễ phối cùng áo thun, áo sơ mi hoặc áo polo.', 'short4_thumb1.jpg', 'DM002', 0, 1, 0, 10, 'SP011', 1, 'L006', 0),
(49, 'SP011A34', 'Quần Short', 'Be', '34', 200000, 'Quần short là lựa chọn lý tưởng cho những ngày hè năng động, giúp bạn tự do vận động và cảm giác thoải mái tối đa. Được may từ chất liệu cotton, thun hoặc kaki mỏng nhẹ, quần short thích hợp mặc đi chơi, dạo phố, đi biển hoặc các hoạt động thể thao. Thiết kế đa dạng từ basic đến cá tính, dễ phối cùng áo thun, áo sơ mi hoặc áo polo.', 'short4_thumb1.jpg', 'DM002', 0, 1, 0, 10, 'SP011', 1, 'L006', 0),
(50, 'SP012A28', 'Quần Short 2', 'Đen', '28', 220000, 'Quần short là lựa chọn lý tưởng cho những ngày hè năng động, giúp bạn tự do vận động và cảm giác thoải mái tối đa. Được may từ chất liệu cotton, thun hoặc kaki mỏng nhẹ, quần short thích hợp mặc đi chơi, dạo phố, đi biển hoặc các hoạt động thể thao. Thiết kế đa dạng từ basic đến cá tính, dễ phối cùng áo thun, áo sơ mi hoặc áo polo.', 'short2.jpg', 'DM002', 0, 0, 0, 10, 'SP012', 1, 'L006', 0),
(51, 'SP012A29', 'Quần Short 2', 'Đen', '29', 220000, 'Quần short là lựa chọn lý tưởng cho những ngày hè năng động, giúp bạn tự do vận động và cảm giác thoải mái tối đa. Được may từ chất liệu cotton, thun hoặc kaki mỏng nhẹ, quần short thích hợp mặc đi chơi, dạo phố, đi biển hoặc các hoạt động thể thao. Thiết kế đa dạng từ basic đến cá tính, dễ phối cùng áo thun, áo sơ mi hoặc áo polo.', 'short2.jpg', 'DM002', 0, 0, 0, 10, 'SP012', 1, 'L006', 0),
(52, 'SP012A30', 'Quần Short 2', 'Đen', '30', 220000, 'Quần short là lựa chọn lý tưởng cho những ngày hè năng động, giúp bạn tự do vận động và cảm giác thoải mái tối đa. Được may từ chất liệu cotton, thun hoặc kaki mỏng nhẹ, quần short thích hợp mặc đi chơi, dạo phố, đi biển hoặc các hoạt động thể thao. Thiết kế đa dạng từ basic đến cá tính, dễ phối cùng áo thun, áo sơ mi hoặc áo polo.', 'short2.jpg', 'DM002', 0, 0, 0, 10, 'SP012', 1, 'L006', 0),
(53, 'SP012A31', 'Quần Short 2', 'Đen', '31', 220000, 'Quần short là lựa chọn lý tưởng cho những ngày hè năng động, giúp bạn tự do vận động và cảm giác thoải mái tối đa. Được may từ chất liệu cotton, thun hoặc kaki mỏng nhẹ, quần short thích hợp mặc đi chơi, dạo phố, đi biển hoặc các hoạt động thể thao. Thiết kế đa dạng từ basic đến cá tính, dễ phối cùng áo thun, áo sơ mi hoặc áo polo.', 'short2.jpg', 'DM002', 0, 0, 0, 10, 'SP012', 1, 'L006', 0),
(54, 'SP012A32', 'Quần Short 2', 'Đen', '32', 220000, 'Quần short là lựa chọn lý tưởng cho những ngày hè năng động, giúp bạn tự do vận động và cảm giác thoải mái tối đa. Được may từ chất liệu cotton, thun hoặc kaki mỏng nhẹ, quần short thích hợp mặc đi chơi, dạo phố, đi biển hoặc các hoạt động thể thao. Thiết kế đa dạng từ basic đến cá tính, dễ phối cùng áo thun, áo sơ mi hoặc áo polo.', 'short2.jpg', 'DM002', 0, 0, 0, 10, 'SP012', 1, 'L006', 0),
(55, 'SP012A33', 'Quần Short 2', 'Đen', '33', 220000, 'Quần short là lựa chọn lý tưởng cho những ngày hè năng động, giúp bạn tự do vận động và cảm giác thoải mái tối đa. Được may từ chất liệu cotton, thun hoặc kaki mỏng nhẹ, quần short thích hợp mặc đi chơi, dạo phố, đi biển hoặc các hoạt động thể thao. Thiết kế đa dạng từ basic đến cá tính, dễ phối cùng áo thun, áo sơ mi hoặc áo polo.', 'short2.jpg', 'DM002', 0, 0, 0, 10, 'SP012', 1, 'L006', 0),
(56, 'SP012A34', 'Quần Short 2', 'Đen', '34', 220000, 'Quần short là lựa chọn lý tưởng cho những ngày hè năng động, giúp bạn tự do vận động và cảm giác thoải mái tối đa. Được may từ chất liệu cotton, thun hoặc kaki mỏng nhẹ, quần short thích hợp mặc đi chơi, dạo phố, đi biển hoặc các hoạt động thể thao. Thiết kế đa dạng từ basic đến cá tính, dễ phối cùng áo thun, áo sơ mi hoặc áo polo.', 'short2.jpg', 'DM002', 0, 0, 0, 10, 'SP012', 1, 'L006', 0),
(57, 'SP013A28', 'Quần Kaki', 'Nâu', '28', 270000, 'Quần kaki nổi bật với chất liệu bền, đứng form nhưng vẫn tạo cảm giác dễ chịu khi mặc. Thiết kế đơn giản, lịch sự, phù hợp cho cả môi trường công sở lẫn đi chơi, dạo phố. Quần kaki dễ phối với nhiều loại áo, giúp bạn biến hóa linh hoạt từ phong cách trẻ trung, năng động đến lịch lãm, chững chạc.', 'kaki3_thumb2.jpg', 'DM002', 0, 0, 0, 10, 'SP013', 1, 'L007', 0),
(58, 'SP013A29', 'Quần Kaki', 'Nâu', '29', 270000, 'Quần kaki nổi bật với chất liệu bền, đứng form nhưng vẫn tạo cảm giác dễ chịu khi mặc. Thiết kế đơn giản, lịch sự, phù hợp cho cả môi trường công sở lẫn đi chơi, dạo phố. Quần kaki dễ phối với nhiều loại áo, giúp bạn biến hóa linh hoạt từ phong cách trẻ trung, năng động đến lịch lãm, chững chạc.', 'kaki3_thumb2.jpg', 'DM002', 0, 0, 0, 10, 'SP013', 1, 'L007', 0),
(59, 'SP013A30', 'Quần Kaki', 'Nâu', '30', 270000, 'Quần kaki nổi bật với chất liệu bền, đứng form nhưng vẫn tạo cảm giác dễ chịu khi mặc. Thiết kế đơn giản, lịch sự, phù hợp cho cả môi trường công sở lẫn đi chơi, dạo phố. Quần kaki dễ phối với nhiều loại áo, giúp bạn biến hóa linh hoạt từ phong cách trẻ trung, năng động đến lịch lãm, chững chạc.', 'kaki3_thumb2.jpg', 'DM002', 0, 0, 0, 10, 'SP013', 1, 'L007', 0),
(60, 'SP013A31', 'Quần Kaki', 'Nâu', '31', 270000, 'Quần kaki nổi bật với chất liệu bền, đứng form nhưng vẫn tạo cảm giác dễ chịu khi mặc. Thiết kế đơn giản, lịch sự, phù hợp cho cả môi trường công sở lẫn đi chơi, dạo phố. Quần kaki dễ phối với nhiều loại áo, giúp bạn biến hóa linh hoạt từ phong cách trẻ trung, năng động đến lịch lãm, chững chạc.', 'kaki3_thumb2.jpg', 'DM002', 0, 0, 0, 10, 'SP013', 1, 'L007', 0),
(61, 'SP013A32', 'Quần Kaki', 'Nâu', '32', 270000, 'Quần kaki nổi bật với chất liệu bền, đứng form nhưng vẫn tạo cảm giác dễ chịu khi mặc. Thiết kế đơn giản, lịch sự, phù hợp cho cả môi trường công sở lẫn đi chơi, dạo phố. Quần kaki dễ phối với nhiều loại áo, giúp bạn biến hóa linh hoạt từ phong cách trẻ trung, năng động đến lịch lãm, chững chạc.', 'kaki3_thumb2.jpg', 'DM002', 0, 0, 0, 10, 'SP013', 1, 'L007', 0),
(62, 'SP013A33', 'Quần Kaki', 'Nâu', '33', 270000, 'Quần kaki nổi bật với chất liệu bền, đứng form nhưng vẫn tạo cảm giác dễ chịu khi mặc. Thiết kế đơn giản, lịch sự, phù hợp cho cả môi trường công sở lẫn đi chơi, dạo phố. Quần kaki dễ phối với nhiều loại áo, giúp bạn biến hóa linh hoạt từ phong cách trẻ trung, năng động đến lịch lãm, chững chạc.', 'kaki3_thumb2.jpg', 'DM002', 0, 0, 0, 10, 'SP013', 1, 'L007', 0),
(63, 'SP013A34', 'Quần Kaki', 'Nâu', '34', 270000, 'Quần kaki nổi bật với chất liệu bền, đứng form nhưng vẫn tạo cảm giác dễ chịu khi mặc. Thiết kế đơn giản, lịch sự, phù hợp cho cả môi trường công sở lẫn đi chơi, dạo phố. Quần kaki dễ phối với nhiều loại áo, giúp bạn biến hóa linh hoạt từ phong cách trẻ trung, năng động đến lịch lãm, chững chạc.', 'kaki3_thumb2.jpg', 'DM002', 0, 0, 0, 10, 'SP013', 1, 'L007', 0),
(64, 'SP014A28', 'Quần Kaki 2', 'Trắng', '28', 285000, 'Quần kaki nổi bật với chất liệu bền, đứng form nhưng vẫn tạo cảm giác dễ chịu khi mặc. Thiết kế đơn giản, lịch sự, phù hợp cho cả môi trường công sở lẫn đi chơi, dạo phố. Quần kaki dễ phối với nhiều loại áo, giúp bạn biến hóa linh hoạt từ phong cách trẻ trung, năng động đến lịch lãm, chững chạc.', 'kaki1_thumb2.jpg', 'DM002', 0, 1, 0, 10, 'SP014', 1, 'L007', 0),
(65, 'SP014A29', 'Quần Kaki 2', 'Trắng', '29', 285000, 'Quần kaki nổi bật với chất liệu bền, đứng form nhưng vẫn tạo cảm giác dễ chịu khi mặc. Thiết kế đơn giản, lịch sự, phù hợp cho cả môi trường công sở lẫn đi chơi, dạo phố. Quần kaki dễ phối với nhiều loại áo, giúp bạn biến hóa linh hoạt từ phong cách trẻ trung, năng động đến lịch lãm, chững chạc.', 'kaki1_thumb2.jpg', 'DM002', 0, 1, 0, 10, 'SP014', 1, 'L007', 0),
(66, 'SP014A30', 'Quần Kaki 2', 'Trắng', '30', 285000, 'Quần kaki nổi bật với chất liệu bền, đứng form nhưng vẫn tạo cảm giác dễ chịu khi mặc. Thiết kế đơn giản, lịch sự, phù hợp cho cả môi trường công sở lẫn đi chơi, dạo phố. Quần kaki dễ phối với nhiều loại áo, giúp bạn biến hóa linh hoạt từ phong cách trẻ trung, năng động đến lịch lãm, chững chạc.', 'kaki1_thumb2.jpg', 'DM002', 0, 1, 0, 10, 'SP014', 1, 'L007', 0),
(67, 'SP014A31', 'Quần Kaki 2', 'Trắng', '31', 285000, 'Quần kaki nổi bật với chất liệu bền, đứng form nhưng vẫn tạo cảm giác dễ chịu khi mặc. Thiết kế đơn giản, lịch sự, phù hợp cho cả môi trường công sở lẫn đi chơi, dạo phố. Quần kaki dễ phối với nhiều loại áo, giúp bạn biến hóa linh hoạt từ phong cách trẻ trung, năng động đến lịch lãm, chững chạc.', 'kaki1_thumb2.jpg', 'DM002', 0, 1, 0, 10, 'SP014', 1, 'L007', 0),
(68, 'SP014A32', 'Quần Kaki 2', 'Trắng', '32', 285000, 'Quần kaki nổi bật với chất liệu bền, đứng form nhưng vẫn tạo cảm giác dễ chịu khi mặc. Thiết kế đơn giản, lịch sự, phù hợp cho cả môi trường công sở lẫn đi chơi, dạo phố. Quần kaki dễ phối với nhiều loại áo, giúp bạn biến hóa linh hoạt từ phong cách trẻ trung, năng động đến lịch lãm, chững chạc.', 'kaki1_thumb2.jpg', 'DM002', 0, 1, 0, 10, 'SP014', 1, 'L007', 0),
(69, 'SP014A33', 'Quần Kaki 2', 'Trắng', '33', 285000, 'Quần kaki nổi bật với chất liệu bền, đứng form nhưng vẫn tạo cảm giác dễ chịu khi mặc. Thiết kế đơn giản, lịch sự, phù hợp cho cả môi trường công sở lẫn đi chơi, dạo phố. Quần kaki dễ phối với nhiều loại áo, giúp bạn biến hóa linh hoạt từ phong cách trẻ trung, năng động đến lịch lãm, chững chạc.', 'kaki1_thumb2.jpg', 'DM002', 0, 1, 0, 10, 'SP014', 1, 'L007', 0),
(70, 'SP014A34', 'Quần Kaki 2', 'Trắng', '34', 285000, 'Quần kaki nổi bật với chất liệu bền, đứng form nhưng vẫn tạo cảm giác dễ chịu khi mặc. Thiết kế đơn giản, lịch sự, phù hợp cho cả môi trường công sở lẫn đi chơi, dạo phố. Quần kaki dễ phối với nhiều loại áo, giúp bạn biến hóa linh hoạt từ phong cách trẻ trung, năng động đến lịch lãm, chững chạc.', 'kaki1_thumb2.jpg', 'DM002', 0, 1, 0, 10, 'SP014', 1, 'L007', 0),
(71, 'SP015A28', 'Quần Tây', 'Đen', '28', 385000, 'Quần tây là lựa chọn hoàn hảo cho phong cách lịch lãm, sang trọng. Chất liệu vải cao cấp, mềm mịn, giúp quần đứng form, tôn dáng và luôn tạo cảm giác thoải mái suốt ngày dài. Thiết kế đơn giản, tinh tế, dễ phối cùng áo sơ mi, áo vest hoặc áo polo, phù hợp cho môi trường công sở, sự kiện trang trọng hay gặp gỡ đối tác.', 'trouser2_thumb1.jpg', 'DM002', 0, 1, 0, 385000, 'SP015', 1, 'L005', 0),
(72, 'SP015A29', 'Quần Tây', 'Đen', '29', 385000, 'Quần tây là lựa chọn hoàn hảo cho phong cách lịch lãm, sang trọng. Chất liệu vải cao cấp, mềm mịn, giúp quần đứng form, tôn dáng và luôn tạo cảm giác thoải mái suốt ngày dài. Thiết kế đơn giản, tinh tế, dễ phối cùng áo sơ mi, áo vest hoặc áo polo, phù hợp cho môi trường công sở, sự kiện trang trọng hay gặp gỡ đối tác.', 'trouser2_thumb1.jpg', 'DM002', 0, 1, 0, 385000, 'SP015', 1, 'L005', 0),
(73, 'SP015A30', 'Quần Tây', 'Đen', '30', 385000, 'Quần tây là lựa chọn hoàn hảo cho phong cách lịch lãm, sang trọng. Chất liệu vải cao cấp, mềm mịn, giúp quần đứng form, tôn dáng và luôn tạo cảm giác thoải mái suốt ngày dài. Thiết kế đơn giản, tinh tế, dễ phối cùng áo sơ mi, áo vest hoặc áo polo, phù hợp cho môi trường công sở, sự kiện trang trọng hay gặp gỡ đối tác.', 'trouser2_thumb1.jpg', 'DM002', 0, 1, 0, 385000, 'SP015', 1, 'L005', 0),
(74, 'SP015A31', 'Quần Tây', 'Đen', '31', 385000, 'Quần tây là lựa chọn hoàn hảo cho phong cách lịch lãm, sang trọng. Chất liệu vải cao cấp, mềm mịn, giúp quần đứng form, tôn dáng và luôn tạo cảm giác thoải mái suốt ngày dài. Thiết kế đơn giản, tinh tế, dễ phối cùng áo sơ mi, áo vest hoặc áo polo, phù hợp cho môi trường công sở, sự kiện trang trọng hay gặp gỡ đối tác.', 'trouser2_thumb1.jpg', 'DM002', 0, 1, 0, 385000, 'SP015', 1, 'L005', 0),
(75, 'SP015A32', 'Quần Tây', 'Đen', '32', 385000, 'Quần tây là lựa chọn hoàn hảo cho phong cách lịch lãm, sang trọng. Chất liệu vải cao cấp, mềm mịn, giúp quần đứng form, tôn dáng và luôn tạo cảm giác thoải mái suốt ngày dài. Thiết kế đơn giản, tinh tế, dễ phối cùng áo sơ mi, áo vest hoặc áo polo, phù hợp cho môi trường công sở, sự kiện trang trọng hay gặp gỡ đối tác.', 'trouser2_thumb1.jpg', 'DM002', 0, 1, 0, 385000, 'SP015', 1, 'L005', 0),
(76, 'SP015A33', 'Quần Tây', 'Đen', '33', 385000, 'Quần tây là lựa chọn hoàn hảo cho phong cách lịch lãm, sang trọng. Chất liệu vải cao cấp, mềm mịn, giúp quần đứng form, tôn dáng và luôn tạo cảm giác thoải mái suốt ngày dài. Thiết kế đơn giản, tinh tế, dễ phối cùng áo sơ mi, áo vest hoặc áo polo, phù hợp cho môi trường công sở, sự kiện trang trọng hay gặp gỡ đối tác.', 'trouser2_thumb1.jpg', 'DM002', 0, 1, 0, 385000, 'SP015', 1, 'L005', 0),
(77, 'SP015A34', 'Quần Tây', 'Đen', '34', 385000, 'Quần tây là lựa chọn hoàn hảo cho phong cách lịch lãm, sang trọng. Chất liệu vải cao cấp, mềm mịn, giúp quần đứng form, tôn dáng và luôn tạo cảm giác thoải mái suốt ngày dài. Thiết kế đơn giản, tinh tế, dễ phối cùng áo sơ mi, áo vest hoặc áo polo, phù hợp cho môi trường công sở, sự kiện trang trọng hay gặp gỡ đối tác.', 'trouser2_thumb1.jpg', 'DM002', 0, 1, 0, 385000, 'SP015', 1, 'L005', 0),
(78, 'SP016A28', 'Quần Tây 2', 'Trắng', '28', 350000, 'Quần tây là lựa chọn hoàn hảo cho phong cách lịch lãm, sang trọng. Chất liệu vải cao cấp, mềm mịn, giúp quần đứng form, tôn dáng và luôn tạo cảm giác thoải mái suốt ngày dài. Thiết kế đơn giản, tinh tế, dễ phối cùng áo sơ mi, áo vest hoặc áo polo, phù hợp cho môi trường công sở, sự kiện trang trọng hay gặp gỡ đối tác.', 'trouser1.jpg', 'DM002', 1, 0, 0, 10, 'SP016', 1, 'L005', 0),
(79, 'SP016A29', 'Quần Tây 2', 'Trắng', '29', 350000, 'Quần tây là lựa chọn hoàn hảo cho phong cách lịch lãm, sang trọng. Chất liệu vải cao cấp, mềm mịn, giúp quần đứng form, tôn dáng và luôn tạo cảm giác thoải mái suốt ngày dài. Thiết kế đơn giản, tinh tế, dễ phối cùng áo sơ mi, áo vest hoặc áo polo, phù hợp cho môi trường công sở, sự kiện trang trọng hay gặp gỡ đối tác.', 'trouser1.jpg', 'DM002', 1, 0, 0, 9, 'SP016', 1, 'L005', 0),
(80, 'SP016A30', 'Quần Tây 2', 'Trắng', '30', 350000, 'Quần tây là lựa chọn hoàn hảo cho phong cách lịch lãm, sang trọng. Chất liệu vải cao cấp, mềm mịn, giúp quần đứng form, tôn dáng và luôn tạo cảm giác thoải mái suốt ngày dài. Thiết kế đơn giản, tinh tế, dễ phối cùng áo sơ mi, áo vest hoặc áo polo, phù hợp cho môi trường công sở, sự kiện trang trọng hay gặp gỡ đối tác.', 'trouser1.jpg', 'DM002', 1, 0, 0, 10, 'SP016', 1, 'L005', 0),
(81, 'SP016A31', 'Quần Tây 2', 'Trắng', '31', 350000, 'Quần tây là lựa chọn hoàn hảo cho phong cách lịch lãm, sang trọng. Chất liệu vải cao cấp, mềm mịn, giúp quần đứng form, tôn dáng và luôn tạo cảm giác thoải mái suốt ngày dài. Thiết kế đơn giản, tinh tế, dễ phối cùng áo sơ mi, áo vest hoặc áo polo, phù hợp cho môi trường công sở, sự kiện trang trọng hay gặp gỡ đối tác.', 'trouser1.jpg', 'DM002', 1, 0, 0, 10, 'SP016', 1, 'L005', 0),
(82, 'SP016A32', 'Quần Tây 2', 'Trắng', '32', 350000, 'Quần tây là lựa chọn hoàn hảo cho phong cách lịch lãm, sang trọng. Chất liệu vải cao cấp, mềm mịn, giúp quần đứng form, tôn dáng và luôn tạo cảm giác thoải mái suốt ngày dài. Thiết kế đơn giản, tinh tế, dễ phối cùng áo sơ mi, áo vest hoặc áo polo, phù hợp cho môi trường công sở, sự kiện trang trọng hay gặp gỡ đối tác.', 'trouser1.jpg', 'DM002', 1, 0, 0, 10, 'SP016', 1, 'L005', 0),
(83, 'SP016A33', 'Quần Tây 2', 'Trắng', '33', 350000, 'Quần tây là lựa chọn hoàn hảo cho phong cách lịch lãm, sang trọng. Chất liệu vải cao cấp, mềm mịn, giúp quần đứng form, tôn dáng và luôn tạo cảm giác thoải mái suốt ngày dài. Thiết kế đơn giản, tinh tế, dễ phối cùng áo sơ mi, áo vest hoặc áo polo, phù hợp cho môi trường công sở, sự kiện trang trọng hay gặp gỡ đối tác.', 'trouser1.jpg', 'DM002', 1, 0, 0, 10, 'SP016', 1, 'L005', 0),
(84, 'SP016A34', 'Quần Tây 2', 'Trắng', '34', 350000, 'Quần tây là lựa chọn hoàn hảo cho phong cách lịch lãm, sang trọng. Chất liệu vải cao cấp, mềm mịn, giúp quần đứng form, tôn dáng và luôn tạo cảm giác thoải mái suốt ngày dài. Thiết kế đơn giản, tinh tế, dễ phối cùng áo sơ mi, áo vest hoặc áo polo, phù hợp cho môi trường công sở, sự kiện trang trọng hay gặp gỡ đối tác.', 'trouser1.jpg', 'DM002', 1, 0, 0, 10, 'SP016', 1, 'L005', 0),
(85, 'SP017A', 'Nón', 'Trắng Xanh', 'Freesize', 45000, 'Nón là phụ kiện không chỉ giúp bảo vệ bạn khỏi nắng, mưa mà còn tạo điểm nhấn cho phong cách thời trang cá nhân. Với đa dạng kiểu dáng như nón lưỡi trai trẻ trung, bucket cá tính hay nón rộng vành thanh lịch, mỗi chiếc nón đều dễ dàng phối với nhiều trang phục, mang lại vẻ ngoài năng động và thời thượng cho người sử dụng.', 'hat2.jpg', 'DM003', 1, 0, 0, 10, 'SP017', 1, 'L010', 0),
(86, 'SP018A', 'Nón 2', 'Xanh', 'Freesize', 40000, 'Nón là phụ kiện không chỉ giúp bảo vệ bạn khỏi nắng, mưa mà còn tạo điểm nhấn cho phong cách thời trang cá nhân. Với đa dạng kiểu dáng như nón lưỡi trai trẻ trung, bucket cá tính hay nón rộng vành thanh lịch, mỗi chiếc nón đều dễ dàng phối với nhiều trang phục, mang lại vẻ ngoài năng động và thời thượng cho người sử dụng.', 'hat3_thumb1.jpg', 'DM003', 0, 0, 0, 10, 'SP018', 1, 'L010', 0),
(87, 'SP019A', 'Cà vạt', 'Xanh Đỏ', 'Freesize', 50000, 'Cà vạt và nơ là những phụ kiện không thể thiếu cho các quý ông khi tham dự sự kiện, hội họp hay những dịp đặc biệt. Được làm từ chất liệu vải cao cấp, đa dạng về màu sắc và họa tiết, cà vạt và nơ giúp tôn lên vẻ lịch lãm, sang trọng và chuyên nghiệp. Dễ dàng phối cùng áo sơ mi, vest, tạo điểm nhấn nổi bật cho tổng thể trang phục.', 'tie2_thumb1.jpg', 'DM003', 0, 0, 0, 10, 'SP019', 1, 'L011', 0),
(88, 'SP020A', 'Nơ', 'Đen', 'Freesize', 35000, 'Cà vạt và nơ là những phụ kiện không thể thiếu cho các quý ông khi tham dự sự kiện, hội họp hay những dịp đặc biệt. Được làm từ chất liệu vải cao cấp, đa dạng về màu sắc và họa tiết, cà vạt và nơ giúp tôn lên vẻ lịch lãm, sang trọng và chuyên nghiệp. Dễ dàng phối cùng áo sơ mi, vest, tạo điểm nhấn nổi bật cho tổng thể trang phục.', 'bow2_thumb1.jpg', 'DM003', 0, 0, 0, 9, 'SP020', 1, 'L011', 0),
(89, 'SP021A', 'Thắt Lưng', 'Đen', 'Freesize', 65000, 'Thắt lưng là phụ kiện vừa mang tính thẩm mỹ, vừa đóng vai trò cố định trang phục, giúp tổng thể thêm phần gọn gàng và chỉn chu. Chất liệu đa dạng như da thật, da tổng hợp hay vải dù, thiết kế từ cổ điển đến hiện đại, thắt lưng dễ phối với quần tây, jean, kaki… Phù hợp cho cả đi làm, đi chơi hoặc dự tiệc.', 'belt1.jpg', 'DM003', 0, 0, 0, 10, 'SP021', 1, 'L012', 0),
(90, 'SP022A', 'Ví', 'Nâu', 'Freesize', 70000, 'Ví là vật dụng thiết yếu, giúp bạn lưu giữ tiền mặt, thẻ và giấy tờ quan trọng một cách ngăn nắp, an toàn. Được chế tác từ các loại da cao cấp hoặc vải bền đẹp, ví không chỉ tiện lợi mà còn thể hiện đẳng cấp và gu thẩm mỹ riêng của người dùng. Thiết kế nhỏ gọn, nhiều ngăn tiện lợi, dễ dàng mang theo mọi lúc mọi nơi.', 'wallet1_thumb1.jpg', 'DM003', 0, 1, 0, 10, 'SP022', 1, 'L009', 0),
(91, 'SP023AS', 'Áo Thun Cổ Tròn', 'Trắng sọc ', 'S', 200000, 'Áo thun cổ tròn là lựa chọn kinh điển phù hợp với mọi lứa tuổi và giới tính. Thiết kế đơn giản, ôm nhẹ cơ thể, mang lại cảm giác thoải mái khi vận động. Chất liệu cotton cao cấp giúp áo thấm hút mồ hôi tốt, dễ phối cùng quần jean, quần short hoặc chân váy cho nhiều phong cách khác nhau.', 'tshirt4.jpg', 'DM001', 0, 1, 0, 10, 'SP023', 1, 'L001', 0),
(92, 'SP023AM', 'Áo Thun Cổ Tròn', 'Trắng sọc ', 'M', 200000, 'Áo thun cổ tròn là lựa chọn kinh điển phù hợp với mọi lứa tuổi và giới tính. Thiết kế đơn giản, ôm nhẹ cơ thể, mang lại cảm giác thoải mái khi vận động. Chất liệu cotton cao cấp giúp áo thấm hút mồ hôi tốt, dễ phối cùng quần jean, quần short hoặc chân váy cho nhiều phong cách khác nhau.', 'tshirt4.jpg', 'DM001', 0, 1, 0, 10, 'SP023', 1, 'L001', 0),
(93, 'SP023AL', 'Áo Thun Cổ Tròn', 'Trắng sọc ', 'L', 200000, 'Áo thun cổ tròn là lựa chọn kinh điển phù hợp với mọi lứa tuổi và giới tính. Thiết kế đơn giản, ôm nhẹ cơ thể, mang lại cảm giác thoải mái khi vận động. Chất liệu cotton cao cấp giúp áo thấm hút mồ hôi tốt, dễ phối cùng quần jean, quần short hoặc chân váy cho nhiều phong cách khác nhau.', 'tshirt4.jpg', 'DM001', 0, 1, 0, 10, 'SP023', 1, 'L001', 0),
(94, 'SP023AXL', 'Áo Thun Cổ Tròn', 'Trắng sọc ', 'XL', 200000, 'Áo thun cổ tròn là lựa chọn kinh điển phù hợp với mọi lứa tuổi và giới tính. Thiết kế đơn giản, ôm nhẹ cơ thể, mang lại cảm giác thoải mái khi vận động. Chất liệu cotton cao cấp giúp áo thấm hút mồ hôi tốt, dễ phối cùng quần jean, quần short hoặc chân váy cho nhiều phong cách khác nhau.', 'tshirt4.jpg', 'DM001', 0, 1, 0, 10, 'SP023', 1, 'L001', 0),
(95, 'SP024AS', 'Áo Thun', 'Đen', 'S', 170000, 'Áo thun cổ tròn là lựa chọn kinh điển phù hợp với mọi lứa tuổi và giới tính. Thiết kế đơn giản, ôm nhẹ cơ thể, mang lại cảm giác thoải mái khi vận động. Chất liệu cotton cao cấp giúp áo thấm hút mồ hôi tốt, dễ phối cùng quần jean, quần short hoặc chân váy cho nhiều phong cách khác nhau.', 'tshirt2.jpg', 'DM001', 0, 0, 1, 10, 'SP024', 1, 'L001', 0),
(96, 'SP024AM', 'Áo Thun', 'Đen', 'M', 170000, 'Áo thun cổ tròn là lựa chọn kinh điển phù hợp với mọi lứa tuổi và giới tính. Thiết kế đơn giản, ôm nhẹ cơ thể, mang lại cảm giác thoải mái khi vận động. Chất liệu cotton cao cấp giúp áo thấm hút mồ hôi tốt, dễ phối cùng quần jean, quần short hoặc chân váy cho nhiều phong cách khác nhau.', 'tshirt2.jpg', 'DM001', 0, 0, 1, 10, 'SP024', 1, 'L001', 0),
(97, 'SP024AL', 'Áo Thun', 'Đen', 'L', 170000, 'Áo thun cổ tròn là lựa chọn kinh điển phù hợp với mọi lứa tuổi và giới tính. Thiết kế đơn giản, ôm nhẹ cơ thể, mang lại cảm giác thoải mái khi vận động. Chất liệu cotton cao cấp giúp áo thấm hút mồ hôi tốt, dễ phối cùng quần jean, quần short hoặc chân váy cho nhiều phong cách khác nhau.', 'tshirt2.jpg', 'DM001', 0, 0, 1, 10, 'SP024', 1, 'L001', 0),
(98, 'SP024AXL', 'Áo Thun', 'Đen', 'XL', 170000, 'Áo thun cổ tròn là lựa chọn kinh điển phù hợp với mọi lứa tuổi và giới tính. Thiết kế đơn giản, ôm nhẹ cơ thể, mang lại cảm giác thoải mái khi vận động. Chất liệu cotton cao cấp giúp áo thấm hút mồ hôi tốt, dễ phối cùng quần jean, quần short hoặc chân váy cho nhiều phong cách khác nhau.', 'tshirt2.jpg', 'DM001', 0, 0, 1, 10, 'SP024', 1, 'L001', 0);

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
(1, 'TK001', 'admin', '$2y$10$wL9zYVBblJDXutrbpDhDHu0p4whNtJ1OBq7GgUH7LM5JzL9f6LFnG', 'KH001', 'admin'),
(2, 'TK002', 'thinh', '$2y$10$e8z7biCo0hIF//KYg.o5P.rqJ3IW5WpSn9srMQiTidI.FzlEoS3CO', 'KH002', 'user'),
(3, 'TK003', 'phat', '$2y$10$kpVWLcyXQ3JdzFT/BAxCYuUBx8WxoASguJ01UzddZ0FTTrV2/hduW', 'KH003', 'user'),
(4, 'TK004', 'lan', '$2y$10$igzmXHbouVNOuLpYgI9xFuJ9xlsiFvq4UOnrAFjWymRp6tJTV5JOy', 'KH004', 'user');

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

--
-- Đang đổ dữ liệu cho bảng `voucher`
--

INSERT INTO `voucher` (`ID`, `MAVOUCHER`, `TENVOUCHER`, `MOTA`, `LOAIVOUCHER`, `GIATRI`, `GIATRIMIN`, `GIATRIMAX`, `SOLUONG`, `SOLUONGSUDUNG`, `NGAYBATDAU`, `NGAYHETHAN`, `TRANGTHAI`, `NGAYTAO`, `NGAYCAPNHAT`) VALUES
(7, 'SALE10', 'Giảm 10%', 'Giảm 10% cho đơn từ 100k', 'percent', 10.00, 100000.00, 30000.00, 100, 0, '2025-08-01 00:00:00', '2025-08-31 23:59:59', 'active', '2025-08-05 22:09:47', '2025-08-06 00:18:28'),
(8, 'GIAM20K', 'Giảm 20k', 'Giảm ngay 20.000đ cho đơn từ 150k', 'fixed', 20000.00, 150000.00, NULL, 50, -3, '2025-08-01 00:00:00', '2025-08-31 23:59:59', 'active', '2025-08-05 22:09:47', '2025-08-06 03:50:00'),
(9, 'FREESHIP', 'Miễn phí vận chuyển', 'Free ship cho đơn từ 200k', 'freeship', 0.00, 200000.00, NULL, 30, 0, '2025-08-01 00:00:00', '2025-08-31 23:59:59', 'active', '2025-08-05 22:09:47', '2025-08-05 22:09:47');

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
-- Chỉ mục cho bảng `chitietdonhang`
--
ALTER TABLE `chitietdonhang`
  ADD PRIMARY KEY (`ID`);

--
-- Chỉ mục cho bảng `chitiethoadon`
--
ALTER TABLE `chitiethoadon`
  ADD PRIMARY KEY (`MASP`,`MAHD`);

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
  ADD KEY `giaohang_ibfk_1` (`MAHD`),
  ADD KEY `FK_giaohang_donhang` (`MADONHANG`);

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
-- AUTO_INCREMENT cho bảng `chitietdonhang`
--
ALTER TABLE `chitietdonhang`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `chuongtrinhkhuyenmai`
--
ALTER TABLE `chuongtrinhkhuyenmai`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `hoadon`
--
ALTER TABLE `hoadon`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `khachhang`
--
ALTER TABLE `khachhang`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `sanpham`
--
ALTER TABLE `sanpham`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT cho bảng `taikhoan`
--
ALTER TABLE `taikhoan`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `voucher`
--
ALTER TABLE `voucher`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `voucher_usage`
--
ALTER TABLE `voucher_usage`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `donhang`
--
ALTER TABLE `donhang`
  ADD CONSTRAINT `donhang_ibfk_1` FOREIGN KEY (`MAVOUCHER`) REFERENCES `voucher` (`MAVOUCHER`);

--
-- Các ràng buộc cho bảng `giaohang`
--
ALTER TABLE `giaohang`
  ADD CONSTRAINT `FK_giaohang_donhang` FOREIGN KEY (`MADONHANG`) REFERENCES `donhang` (`MADONHANG`) ON DELETE CASCADE ON UPDATE CASCADE;

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
