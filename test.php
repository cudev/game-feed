<?php

use GameFeed\Games;
use GameFeed\Retrievers\ArcadeGameFeed;
use GameFeed\Retrievers\Spilgames;

require __DIR__ . '/vendor/autoload.php';

$spilgames = new Spilgames();
$arcadeGameFeed = new ArcadeGameFeed();

$games = Games::from($arcadeGameFeed);
foreach ($games as $game) {
    var_dump($game);
}