-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 18, 2026 at 08:28 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `clothingstore`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbladmin`
--

CREATE TABLE `tbladmin` (
  `AdminID` int(11) NOT NULL,
  `AdminName` varchar(100) NOT NULL,
  `AdminEmail` varchar(150) NOT NULL,
  `AdminPass` varchar(255) NOT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbladmin`
--

INSERT INTO `tbladmin` (`AdminID`, `AdminName`, `AdminEmail`, `AdminPass`, `CreatedAt`) VALUES
(1, 'Super Admin', 'admin@clothingstore.co.za', 'e88df8596ff8847e232b1e4b1b5ffde2', '2026-06-18 14:11:56'),
(2, 'Jane Sithole', 'jane.s@clothingstore.co.za', 'e88df8596ff8847e232b1e4b1b5ffde2', '2026-06-18 14:11:56'),
(3, 'Thabo Nkosi', 'thabo.n@clothingstore.co.za', 'e88df8596ff8847e232b1e4b1b5ffde2', '2026-06-18 14:11:56'),
(4, 'Priya Pillay', 'priya.p@clothingstore.co.za', 'e88df8596ff8847e232b1e4b1b5ffde2', '2026-06-18 14:11:56'),
(5, 'Marcus Dlamini', 'marcus.d@clothingstore.co.za', 'e88df8596ff8847e232b1e4b1b5ffde2', '2026-06-18 14:11:56');

-- --------------------------------------------------------

--
-- Table structure for table `tblaorder`
--

CREATE TABLE `tblaorder` (
  `OrderID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `ClothesID` int(11) NOT NULL,
  `Quantity` int(11) DEFAULT 1,
  `TotalPrice` decimal(10,2) NOT NULL,
  `OrderDate` datetime DEFAULT current_timestamp(),
  `OrderStatus` enum('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblaorder`
--

INSERT INTO `tblaorder` (`OrderID`, `UserID`, `ClothesID`, `Quantity`, `TotalPrice`, `OrderDate`, `OrderStatus`) VALUES
(1, 1, 1, 1, 350.00, '2026-06-18 14:11:56', 'confirmed'),
(2, 2, 2, 2, 440.00, '2026-06-18 14:11:56', 'shipped'),
(3, 3, 3, 1, 180.00, '2026-06-18 14:11:56', 'delivered'),
(4, 4, 4, 3, 360.00, '2026-06-18 14:11:56', 'pending'),
(5, 1, 5, 1, 550.00, '2026-06-18 14:11:56', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `tblclothes`
--

CREATE TABLE `tblclothes` (
  `ClothesID` int(11) NOT NULL,
  `ItemName` varchar(150) NOT NULL,
  `Description` text DEFAULT NULL,
  `Category` varchar(80) NOT NULL,
  `Size` varchar(20) NOT NULL,
  `SellPrice` decimal(10,2) NOT NULL,
  `StockQty` int(11) DEFAULT 0,
  `ImageFile` varchar(200) DEFAULT NULL,
  `AdminID` int(11) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblclothes`
--

INSERT INTO `tblclothes` (`ClothesID`, `ItemName`, `Description`, `Category`, `Size`, `SellPrice`, `StockQty`, `ImageFile`, `AdminID`, `CreatedAt`) VALUES
(1, 'Classic Denim Jacket', 'Vintage blue denim jacket', 'Jackets', 'M', 350.00, 3, 'Vintage blue denim.WEBP', 1, '2026-06-18 14:11:56'),
(2, 'Floral Summer Dress', 'Bright floral summer dress', 'Dresses', 'S', 220.00, 5, 'Floral summer dress.jpg', 1, '2026-06-18 14:11:56'),
(3, 'Slim Fit Chinos', 'Beige chinos', 'Pants', 'L', 180.00, 4, 'chinos.jpg', 1, '2026-06-18 14:11:56'),
(4, 'Graphic Tee - Pastimes', 'White cotton tee', 'Tops', 'XL', 120.00, 8, 'Graphic tee.jpg', 1, '2026-06-18 14:11:56'),
(5, 'Leather Ankle Boots', 'Brown leather boots', 'Shoes', '7', 550.00, 2, 'Leather ankle boots.jpg', 1, '2026-06-18 14:11:56'),
(6, 'Adidas sweatpants', 'black pants', 'Pants', 'M', 700.00, 11, 'Adidas pants.PNG', 3, '2026-06-18 15:51:26'),
(7, 'Adidas slides', 'blue white slides', 'shoes', '8', 700.00, 4, 'Adidas slides.PNG', 2, '2026-06-18 15:55:52'),
(8, 'Adidas sweater', 'green sweater', 'sweaters', 'L', 650.00, 3, 'Green sweater.PNG\r\n', 1, '2026-06-18 15:57:59'),
(9, 'K7 star jacket', 'stereo khakhi', 'jackets', 'L', 950.00, 1, 'K7 star stereo khakhi jacket.PNG', 5, '2026-06-18 15:59:41'),
(10, 'Lacoste socks', 'green socks', 'socks', 'M', 499.00, 6, 'lacoste socks.PNG', 3, '2026-06-18 16:01:31'),
(11, 'Nike airforce 1', 'red and black sneakers', 'shoes', '9', 1699.00, 25, 'Nike Airforce.PNG', 3, '2026-06-18 16:03:21'),
(12, 'Pringle of scotland belt', 'white and blue belt', 'belts', 'L', 450.00, 2, 'Pringle.PNG\r\n', 4, '2026-06-18 16:09:38'),
(13, 'H&M pullover', 'light brown pullover', 'Top', 'M', 299.00, 11, 'Pullover.PNG', 2, '2026-06-18 16:11:15'),
(14, 'Puma jacket', 'black puffer jacket', 'jacket', 'M', 1299.00, 4, 'Puma puffer jacket.PNG', 3, '2026-06-18 16:12:56'),
(15, 'Redbat cap', 'black cap', 'headwear', 'M', 400.00, 8, 'Redbat cap.PNG', 4, '2026-06-18 16:14:22'),
(16, 'S.P.C.C hat', 'army green bucket hat', 'headwear', 'L', 600.00, 5, 'S.P.C.C bucket hat.PNG', 5, '2026-06-18 16:15:58'),
(17, 'Floral dress', 'Bright and pink floral', 'dress', 'M', 250.00, 6, 'Bright floral summer dress.JPG', 4, '2026-06-18 16:29:38'),
(18, 'Cotton shirt', 'white cotton tee', 'shirt', 'S', 150.00, 6, 'White cotton tee.JPG', 4, '2026-06-18 16:31:27'),
(19, 'Chinos pants', 'brown pants', 'pants', '32', 200.00, 3, 'Chinos.JPG', 1, '2026-06-18 16:33:28'),
(20, 'Leather boots', 'black high boots', 'shoes', '6', 500.00, 0, 'Leather_boots.JPG', 4, '2026-06-18 16:35:14'),
(21, 'Beige chinos', 'brown slim chinos', 'pants', '28', 220.00, 9, 'Beige chinnos.JPG', 5, '2026-06-18 16:50:21'),
(22, 'silk dress', 'silk and colorful', 'dress', 'S', 460.00, 2, 'Dress.JPG', 2, '2026-06-18 16:52:16');

-- --------------------------------------------------------

--
-- Table structure for table `tblmessages`
--

CREATE TABLE `tblmessages` (
  `MessageID` int(11) NOT NULL,
  `ClothesID` int(11) NOT NULL,
  `SenderID` int(11) NOT NULL,
  `SenderName` varchar(120) NOT NULL,
  `Message` text NOT NULL,
  `SentAt` datetime NOT NULL DEFAULT current_timestamp(),
  `IsRead` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblmessages`
--

INSERT INTO `tblmessages` (`MessageID`, `ClothesID`, `SenderID`, `SenderName`, `Message`, `SentAt`, `IsRead`) VALUES
(1, 1, 7, 'TUMELO MAKOFANE', 'heyy', '2026-06-17 17:40:21', 0);

-- --------------------------------------------------------

--
-- Table structure for table `tblorderitems`
--

CREATE TABLE `tblorderitems` (
  `ItemID` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL,
  `ClothesID` int(11) NOT NULL,
  `ItemName` varchar(255) NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  `Qty` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblorderitems`
--

INSERT INTO `tblorderitems` (`ItemID`, `OrderID`, `ClothesID`, `ItemName`, `Price`, `Qty`) VALUES
(1, 1, 20, 'Leather boots', 500.00, 1),
(2, 1, 22, 'silk dress', 460.00, 1),
(3, 1, 18, 'Cotton shirt', 150.00, 1),
(4, 1, 19, 'Chinos pants', 200.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tblorders`
--

CREATE TABLE `tblorders` (
  `OrderID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `AddressType` varchar(20) NOT NULL DEFAULT 'Residential',
  `StreetAddress` varchar(255) NOT NULL,
  `Suburb` varchar(120) NOT NULL,
  `City` varchar(120) NOT NULL,
  `PostalCode` varchar(10) NOT NULL,
  `PhoneNumber` varchar(20) NOT NULL,
  `Subtotal` decimal(10,2) NOT NULL,
  `DeliveryFee` decimal(10,2) NOT NULL DEFAULT 75.00,
  `Total` decimal(10,2) NOT NULL,
  `Status` varchar(40) NOT NULL DEFAULT 'Pending',
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblorders`
--

INSERT INTO `tblorders` (`OrderID`, `UserID`, `AddressType`, `StreetAddress`, `Suburb`, `City`, `PostalCode`, `PhoneNumber`, `Subtotal`, `DeliveryFee`, `Total`, `Status`, `CreatedAt`) VALUES
(1, 6, 'Residential', '55 murray street', 'preston pad', 'mbombela', '1391', '0788141890', 1310.00, 75.00, 1385.00, 'Pending', '2026-06-18 20:11:18');

-- --------------------------------------------------------

--
-- Table structure for table `tbluser`
--

CREATE TABLE `tbluser` (
  `UserID` int(11) NOT NULL,
  `FirstName` varchar(80) NOT NULL,
  `LastName` varchar(80) NOT NULL,
  `Email` varchar(150) NOT NULL,
  `UserPass` varchar(255) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `Status` enum('pending','active','suspended') DEFAULT 'pending',
  `RegisteredAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbluser`
--

INSERT INTO `tbluser` (`UserID`, `FirstName`, `LastName`, `Email`, `UserPass`, `Phone`, `Address`, `Status`, `RegisteredAt`) VALUES
(1, 'John', 'Doe', 'j.doe@abc.co.za', '482c811da5d5b4bc6d497ffa98491e38', '0821234567', 'Pretoria', 'active', '2026-06-18 14:11:56'),
(2, 'Sarah', 'Smith', 's.smith@xyz.co.za', '482c811da5d5b4bc6d497ffa98491e38', '0839876543', 'Johannesburg', 'active', '2026-06-18 14:11:56'),
(3, 'Michael', 'Johnson', 'm.johnson@mail.co.za', '482c811da5d5b4bc6d497ffa98491e38', '0761112233', 'Cape Town', 'active', '2026-06-18 14:11:56'),
(4, 'Nomsa', 'Zulu', 'n.zulu@web.co.za', '482c811da5d5b4bc6d497ffa98491e38', '0724445566', 'Durban', 'active', '2026-06-18 14:11:56'),
(5, 'Pieter', 'van Wyk', 'p.vanwyk@net.co.za', '482c811da5d5b4bc6d497ffa98491e38', '0837778899', 'Bloemfontein', 'pending', '2026-06-18 14:11:56'),
(6, 'Lesedi', 'Makofane', 'brad255@gmail.com', 'd5df67bb9569c8f9448b0283f2adb2e1', '', '', 'active', '2026-06-18 14:19:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbladmin`
--
ALTER TABLE `tbladmin`
  ADD PRIMARY KEY (`AdminID`),
  ADD UNIQUE KEY `AdminEmail` (`AdminEmail`);

--
-- Indexes for table `tblaorder`
--
ALTER TABLE `tblaorder`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `ClothesID` (`ClothesID`);

--
-- Indexes for table `tblclothes`
--
ALTER TABLE `tblclothes`
  ADD PRIMARY KEY (`ClothesID`),
  ADD KEY `AdminID` (`AdminID`);

--
-- Indexes for table `tblmessages`
--
ALTER TABLE `tblmessages`
  ADD PRIMARY KEY (`MessageID`),
  ADD KEY `idx_clothes` (`ClothesID`),
  ADD KEY `idx_sender` (`SenderID`);

--
-- Indexes for table `tblorderitems`
--
ALTER TABLE `tblorderitems`
  ADD PRIMARY KEY (`ItemID`),
  ADD KEY `idx_order` (`OrderID`);

--
-- Indexes for table `tblorders`
--
ALTER TABLE `tblorders`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `idx_user` (`UserID`);

--
-- Indexes for table `tbluser`
--
ALTER TABLE `tbluser`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbladmin`
--
ALTER TABLE `tbladmin`
  MODIFY `AdminID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tblaorder`
--
ALTER TABLE `tblaorder`
  MODIFY `OrderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tblclothes`
--
ALTER TABLE `tblclothes`
  MODIFY `ClothesID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `tblmessages`
--
ALTER TABLE `tblmessages`
  MODIFY `MessageID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tblorderitems`
--
ALTER TABLE `tblorderitems`
  MODIFY `ItemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tblorders`
--
ALTER TABLE `tblorders`
  MODIFY `OrderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbluser`
--
ALTER TABLE `tbluser`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tblaorder`
--
ALTER TABLE `tblaorder`
  ADD CONSTRAINT `tblaorder_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `tbluser` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tblaorder_ibfk_2` FOREIGN KEY (`ClothesID`) REFERENCES `tblclothes` (`ClothesID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tblclothes`
--
ALTER TABLE `tblclothes`
  ADD CONSTRAINT `tblclothes_ibfk_1` FOREIGN KEY (`AdminID`) REFERENCES `tbladmin` (`AdminID`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
