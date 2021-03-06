# Game Feed
###:video_game: Library for retrieving browser games from public feeds

Package is shipped with a few retrievers for popular providers such as:
- [Spilgames](http://www.spilgames.com/)
- [Arcade Game Feed](http://arcadegamefeed.com/)
- [2PG](http://www.2pg.com/)

Others can be easily added.

## Requirements
- php 7
- Guzzle 6

## Install
Via Composer

``` bash
$ composer require cudev/game-feed
```

## Overview

Package consists of a bunch of classes that implement `RetrieverInterface` and a `Games` class on its own.
`Games` is a composite iterator of `RetrieverInterface` implementers.

`RetrieverInterface` as simple as that:
```php
<?php

// notice Countable
interface RetrieverInterface extends Countable
{
    public function retrieve(): Generator;
}
```
Main purpose of the interface is to retrieve a game from a provider and `yield` it. An implementer must
also be able to count all games from a provider.

----

To get games, simply instantiate `Games` and pass retrievers to it.
As mentioned above, `Games` is an `Iterator`, therefore directly iterate over it.
```php
<?php

use GameFeed\Games;
use GameFeed\Retrievers\Spilgames;
use GameFeed\Retrievers\ArcadeGameFeed;
use GameFeed\Retrievers\TwoPlayerGames;

// classic way
$games = new Games(new Spilgames(), new ArcadeGameFeed(), new TwoPlayerGames());

// in my opinion, this one is more eloquent
$games = Games::from(new Spilgames(), new ArcadeGameFeed(), new TwoPlayerGames());

/** @var array $game */
foreach ($games as $game) {
    // ...
}
```

---

A received game might be in any format from API or RSS (*usually, it's some sort of a string*), what is not quite useful
most of a time. Using `Spilgames` (*JSON response*) with `json_decode($unprocessedGame, true)`, for example:
```php
array(10) {
  'width'       => int(700)
  'height'      => int(500)
  'id'          => string(18) "576742227280294192"
  'description' => string(109) "The orcs think they can conquer your kingdom in no time flat. You’re not going to let that happen, are you?"
  'subcategory' => string(6) "Action"
  'technology'  => NULL
  'thumbnails'  =>
    array(3) {
      'small'   => string(60) "http://images.cdn.spilcloud.com/thumbs-4-8/100X75_160048.jpg"
      'medium'  => string(60) "http://images.cdn.spilcloud.com/thumbs-4-8/120X90_160048.jpg"
      'large'   => string(61) "http://images.cdn.spilcloud.com/thumbs-4-8/200X120_160048.jpg"
    }
  'gameUrl'     => string(53) "http://games.cdn.spilcloud.com/s/StackerWar_final.swf"
  'title'       => string(11) "Stacker War"
  'category'    => string(6) "Action"
}
```
And after `ArcadeGameFeed` (*XML response*) with `simplexml_load_string($unprocessedGame, null, LIBXML_NOCDATA)`:
```php
class SimpleXMLElement#56 (3) {
  public $title       => string(16) " Kings Castle 4 "
  public $link        => string(57) " http://arcadegamefeed.com/view/2228/Kings-Castle-4.html "
  public $description => string(609) " <img src='http://arcadegamefeed.com/img/agf-kings_castle-4180x135.jpg' > Dream up a situation that you are in "...
}
```

---

Shipped retrievers allow usage of special transformers (*callables*) for altering received games to a desired format.
An anonymous function, in the example below, will be called on every game,
though any class with `__invoke($unprocessedGame)` method signature can be used.
```php
<?php

use GameFeed\Games;
use GameFeed\Retrievers\Spilgames;

$transformer = function ($unprocessedGame) {
    /* transform to a common format */
    return $transformed;
};

$spilgames = new Spilgames($transformer);

/** @var array $game */
foreach (Games::from($spilgames) as $game) {
    // ...
}
```

---

Retrievers in the package try to fetch games as lazily as possible. However, a little caching never hurt anyone (arguably).
Provided retrievers can utilise any PSR-6 compliant library.

Using [Stash](http://www.stashphp.com/), for example:
```php
<?php

use GameFeed\Games;
use GameFeed\Retrievers\ArcadeGameFeed;
use Stash\Driver\FileSystem;
use Stash\Pool;

$transformer = function ($unprocessedGame) {
    /* transform to a common format */
    return $transformed;
};

// Cache is used to save raw responses from API or RSS
$cache = new Pool(new FileSystem());
$spilgames = new ArcadeGameFeed($transformer, $cache);


// By default cache expires in one day, it can be altered
$spilgames->setCacheExpiresAfter(DateInterval::createFromDateString('1 minute'));

/** @var array $game */
foreach (Games::from($spilgames) as $game) {
    // ...
}
```

---

And lastly, if something goes wrong, `RetrieverException` is thrown
```php
<?php
use GameFeed\Games;
use GameFeed\Exceptions\RetrieverException;

try {
    foreach (Games::from($spilgames) as $game) {
        // ...
    }
} catch (RetrieverException $exception) {
    // ...
}
```

## Copyright and license
Code and documentation copyright 2016 CUDEV. Code released under [the MIT license](https://github.com/cudev/game-feed/blob/master/LICENSE).
