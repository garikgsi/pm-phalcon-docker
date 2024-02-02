<?php
use Phalcon\Mvc\Micro\Collection as MicroCollection;

return function(Phalcon\Mvc\Micro &$App) {
    $moduleHandler = 'Api\Social\\';

    $routesLike = new MicroCollection();
    $routesLike->setHandler($moduleHandler.'LikeController', true);
    $routesLike->setPrefix('/api/social/like/');

    $routesLike->post('toggle', 'toggle');

    $App->mount($routesLike);

    $routesSubscribe = new MicroCollection();
    $routesSubscribe->setHandler($moduleHandler.'SubscribeController', true);
    $routesSubscribe->setPrefix('/api/social/subscribe/');

    $routesSubscribe->post('toggle', 'toggle');

    $App->mount($routesSubscribe);
};