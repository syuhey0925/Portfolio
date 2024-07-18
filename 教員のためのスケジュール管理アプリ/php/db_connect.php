<?php
$servername = "localhost";
$username = "root";
$password = "pxpdnsiku0627";
$dbname = "schedule_db";

// データベースに接続
$conn = new mysqli($servername, $username, $password, $dbname);

// 接続確認
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}