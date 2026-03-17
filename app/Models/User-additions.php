<?php
/*
|----------------------------------------------------------------------
| ADD these two methods to your existing app/Models/User.php
| Inside the class body, before the closing brace.
|----------------------------------------------------------------------
*/

    /**
     * Check if the user has the admin role.
     * Add  ->role  column or use a simple flag — adjust to your schema.
     * For now returns true if this is user #1 (first registered).
     */
   