<?php

/*
 * Demoplatz (database/seeders/DemoCourseSeeder.php).
 * DEMO_DATA=true legt ihn beim Deploy an, falls er fehlt; DEMO_DATA_RESET=true legt ihn komplett neu an
 * (löscht dabei Clubmitgliedschaften, Club-Chat und Merklisten des Demoplatzes).
 */
return [
    'reset' => (bool) env('DEMO_DATA_RESET', false),
];
