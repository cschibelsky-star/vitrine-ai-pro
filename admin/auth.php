<?php session_start(); if(empty($_SESSION['admin'])){ header('Location: /admin/login.php'); exit; } require_once __DIR__.'/../includes/functions.php';
