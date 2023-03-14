<?php

// Backward compatibility to support Error in PHP 5.6
if (!class_exists('Error')) {
    class Error extends Exception
    {
    }
}

// Backward compatibility to support TypeError in PHP 5.6
if (!class_exists('TypeError')) {
    class TypeError extends Error
    {
    }
}
