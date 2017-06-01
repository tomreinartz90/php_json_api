<?php


if(isset($_GET['page'])){
  $page = $_GET['page'] -1;
} else {
  $page = 0;
}

if(isset($_GET['size'])){
  $size = $_GET['size'];
} else {
  $size = $default_page_size;
}

?>