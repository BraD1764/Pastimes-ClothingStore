<?php

function cleanInput($data) {
    return htmlspecialchars(trim($data));
}

function hashPassword($password) {
    return md5($password); // (later we can upgrade this)
}
?>