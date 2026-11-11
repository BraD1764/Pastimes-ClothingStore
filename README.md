# Pastimes-ClothingStore
Second-Hand Branded Clothing Web Application - PHP &amp; MySQL

Project by: BraD1764(Me) And Fortunate Lukhele

Project Overview

Pastimes Clothing Store is a PHP and MySQL web application developed to provide an online clothing store platform with customer and administrator functionality. The system allows customers to browse clothing items, register accounts, log in, manage their shopping cart, place orders, and view purchase history. Administrators can manage users, products, and monitor store activity through an administrative dashboard.


Technologies Used

· PHP

· MySQL

· HTML5

· CSS3

· JavaScript

· XAMPP

· phpMyAdmin


Database Name:
ClothingStore

Database script:
MyClothingStore.sql


System Features
Customer Features

· User Registration

· User Login and Logout

· Browse Clothing Products

· View Product Details

· Add Products to Cart

· Checkout Functionality

· Purchase History

· Session Management

Administrator Features

· Administrator Login

· Dashboard Overview

· Verify User Accounts

· Suspend User Accounts

· Delete User Accounts

· Add Clothing Items

· Edit Clothing Items

· Delete Clothing Items

· View Purchase History Reports

Installation Instructions

Step 1: Install XAMPP

Download and install XAMPP from: https://www.apachefriends.org


Step 2: Copy Project Files

Copy the project folder:

Pastimes

into:

C:\xampp\htdocs\


Step 3: Start Services

Open XAMPP Control Panel and start:
· Apache

· MySQL


Step 4: Create Database

Open phpMyAdmin:

http://localhost/phpmyadmin

Import:

MyClothingStore.sql

The script will automatically create:

ClothingStore

database and populate it with sample data.

Step 5: Configure Database Connection

Open:

includes\DBconn.php

Verify connection settings:

$host = "localhost";

$user = "root";

$password = "";

$database = "ClothingStore";

Step 6: Run Application

Navigate to:

http://localhost/Pastimes


 

Default Administrator Account 

Email: 

admin@clothingstore.co.za 

 

Password: 

admin2025 

 

Project Structure 

wede2/ 

│ 

├── admin_dashboard.php 

├── admin_login.php 

├── cart.php 

├── Checkout.php 

├── Clothes.php 

├── index.php 

├── Login.php 

├── logout.php 

├── Messages.php 

├── purchase_history.php 

├── Register.php 

│ 

├── images/ 

│ 

├── includes/ 

│   ├── DBconn.php 

│   ├── functions.php 

│   ├── header.php 

│   └── footer.php 

│ 

└── MyClothingStore.sql 

 

Purchase History Report 

The system includes a Purchase History feature that allows administrators to: 

View customer purchase records. 

Display all items purchased. 

View order dates and order status. 

Generate a printable report. 

Calculate and display the Grand Total of all purchases at the bottom of the report. 

This functionality satisfies the assessment requirement: 

"User has option to draw history of purchases. Report must show total of all purchases at bottom of page." 

 

Security Features 

Session-based authentication 

Prepared SQL statements 

Password hashing 

Access control for administrator pages 

User status verification

Project: Pastimes Clothing Store Management System 

Purpose: 
To demonstrate the implementation of a dynamic web application using PHP and MySQL, incorporating database connectivity, user management, e-commerce functionality, and reporting features. 

 
