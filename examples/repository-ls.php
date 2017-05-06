<?php declare(strict_types=1);

use ApiClients\Client\Github\AsyncClient;
use ApiClients\Client\Github\Authentication\Anonymous;
use ApiClients\Client\Github\Resource\Async\Repository;
use ApiClients\Client\Github\Resource\Async\User;
use React\EventLoop\Factory;
use React\Filesystem\Filesystem;
use WyriHaximus\React\Filesystem\Github\RepositoryAdapter;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$loop = Factory::create();

$github = AsyncClient::create($loop, new Anonymous());

$github->user('php-api-clients')->then(function (User $user) {
    return $user->repository('github');
})->then(function (Repository $repository) use ($loop) {
    $options = [
        'repository' => $repository,
    ];
    $adapter = new RepositoryAdapter($loop, $options);
    return Filesystem::createFromAdapter($adapter);
})->then(function (Filesystem $filesystem) {
    return $filesystem->dir('/')->ls();
})->done(function ($nodes) {
    foreach ($nodes as $node) {
        echo get_class($node), ': ', $node->getPath(), PHP_EOL;
    }
});

$loop->run();