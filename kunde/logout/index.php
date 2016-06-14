<?php
session_start();
header_remove();
unset($_SESSION['order_ids']);
session_destroy();
header("Location: /ewa-pizzaservice/kunde");