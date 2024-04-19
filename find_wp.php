<?php

// Returns true if any of the links contain '/wp-content/'
function find_wp($links) 
{
    foreach ($links as $link) {
        if (strpos($link, '/wp-content/') !== false) {
            return true;
        }
    }
    
    return false;
}
?>