#!/bin/bash
clear

echo
echo ----- Front-end tests -----
echo 
phpunit --bootstrap ./bootstrap.php BenderTest.php
