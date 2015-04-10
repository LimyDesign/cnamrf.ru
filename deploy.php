<?php

if ($_POST['payload']) {
	file_put_contents('test.txt', $_POST['playload']);
}

?>