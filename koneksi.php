<?php
$koneksi = mysqli_connect("localhost","root","","parfum_abah");

if(!$koneksi){
    die("Koneksi Gagal : ".mysqli_connect_error());
}
?>